<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user || !$user->hasAnyRole(['ict_administrator', 'executive_director', 'deputy_director'])) {
                abort(403, 'Unauthorized.');
            }
            return $next($request);
        });
    }
    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        return Inertia::render('Admin/Users/Index', [
            'users'   => $query->orderBy('name')->paginate(10)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Users/Create', [
            'roles' => Role::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:8|confirmed',
            'phone'     => 'nullable|string|max:30',
            'role'      => 'required|exists:roles,name',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'phone'     => $validated['phone'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $user->load('roles');
        return Inertia::render('Admin/Users/Show', ['user' => $user]);
    }

    public function edit(User $user)
    {
        $user->load('roles');
        return Inertia::render('Admin/Users/Edit', [
            'user'  => $user,
            'roles' => Role::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,'.$user->id,
            'password'  => 'nullable|string|min:8|confirmed',
            'phone'     => 'nullable|string|max:30',
            'role'      => 'required|exists:roles,name',
            'is_active' => 'boolean',
        ]);

        $authUser = auth()->user();

        // Prevent self-suspension
        if ($user->id === $authUser->id && $request->has('is_active') && !$request->boolean('is_active')) {
            return redirect()->back()->withErrors(['is_active' => 'You cannot suspend your own account.']);
        }

        // Prevent Deputy Director from suspending Executive Director
        if ($authUser->hasRole('deputy_director') && $user->hasRole('executive_director') && $request->has('is_active') && !$request->boolean('is_active')) {
            return redirect()->back()->withErrors(['is_active' => 'As Deputy Director, you do not have permission to suspend the Executive Director account.']);
        }

        $user->update([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'phone'     => $validated['phone'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $user->is_active,
            'password'  => $validated['password'] ? Hash::make($validated['password']) : $user->password,
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    public function toggle(User $user)
    {
        $authUser = auth()->user();

        // Don't let users suspend themselves!
        if ($user->id === $authUser->id) {
            return redirect()->back()->with('error', 'You cannot suspend your own account.');
        }

        // Prevent Deputy Director from suspending Executive Director
        if ($authUser->hasRole('deputy_director') && $user->hasRole('executive_director')) {
            return redirect()->back()->with('error', 'As Deputy Director, you do not have permission to suspend the Executive Director account.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'activated' : 'suspended';
        return redirect()->back()->with('success', "User account {$status} successfully.");
    }
}

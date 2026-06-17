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
        $countQuery = User::query();

        if ($request->filled('search')) {
            $countQuery->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%')
                  ->orWhere('phone', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('unit_id')) {
            $countQuery->where('department_id', $request->unit_id);
        }

        if ($request->filled('section_id')) {
            $countQuery->where('section_id', $request->section_id);
        }

        if ($request->filled('status')) {
            $countQuery->where('is_active', $request->status === 'active');
        }

        // Calculate counts dynamically based on active search/scoping/status filters
        $staffCount = (clone $countQuery)->where(function ($q) {
            $q->whereDoesntHave('roles')
              ->orWhereHas('roles', function ($roleQuery) {
                  $roleQuery->whereNotIn('name', ['client', 'student']);
              });
        })->count();

        $clientCount = (clone $countQuery)->whereHas('roles', function ($roleQuery) {
            $roleQuery->where('name', 'client');
        })->count();

        $studentCount = (clone $countQuery)->whereHas('roles', function ($roleQuery) {
            $roleQuery->where('name', 'student');
        })->count();

        $counts = [
            'staff'   => $staffCount,
            'client'  => $clientCount,
            'student' => $studentCount,
            'all'     => (clone $countQuery)->count(),
        ];

        // Get category from request, default to 'staff'
        $category = $request->input('category', 'staff');

        if ($category === 'client') {
            $query = (clone $countQuery)->whereHas('roles', function ($roleQuery) {
                $roleQuery->where('name', 'client');
            });
        } elseif ($category === 'student') {
            $query = (clone $countQuery)->whereHas('roles', function ($roleQuery) {
                $roleQuery->where('name', 'student');
            });
        } elseif ($category === 'all') {
            $query = (clone $countQuery);
        } else {
            $category = 'staff';
            $query = (clone $countQuery)->where(function ($q) {
                $q->whereDoesntHave('roles')
                  ->orWhereHas('roles', function ($roleQuery) {
                      $roleQuery->whereNotIn('name', ['client', 'student']);
                  });
            });

            // Only apply specific role filter to staff category
            if ($request->filled('role')) {
                $query->whereHas('roles', function($q) use ($request) {
                    $q->where('name', $request->role);
                });
            }
        }

        $users = $query->with(['roles', 'department', 'section', 'msunliRole'])
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users'   => $users,
            'filters' => $request->only(['search', 'unit_id', 'section_id', 'role', 'status', 'category']),
            'units'   => \App\Models\Department::orderBy('name')->get(['id', 'name', 'code']),
            'sections'=> \App\Models\MsunliSection::orderBy('name')->get(['id', 'name', 'unit_id']),
            'roles'   => \Spatie\Permission\Models\Role::orderBy('name')->get(['id', 'name']),
            'counts'  => $counts,
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:pdf,excel,csv',
            'category' => 'nullable|string',
        ]);

        $format = $request->format;
        $category = $request->input('category', 'all');

        $query = User::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%')
                  ->orWhere('phone', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('unit_id')) {
            $query->where('department_id', $request->unit_id);
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($category === 'client') {
            $query->whereHas('roles', function ($roleQuery) {
                $roleQuery->where('name', 'client');
            });
        } elseif ($category === 'student') {
            $query->whereHas('roles', function ($roleQuery) {
                $roleQuery->where('name', 'student');
            });
        } elseif ($category === 'staff') {
            $query->where(function ($q) {
                $q->whereDoesntHave('roles')
                  ->orWhereHas('roles', function ($roleQuery) {
                      $roleQuery->whereNotIn('name', ['client', 'student']);
                  });
            });

            if ($request->filled('role')) {
                $query->whereHas('roles', function($q) use ($request) {
                    $q->where('name', $request->role);
                });
            }
        }

        $users = $query->with(['roles', 'department', 'section', 'msunliRole'])
            ->orderBy('name')
            ->get();

        $title = ucwords($category) . ' Users Export';
        $generatedBy = auth()->user()->name;
        $generatedDate = now()->format('F d, Y h:i A');

        if ($format === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.users_pdf', [
                'title' => $title,
                'users' => $users,
                'generated_by' => $generatedBy,
                'generated_date' => $generatedDate,
            ]);
            return $pdf->download('users_export_' . $category . '_' . time() . '.pdf');
        } else {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="users_export_' . $category . '_' . time() . '.csv"',
            ];

            $callback = function() use ($users) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['MSUNLI USER REGISTRY EXPORT']);
                fputcsv($file, ['Generated By:', auth()->user()->name]);
                fputcsv($file, ['Date:', now()->format('F d, Y h:i A')]);
                fputcsv($file, []);
                fputcsv($file, ['Name', 'Email Address', 'Phone Number', 'Role', 'Department/Unit', 'Section', 'Status']);

                foreach ($users as $user) {
                    $roleName = $user->msunliRole ? $user->msunliRole->name : ($user->roles->first() ? ucwords(str_replace('_', ' ', $user->roles->first()->name)) : 'Staff');
                    fputcsv($file, [
                        $user->name,
                        $user->email,
                        $user->phone ?? 'N/A',
                        $roleName,
                        $user->department ? $user->department->name : 'N/A',
                        $user->section ? $user->section->name : 'N/A',
                        $user->is_active ? 'Active' : 'Inactive'
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }
    }

    public function create()
    {
        return Inertia::render('Admin/Users/Create', [
            'units'    => \App\Models\Department::orderBy('name')->get(['id', 'name', 'code']),
            'sections' => \App\Models\MsunliSection::orderBy('name')->get(['id', 'name', 'unit_id']),
            'msunli_roles' => \App\Models\MsunliRole::with('spatieRole')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email',
            'password'         => 'required|string|min:8|confirmed',
            'phone'            => 'nullable|string|max:30',
            'unit_id'          => 'required|exists:departments,id',
            'section_id'       => 'required|exists:msunli_sections,id',
            'msunli_role_id'   => 'required|exists:msunli_roles,id',
            'is_active'        => 'boolean',
        ]);

        // Hierarchical dynamic dependency validation to prevent invalid role assignments
        $section = \App\Models\MsunliSection::findOrFail($validated['section_id']);
        if ((int)$section->unit_id !== (int)$validated['unit_id']) {
            return redirect()->back()->withErrors(['section_id' => 'The selected department/section does not belong to the selected institutional Unit.']);
        }

        $msunliRole = \App\Models\MsunliRole::with('spatieRole')->findOrFail($validated['msunli_role_id']);
        if ((int)$msunliRole->section_id !== (int)$validated['section_id']) {
            return redirect()->back()->withErrors(['msunli_role_id' => 'The selected role is not configured under the selected department/section.']);
        }

        $authUser = auth()->user();

        // Limit role assignments based on governance rules: both roles can only be assigned by ICT Admin and Executive Director
        if (in_array($msunliRole->name, ['Executive Director', 'Deputy Director'])) {
            if (!$authUser || !$authUser->hasAnyRole(['ict_administrator', 'executive_director'])) {
                // Log failed attempt for auditing
                \App\Models\ActivityLog::log('failed_role_assignment', 'Unauthorized attempt to assign executive role: ' . $msunliRole->name . ' by ' . ($authUser ? $authUser->email : 'guest'), null, [
                    'user_performing_action' => $authUser ? ($authUser->name . ' (' . $authUser->email . ')') : 'guest',
                    'assigned_role' => $msunliRole->name,
                    'previous_role' => null,
                    'new_role' => $msunliRole->name,
                    'action_type' => 'failed_role_assignment',
                    'ip_address' => request()->ip(),
                    'date_and_time' => now()->toIso8601String(),
                ]);
                return redirect()->back()->withErrors(['msunli_role_id' => 'Only the ICT Administrator and Executive Director can assign the Executive Director or Deputy Director roles.']);
            }
        }

        $user = User::create([
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'password'        => Hash::make($validated['password']),
            'phone'           => $validated['phone'] ?? null,
            'department_id'   => $validated['unit_id'],
            'section_id'      => $validated['section_id'],
            'msunli_role_id'  => $validated['msunli_role_id'],
            'is_active'       => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        // Sync Spatie role automatically mapped according to role assignments
        $rolesToSync = [$msunliRole->spatieRole->name];
        $user->loadMissing('department');
        if ($user->department && $user->department->code !== 'AOS') {
            $rolesToSync[] = 'language_expert';
        }
        $user->syncRoles($rolesToSync);

        // Record Audit Trail for Executive-level user creation
        if (in_array($msunliRole->name, ['Executive Director', 'Deputy Director'])) {
            $properties = [
                'user_performing_action' => $authUser->name . ' (' . $authUser->email . ')',
                'assigned_role' => $msunliRole->name,
                'date_and_time' => now()->toIso8601String(),
                'previous_role' => null,
                'new_role' => $msunliRole->name,
                'action_type' => 'Create',
                'ip_address' => request()->ip(),
                'new_values' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'department_id' => $user->department_id,
                    'section_id' => $user->section_id,
                    'msunli_role_id' => $user->msunli_role_id,
                    'is_active' => $user->is_active,
                ],
            ];
            \App\Models\ActivityLog::log('Create', "Created executive user {$user->name} as {$msunliRole->name}", $user, $properties);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $user->load(['roles', 'department', 'section', 'msunliRole']);
        return Inertia::render('Admin/Users/Show', ['user' => $user]);
    }

    public function edit(User $user)
    {
        $user->load(['roles', 'department', 'section', 'msunliRole']);
        return Inertia::render('Admin/Users/Edit', [
            'user'     => $user,
            'units'    => \App\Models\Department::orderBy('name')->get(['id', 'name', 'code']),
            'sections' => \App\Models\MsunliSection::orderBy('name')->get(['id', 'name', 'unit_id']),
            'msunli_roles' => \App\Models\MsunliRole::with('spatieRole')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email,'.$user->id,
            'password'         => 'nullable|string|min:8|confirmed',
            'phone'            => 'nullable|string|max:30',
            'unit_id'          => 'required|exists:departments,id',
            'section_id'       => 'required|exists:msunli_sections,id',
            'msunli_role_id'   => 'required|exists:msunli_roles,id',
            'is_active'        => 'boolean',
        ]);

        // Hierarchical dynamic dependency validation to prevent invalid role assignments
        $section = \App\Models\MsunliSection::findOrFail($validated['section_id']);
        if ((int)$section->unit_id !== (int)$validated['unit_id']) {
            return redirect()->back()->withErrors(['section_id' => 'The selected department/section does not belong to the selected institutional Unit.']);
        }

        $msunliRole = \App\Models\MsunliRole::with('spatieRole')->findOrFail($validated['msunli_role_id']);
        if ((int)$msunliRole->section_id !== (int)$validated['section_id']) {
            return redirect()->back()->withErrors(['msunli_role_id' => 'The selected role is not configured under the selected department/section.']);
        }

        $authUser = auth()->user();

        // Limit role assignments based on governance rules: both roles can only be assigned by ICT Admin and Executive Director
        if (in_array($msunliRole->name, ['Executive Director', 'Deputy Director'])) {
            if (!$authUser || !$authUser->hasAnyRole(['ict_administrator', 'executive_director'])) {
                // Log failed attempt for auditing
                \App\Models\ActivityLog::log('failed_role_assignment', 'Unauthorized attempt to assign executive role: ' . $msunliRole->name . ' by ' . ($authUser ? $authUser->email : 'guest'), null, [
                    'user_performing_action' => $authUser ? ($authUser->name . ' (' . $authUser->email . ')') : 'guest',
                    'assigned_role' => $msunliRole->name,
                    'previous_role' => $user->msunliRole ? $user->msunliRole->name : null,
                    'new_role' => $msunliRole->name,
                    'action_type' => 'failed_role_assignment',
                    'ip_address' => request()->ip(),
                    'date_and_time' => now()->toIso8601String(),
                ]);
                return redirect()->back()->withErrors(['msunli_role_id' => 'Only the ICT Administrator and Executive Director can assign the Executive Director or Deputy Director roles.']);
            }
        }

        // Prevent self-suspension
        if ($user->id === $authUser->id && $request->has('is_active') && !$request->boolean('is_active')) {
            return redirect()->back()->withErrors(['is_active' => 'You cannot suspend your own account.']);
        }

        // Prevent Deputy Director from suspending Executive Director
        if ($authUser->hasRole('deputy_director') && $user->hasRole('executive_director') && $request->has('is_active') && !$request->boolean('is_active')) {
            return redirect()->back()->withErrors(['is_active' => 'As Deputy Director, you do not have permission to suspend the Executive Director account.']);
        }

        // Capture original values for audit trail
        $user->load('msunliRole');
        $originalRoleName = $user->msunliRole ? $user->msunliRole->name : null;
        $originalValues = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'department_id' => $user->department_id,
            'section_id' => $user->section_id,
            'msunli_role_id' => $user->msunli_role_id,
            'is_active' => $user->is_active === 1 || $user->is_active === true || $user->is_active === '1',
        ];

        $user->fill([
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'phone'           => $validated['phone'] ?? null,
            'department_id'   => $validated['unit_id'],
            'section_id'      => $validated['section_id'],
            'msunli_role_id'  => $validated['msunli_role_id'],
            'is_active'       => $request->has('is_active') ? $request->boolean('is_active') : $user->is_active,
            'password'        => $validated['password'] ? Hash::make($validated['password']) : $user->password,
        ]);

        $roleChanged = !$user->hasRole($msunliRole->spatieRole->name);

        if (!$user->isDirty() && !$roleChanged) {
            return redirect()->back()->with('error', 'No changes detected. Record remains unchanged.');
        }

        $user->save();
        $rolesToSync = [$msunliRole->spatieRole->name];
        $user->loadMissing('department');
        if ($user->department && $user->department->code !== 'AOS') {
            $rolesToSync[] = 'language_expert';
        }
        $user->syncRoles($rolesToSync);

        // Record Audit Trail for Executive-level user modification
        if (in_array($originalRoleName, ['Executive Director', 'Deputy Director']) || in_array($msunliRole->name, ['Executive Director', 'Deputy Director'])) {
            $actionType = 'Update';
            if ($originalRoleName !== $msunliRole->name) {
                $actionType = 'Reassigned';
            } elseif ($originalValues['is_active'] !== ($user->is_active === 1 || $user->is_active === true || $user->is_active === '1')) {
                $actionType = $user->is_active ? 'Activate' : 'Deactivate';
            }

            $properties = [
                'user_performing_action' => $authUser->name . ' (' . $authUser->email . ')',
                'assigned_role' => $msunliRole->name,
                'date_and_time' => now()->toIso8601String(),
                'previous_role' => $originalRoleName,
                'new_role' => $msunliRole->name,
                'action_type' => $actionType,
                'ip_address' => request()->ip(),
                'previous_values' => $originalValues,
                'new_values' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'department_id' => $user->department_id,
                    'section_id' => $user->section_id,
                    'msunli_role_id' => $user->msunli_role_id,
                    'is_active' => $user->is_active === 1 || $user->is_active === true || $user->is_active === '1',
                ],
            ];
            \App\Models\ActivityLog::log($actionType, "{$actionType}d executive user {$user->name}", $user, $properties);
        }

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

        // Record Audit Trail for Executive-level user status toggle
        $user->load('msunliRole');
        if ($user->msunliRole && in_array($user->msunliRole->name, ['Executive Director', 'Deputy Director'])) {
            $actionType = $user->is_active ? 'Activate' : 'Deactivate';
            $properties = [
                'user_performing_action' => $authUser->name . ' (' . $authUser->email . ')',
                'assigned_role' => $user->msunliRole->name,
                'date_and_time' => now()->toIso8601String(),
                'previous_role' => $user->msunliRole->name,
                'new_role' => $user->msunliRole->name,
                'action_type' => $actionType,
                'ip_address' => request()->ip(),
            ];
            \App\Models\ActivityLog::log($actionType, "{$actionType}d executive user {$user->name}", $user, $properties);
        }

        $status = $user->is_active ? 'activated' : 'suspended';
        return redirect()->back()->with('success', "User account {$status} successfully.");
    }
}

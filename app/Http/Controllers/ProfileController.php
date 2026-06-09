<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit()
    {
        return Inertia::render('Profile/Edit', [
            'user' => Auth::user()
        ]);
    }

    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $canEditEmail = $user->hasAnyRole(['executive_director', 'deputy_director', 'ict_administrator']);

        if (!$canEditEmail && $request->has('email') && $request->input('email') !== $user->email) {
            return redirect()->back()->withErrors(['email' => 'Only the Executive Director, Deputy Director, or ICT Administrator can update the email address.']);
        }

        $rules = [
            'name'     => 'required|string|max:255',
            'password' => 'nullable|string|min:8|confirmed',
            'avatar'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];

        if ($canEditEmail) {
            $rules['email'] = 'required|email|max:255|unique:users,email,' . $user->id;
        }

        $validated = $request->validate($rules);

        $user->name = $validated['name'];
        if ($canEditEmail) {
            $user->email = $request->input('email');
        }

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        if (!$user->isDirty()) {
            return redirect()->back()->with('error', 'No changes detected. Record remains unchanged.');
        }

        $user->save();

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function deleteAvatar()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
            $user->save();
        }

        return redirect()->back()->with('success', 'Profile picture removed successfully.');
    }
}

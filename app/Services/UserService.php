<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserService
{
    public function createUser(array $data)
    {
        $data['password'] = Hash::make($data['password']);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
        ]);

        $user->is_active = true;
        $user->save();

        $user->syncRoles([$data['role']]);

        return $user;
    }


    public function updateUser(User $user, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        $user->syncRoles([$data['role']]);
        
        return $user;
    }

    public function toggleUser(User $user)
    {
        if ($user->id === Auth::id()) {
            throw new \Exception('Cannot deactivate your own account.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return $user;
    }

    public function deleteUser(User $user)
    {
        if ($user->id === Auth::id()) {
            throw new \Exception('Cannot delete your own account.');
        }

        if ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
            throw new \Exception('Cannot delete the last admin account.');
        }

        $user->delete();
    }
}

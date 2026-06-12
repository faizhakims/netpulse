<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage users');
    }

    public function rules(): array
    {
        $userId = $this->route('id'); // Route parameter {id}

        $rules = [
            'name'  => 'required|string|max:80',
            'email' => 'required|email|unique:users,email,' . $userId,
            'role'  => 'required|in:admin,operator,viewer',
        ];

        // Password is optional on update; only validate if provided
        if ($this->filled('password')) {
            $rules['password'] = ['required', Password::min(8)];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique'   => 'Email sudah digunakan oleh akun lain.',
            'role.required'  => 'Role wajib dipilih.',
            'role.in'        => 'Role tidak valid. Pilih admin, operator, atau viewer.',
        ];
    }
}

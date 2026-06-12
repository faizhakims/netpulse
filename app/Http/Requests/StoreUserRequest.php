<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage users');
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:80',
            'email'    => 'required|email|unique:users,email',
            'role'     => 'required|in:admin,operator,viewer',
            'password' => ['required', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Nama wajib diisi.',
            'email.required'    => 'Email wajib diisi.',
            'email.unique'      => 'Email sudah terdaftar.',
            'role.required'     => 'Role wajib dipilih.',
            'role.in'           => 'Role tidak valid. Pilih admin, operator, atau viewer.',
            'password.required' => 'Password wajib diisi.',
        ];
    }
}

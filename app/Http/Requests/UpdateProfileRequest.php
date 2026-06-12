<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Any authenticated user can update their own profile
    }

    public function rules(): array
    {
        $rules = [
            'name'  => 'required|string|max:80',
            'email' => 'required|email|unique:users,email,' . $this->user()->id,
        ];

        // Only require password fields when the user is trying to change their password
        if ($this->filled('new_password')) {
            $rules['current_password'] = 'required';
            $rules['new_password']     = ['required', 'confirmed', Password::min(8)];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'             => 'Nama wajib diisi.',
            'email.required'            => 'Email wajib diisi.',
            'email.email'               => 'Format email tidak valid.',
            'email.unique'              => 'Email sudah digunakan oleh akun lain.',
            'current_password.required' => 'Password saat ini wajib diisi untuk mengganti password.',
            'new_password.required'     => 'Password baru wajib diisi.',
            'new_password.confirmed'    => 'Konfirmasi password baru tidak cocok.',
        ];
    }
}

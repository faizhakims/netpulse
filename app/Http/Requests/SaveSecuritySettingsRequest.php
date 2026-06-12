<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveSecuritySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage settings');
    }

    public function rules(): array
    {
        return [
            'session_timeout'         => 'required|integer|min:5|max:1440',
            'max_login_attempts'      => 'required|integer|min:3|max:20',
            'lockout_duration'        => 'required|integer|min:1|max:60',
            'require_strong_password' => 'boolean',
            'log_all_logins'          => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'session_timeout.min'    => 'Session timeout minimum adalah 5 menit.',
            'session_timeout.max'    => 'Session timeout maksimum adalah 1440 menit (24 jam).',
            'max_login_attempts.min' => 'Minimum percobaan login adalah 3 kali.',
            'max_login_attempts.max' => 'Maksimum percobaan login adalah 20 kali.',
            'lockout_duration.min'   => 'Durasi lockout minimum adalah 1 menit.',
            'lockout_duration.max'   => 'Durasi lockout maksimum adalah 60 menit.',
        ];
    }
}

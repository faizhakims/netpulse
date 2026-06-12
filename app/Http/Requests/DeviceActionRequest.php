<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeviceActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage devices');
    }

    public function rules(): array
    {
        return [
            'device' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'device.required' => 'Nama device wajib diisi.',
        ];
    }
}

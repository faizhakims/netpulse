<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage settings');
    }

    public function rules(): array
    {
        return [
            'site_name'     => 'required|string|max:80',
            'site_timezone' => 'required|string|max:60',
            'date_format'   => 'required|string|max:40',
            'site_language' => 'required|string|max:10',
        ];
    }
}

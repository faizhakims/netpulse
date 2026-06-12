<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveMonitoringSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage settings');
    }

    public function rules(): array
    {
        return [
            'polling_interval'       => 'required|integer|min:10|max:3600',
            'latency_threshold'      => 'required|numeric|min:1',
            'packet_loss_threshold'  => 'required|numeric|min:0|max:100',
            'retention_days'         => 'required|integer|min:1|max:365',
            'auto_resolve_incidents' => 'boolean',
            'auto_create_incidents'  => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'polling_interval.min'  => 'Polling interval minimum adalah 10 detik.',
            'polling_interval.max'  => 'Polling interval maksimum adalah 3600 detik (1 jam).',
            'retention_days.min'    => 'Retention period minimum adalah 1 hari.',
            'retention_days.max'    => 'Retention period maksimum adalah 365 hari.',
            'packet_loss_threshold.max' => 'Packet loss threshold tidak boleh melebihi 100%.',
        ];
    }
}

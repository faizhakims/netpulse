<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class AlertRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage alerts');
    }

    public function rules(): array
    {
        return [
            'title'           => 'required|string|max:120',
            'target_device'   => 'nullable|string|max:100',
            'metric_type'     => 'required|in:latency,status,bandwidth,packet_loss',
            'condition'       => 'required|in:gt,lt,eq,is_down,is_up',
            'threshold_value' => 'nullable|numeric',
            'duration'        => 'required|in:1m,5m,10m,15m,30m',
            'severity'        => 'required|in:critical,warning,info',
            'channels'        => 'required|array|min:1',
            'channels.*'      => 'in:telegram,email',
            'is_active'       => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => 'Judul rule wajib diisi.',
            'metric_type.required' => 'Metric type wajib dipilih.',
            'metric_type.in'       => 'Metric type tidak valid.',
            'condition.required'   => 'Kondisi wajib dipilih.',
            'condition.in'         => 'Kondisi tidak valid.',
            'duration.required'    => 'Durasi wajib dipilih.',
            'duration.in'          => 'Durasi tidak valid.',
            'severity.required'    => 'Severity wajib dipilih.',
            'severity.in'          => 'Severity tidak valid.',
            'channels.required'    => 'Minimal satu channel notifikasi harus dipilih.',
            'channels.min'         => 'Minimal satu channel notifikasi harus dipilih.',
            'channels.*.in'        => 'Channel notifikasi tidak valid.',
        ];
    }

    /**
     * Add cross-field validation: condition must be compatible with metric_type,
     * and threshold_value is required for numeric conditions.
     */
    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $metric    = $this->input('metric_type');
            $condition = $this->input('condition');
            $threshold = $this->input('threshold_value');

            if (!$metric || !$condition) return; // Already caught by field-level rules

            // status metric: only is_down / is_up allowed
            if ($metric === 'status' && !in_array($condition, ['is_down', 'is_up'])) {
                $validator->errors()->add(
                    'condition',
                    "Metric 'status' hanya mendukung kondisi 'is_down' atau 'is_up'."
                );
                return;
            }

            // numeric metrics: is_down / is_up not allowed
            if ($metric !== 'status' && in_array($condition, ['is_down', 'is_up'])) {
                $validator->errors()->add(
                    'condition',
                    "Kondisi 'is_down'/'is_up' hanya berlaku untuk metric 'status'."
                );
                return;
            }

            // threshold required for non-boolean conditions
            if (!in_array($condition, ['is_down', 'is_up'])) {
                if ($threshold === null || $threshold === '') {
                    $validator->errors()->add(
                        'threshold_value',
                        'Threshold Value wajib diisi untuk kondisi ini.'
                    );
                }
            }
        });
    }

    /**
     * Override failedValidation to always return JSON for this API endpoint.
     */
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'ok'      => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}

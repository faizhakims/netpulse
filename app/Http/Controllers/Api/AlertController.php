<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AlertResource;
use App\Http\Requests\StoreAlertRuleRequest;
use App\Http\Requests\UpdateAlertRuleRequest;
use App\Models\AlertRule;
use App\Services\AlertService;
use Illuminate\Http\Request;

class AlertController extends BaseApiController
{
    public function __construct(private AlertService $alertService) {}

    public function index(Request $request)
    {
        if (!auth()->user()->can('view alerts')) {
            return $this->error('Forbidden.', 403);
        }

        $query = AlertRule::query();

        if ($search = $request->query('search')) {
            $query->where(fn($q) =>
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('targetDevice', fn($q2) => $q2->where('name', 'like', "%{$search}%"))
            );
        }

        if ($status = $request->query('status')) {
            $query->where('is_active', $status === 'active');
        }

        if ($severity = $request->query('severity')) {
            $query->where('severity', $severity);
        }

        $sort = $request->query('sort', 'sort_order');
        $dir  = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $col  = ltrim($sort, '-');
        $allowed = ['sort_order', 'title', 'severity', 'created_at'];
        if (!in_array($col, $allowed)) $col = 'sort_order';

        $query->orderBy($col, $dir);

        $rules = $query->paginate((int) $request->query('per_page', 20));

        return $this->success([
            'items'        => AlertResource::collection($rules->items()),
            'total'        => $rules->total(),
            'per_page'     => $rules->perPage(),
            'current_page' => $rules->currentPage(),
            'last_page'    => $rules->lastPage(),
        ]);
    }

    public function show(int $id)
    {
        if (!auth()->user()->can('view alerts')) {
            return $this->error('Forbidden.', 403);
        }

        $rule = AlertRule::find($id);

        if (!$rule) {
            return $this->error('Alert rule not found.', 404);
        }

        return $this->success(new AlertResource($rule));
    }

    public function store(StoreAlertRuleRequest $request)
    {
        
        $data             = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        if (in_array($data['condition'], ['is_down', 'is_up'])) {
            $data['threshold_value'] = null;
        }

        if (!empty($data['target_device'])) {
            $device = \App\Models\Device::firstOrCreate(['name' => $data['target_device']]);
            $data['target_device_id'] = $device->id;
        } else {
            $data['target_device_id'] = null;
        }
        unset($data['target_device']);

        $rule = $this->alertService->createRule($data);

        return $this->success(new AlertResource($rule), 'Alert rule created.', 201);
    }

    public function update(UpdateAlertRuleRequest $request, int $id)
    {
        
        $rule = AlertRule::find($id);

        if (!$rule) {
            return $this->error('Alert rule not found.', 404);
        }

        $data             = $request->validated();
        $data['is_active'] = $request->boolean('is_active', $rule->is_active);

        if (in_array($data['condition'], ['is_down', 'is_up'])) {
            $data['threshold_value'] = null;
        }

        if (array_key_exists('target_device', $data)) {
            if (!empty($data['target_device'])) {
                $device = \App\Models\Device::firstOrCreate(['name' => $data['target_device']]);
                $data['target_device_id'] = $device->id;
            } else {
                $data['target_device_id'] = null;
            }
            unset($data['target_device']);
        }

        $rule = $this->alertService->updateRule($rule, $data);

        return $this->success(new AlertResource($rule->fresh()), 'Alert rule updated.');
    }

    public function destroy(int $id)
    {
        if (!auth()->user()->can('manage alerts')) {
            return $this->error('Forbidden.', 403);
        }

        $rule = AlertRule::find($id);

        if (!$rule) {
            return $this->error('Alert rule not found.', 404);
        }

        $this->alertService->deleteRule($rule);

        return $this->success(null, 'Alert rule deleted.');
    }
}

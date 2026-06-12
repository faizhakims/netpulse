<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AlertResource;
use App\Http\Requests\StoreAlertRuleRequest;
use App\Http\Requests\UpdateAlertRuleRequest;
use App\Models\AlertRule;
use App\Services\AlertService;
use Illuminate\Http\Request;

/**
 * GET    /api/alerts
 * GET    /api/alerts/{id}
 * POST   /api/alerts
 * PUT    /api/alerts/{id}
 * DELETE /api/alerts/{id}
 */
class AlertController extends BaseApiController
{
    public function __construct(private AlertService $alertService) {}

    /**
     * GET /api/alerts
     * Supports: ?search=, ?status=active|inactive, ?severity=, ?sort=, ?page=
     */
    public function index(Request $request)
    {
        if (!auth()->user()->can('view alerts')) {
            return $this->error('Forbidden.', 403);
        }

        $query = AlertRule::query();

        if ($search = $request->query('search')) {
            $query->where(fn($q) =>
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('target_device', 'like', "%{$search}%")
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

    /**
     * GET /api/alerts/{id}
     */
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

    /**
     * POST /api/alerts
     */
    public function store(StoreAlertRuleRequest $request)
    {
        // authorize() in StoreAlertRuleRequest handles 'manage alerts'
        $data             = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        if (in_array($data['condition'], ['is_down', 'is_up'])) {
            $data['threshold_value'] = null;
        }

        $rule = $this->alertService->createRule($data);

        return $this->success(new AlertResource($rule), 'Alert rule created.', 201);
    }

    /**
     * PUT /api/alerts/{id}
     */
    public function update(UpdateAlertRuleRequest $request, int $id)
    {
        // authorize() in UpdateAlertRuleRequest handles 'manage alerts'
        $rule = AlertRule::find($id);

        if (!$rule) {
            return $this->error('Alert rule not found.', 404);
        }

        $data             = $request->validated();
        $data['is_active'] = $request->boolean('is_active', $rule->is_active);

        if (in_array($data['condition'], ['is_down', 'is_up'])) {
            $data['threshold_value'] = null;
        }

        $rule = $this->alertService->updateRule($rule, $data);

        return $this->success(new AlertResource($rule->fresh()), 'Alert rule updated.');
    }

    /**
     * DELETE /api/alerts/{id}
     */
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

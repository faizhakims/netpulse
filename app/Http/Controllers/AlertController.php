<?php

namespace App\Http\Controllers;

use App\Models\AlertRule;
use App\Models\AlertHistory;
use App\Models\AlertChannel;
use App\Http\Requests\StoreAlertRuleRequest;
use App\Http\Requests\UpdateAlertRuleRequest;
use App\Services\AlertService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function __construct(
        private AlertService $alertService,
        private NotificationService $notificationService
    ) {}

    public function index()
    {
        $telegram       = AlertChannel::where('type', 'telegram')->first();
        $emailCfg       = AlertChannel::where('type', 'email')->first();
        $thresholdRules = AlertRule::orderBy('sort_order')->orderByRaw("FIELD(severity,'critical','warning','info')")->get();
        $activeRules    = AlertRule::where('is_active', true)->count();
        $sentLast24h    = AlertHistory::where('sent_at', '>=', now()->subDay())->count();
        $failedAlerts   = AlertHistory::where('status', 'failed')->where('sent_at', '>=', now()->subDay())->count();
        $sentCount      = AlertHistory::where('status', 'sent')->where('sent_at', '>=', now()->subDay())->count();

        $successRate  = $sentLast24h > 0 ? number_format(($sentCount / $sentLast24h) * 100, 1) : '100.0';
        $alertHistory = AlertHistory::with('rule')->orderBy('sent_at', 'desc')->limit(10)->get();
        $allHistory   = AlertHistory::with('rule')->orderBy('sent_at', 'desc')->get();

        return view('alert', compact(
            'telegram', 'emailCfg', 'thresholdRules', 'activeRules',
            'sentLast24h', 'failedAlerts', 'successRate', 'alertHistory', 'allHistory'
        ));
    }

    public function saveChannel(Request $request)
    {
        abort_unless(auth()->user()->can('manage alerts'), 403, 'Access denied.');

        $validated = $request->validate([
            'type'                => 'required|in:telegram,email',
            'is_active'           => 'boolean',
            'config.token'        => 'required_if:type,telegram|nullable|string|max:200',
            'config.chat_id'      => 'required_if:type,telegram|nullable|string|max:100',
            'config.host'         => 'required_if:type,email|nullable|string|max:255',
            'config.port'         => 'required_if:type,email|nullable|integer|between:1,65535',
            'config.username'     => 'required_if:type,email|nullable|email|max:255',
            'config.password'     => 'nullable|string|max:500',
            'config.from_address' => 'nullable|email|max:255',
            'config.to_address'   => 'nullable|email|max:255',
        ]);

        $this->alertService->saveChannel(
            $validated['type'],
            $validated['config'] ?? [],
            (bool) ($validated['is_active'] ?? false)
        );

        return response()->json(['ok' => true, 'message' => ucfirst($validated['type']) . ' settings saved.']);
    }

    public function testChannel(Request $request)
    {
        abort_unless(auth()->user()->can('manage alerts'), 403, 'Access denied.');

        $validated = $request->validate([
            'type'                => 'required|in:telegram,email',
            'config.token'        => 'required_if:type,telegram|nullable|string|max:200',
            'config.chat_id'      => 'required_if:type,telegram|nullable|string|max:100',
            'config.host'         => 'required_if:type,email|nullable|string|max:255',
            'config.port'         => 'required_if:type,email|nullable|integer|between:1,65535',
            'config.username'     => 'required_if:type,email|nullable|email|max:255',
            'config.password'     => 'nullable|string|max:500',
            'config.from_address' => 'nullable|email|max:255',
            'config.to_address'   => 'nullable|email|max:255',
        ]);

        try {
            $message = $this->notificationService->testChannel($validated['type'], $validated['config'] ?? []);
            return response()->json(['ok' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }


    public function storeRule(StoreAlertRuleRequest $request)
    {
        $data             = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        if (in_array($data['condition'], ['is_down', 'is_up'])) {
            $data['threshold_value'] = null;
        }

        $rule = $this->alertService->createRule($data);

        return response()->json(['ok' => true, 'message' => 'Rule created successfully.', 'rule' => $rule]);
    }

    public function updateRule(UpdateAlertRuleRequest $request, $id)
    {
        $rule             = AlertRule::findOrFail($id);
        $data             = $request->validated();
        $data['is_active'] = $request->boolean('is_active', $rule->is_active);

        if (in_array($data['condition'], ['is_down', 'is_up'])) {
            $data['threshold_value'] = null;
        }

        $rule = $this->alertService->updateRule($rule, $data);

        return response()->json(['ok' => true, 'message' => 'Rule updated successfully.', 'rule' => $rule->fresh()]);
    }

    public function toggleRule($id)
    {
        abort_unless(auth()->user()->can('manage alerts'), 403, 'Access denied.');

        $rule = AlertRule::findOrFail($id);
        $rule = $this->alertService->toggleRule($rule);

        return response()->json(['ok' => true, 'is_active' => $rule->is_active]);
    }

    public function deleteRule($id)
    {
        abort_unless(auth()->user()->can('manage alerts'), 403, 'Access denied.');

        $rule = AlertRule::findOrFail($id);
        $this->alertService->deleteRule($rule);

        return response()->json(['ok' => true, 'message' => 'Rule deleted.']);
    }

    public function duplicateRule($id)
    {
        abort_unless(auth()->user()->can('manage alerts'), 403, 'Access denied.');

        $original = AlertRule::findOrFail($id);
        $copy     = $this->alertService->duplicateRule($original);

        return response()->json(['ok' => true, 'message' => 'Rule duplicated.', 'rule' => $copy]);
    }
}

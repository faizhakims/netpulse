<?php

namespace App\Http\Controllers;

use App\Models\AlertRule;
use App\Models\AlertHistory;
use App\Models\AlertChannel;
use App\Services\AlertService;
use App\Services\AlertEngineService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function __construct(
        private AlertService $alertService,
        private AlertEngineService $alertEngineService,
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
        
        $successRate    = $sentLast24h > 0 ? number_format(($sentCount / $sentLast24h) * 100, 1) : '100.0';
        $alertHistory   = AlertHistory::with('rule')->orderBy('sent_at', 'desc')->limit(10)->get();
        $allHistory     = AlertHistory::with('rule')->orderBy('sent_at', 'desc')->get();
        
        return view('alert', compact(
            'telegram', 'emailCfg', 'thresholdRules', 'activeRules', 
            'sentLast24h', 'failedAlerts', 'successRate', 'alertHistory', 'allHistory'
        ));
    }

    public function saveChannel(Request $request)
    {
        abort_unless(auth()->user()->can('manage alerts'), 403, 'Access denied.');
        
        $type     = $request->input('type');
        $isActive = $request->boolean('is_active');
        $config   = $request->input('config', []);

        $this->alertService->saveChannel($type, $config, $isActive);

        return response()->json(['ok' => true, 'message' => ucfirst($type) . ' settings saved.']);
    }

    public function testChannel(Request $request)
    {
        abort_unless(auth()->user()->can('manage alerts'), 403, 'Access denied.');
        
        $type         = $request->input('type');
        $inlineConfig = $request->input('config', []);

        try {
            $message = $this->notificationService->testChannel($type, $inlineConfig);
            return response()->json(['ok' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    public function storeRule(Request $request)
    {
        abort_unless(auth()->user()->can('manage alerts'), 403, 'Access denied.');
        
        $data = $request->validate($this->ruleValidationRules());

        try {
            $this->alertEngineService->validateConditionForMetric($data['metric_type'], $data['condition']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        if (in_array($data['condition'], ['is_down', 'is_up'])) {
            $data['threshold_value'] = null;
        } elseif (empty($data['threshold_value']) && $data['threshold_value'] !== '0' && $data['threshold_value'] !== 0) {
            return response()->json(['ok' => false, 'message' => 'Threshold Value wajib diisi untuk kondisi ini.'], 422);
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $rule = $this->alertService->createRule($data);

        return response()->json(['ok' => true, 'message' => 'Rule created successfully.', 'rule' => $rule]);
    }

    public function updateRule(Request $request, $id)
    {
        abort_unless(auth()->user()->can('manage alerts'), 403, 'Access denied.');
        
        $rule = AlertRule::findOrFail($id);
        $data = $request->validate($this->ruleValidationRules());

        try {
            $this->alertEngineService->validateConditionForMetric($data['metric_type'], $data['condition']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        if (in_array($data['condition'], ['is_down', 'is_up'])) {
            $data['threshold_value'] = null;
        } elseif (empty($data['threshold_value']) && $data['threshold_value'] !== '0' && $data['threshold_value'] !== 0) {
            return response()->json(['ok' => false, 'message' => 'Threshold Value wajib diisi untuk kondisi ini.'], 422);
        }

        $data['is_active'] = $request->boolean('is_active', $rule->is_active);
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
        $copy = $this->alertService->duplicateRule($original);

        return response()->json(['ok' => true, 'message' => 'Rule duplicated.', 'rule' => $copy]);
    }

    private function ruleValidationRules(): array
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
}

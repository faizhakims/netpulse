<?php

namespace App\Services;

use App\Models\AlertRule;
use App\Models\AlertChannel;

class AlertService
{
    public function saveChannel(string $type, array $config, bool $isActive)
    {
        $channel = AlertChannel::firstOrNew(['type' => $type]);
        $channel->is_active = $isActive;
        $channel->config    = $config;
        $channel->save();
        
        return $channel;
    }

    public function createRule(array $data)
    {
        $data['description'] = $data['title'];
        $data['sort_order']  = AlertRule::max('sort_order') + 1;
        return AlertRule::create($data);
    }

    public function updateRule(AlertRule $rule, array $data)
    {
        $data['description'] = $data['title'];
        $rule->update($data);
        return $rule;
    }

    public function toggleRule(AlertRule $rule)
    {
        $rule->is_active = !$rule->is_active;
        $rule->save();
        return $rule;
    }

    public function duplicateRule(AlertRule $original)
    {
        $copy = $original->replicate();
        $copy->title      = $original->title . ' (Copy)';
        $copy->sort_order = AlertRule::max('sort_order') + 1;
        $copy->save();
        return $copy;
    }

    public function deleteRule(AlertRule $rule)
    {
        $rule->delete();
    }
}

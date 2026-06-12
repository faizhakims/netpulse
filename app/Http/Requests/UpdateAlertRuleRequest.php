<?php

namespace App\Http\Requests;

/**
 * Form Request for updating an existing Alert Rule.
 *
 * Inherits all validation rules, cross-field checks, and JSON error
 * formatting from AlertRuleRequest.
 */
class UpdateAlertRuleRequest extends AlertRuleRequest
{
    // All logic is inherited from AlertRuleRequest.
    // Add update-specific overrides here if needed in the future.
}

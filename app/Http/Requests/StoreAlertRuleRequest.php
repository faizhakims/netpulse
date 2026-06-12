<?php

namespace App\Http\Requests;

/**
 * Form Request for creating a new Alert Rule.
 *
 * Inherits all validation rules, cross-field checks, and JSON error
 * formatting from AlertRuleRequest.
 */
class StoreAlertRuleRequest extends AlertRuleRequest
{
    // All logic is inherited from AlertRuleRequest.
    // Add store-specific overrides here if needed in the future.
}

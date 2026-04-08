<?php

namespace App\Http\Requests;

class StoreMetaAutoRuleRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'channel' => 'nullable|string|max:50',
            'monitoring_object' => 'required|string|max:50',
            'template_id' => 'required|string|max:100',
            'status' => 'nullable|integer|in:0,1',
            'currency' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:20',
            'effective_period' => 'nullable|string|max:20',
            'check_frequency' => 'nullable|string|max:20',
            'execution_interval' => 'nullable|string|max:10',
            'execute_in_order' => 'nullable|integer|in:0,1',
            'audit_status' => 'nullable|integer',
            'audit_reason' => 'nullable|string|max:500',
            'filters' => 'nullable|array',
            'metric_conditions' => 'nullable|array',
            'anti_fraud_config' => 'nullable|array',
            'actions' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '规则名称不能为空',
            'monitoring_object.required' => '监控对象不能为空',
            'template_id.required' => '模板ID不能为空',
        ];
    }
}

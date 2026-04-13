<?php

namespace App\Http\Requests;

class UpdateMetaOrganizationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => 'nullable|integer|min:0',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100',
            'sort' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '组织名称不能为空',
            'name.max' => '组织名称最多255个字符',
        ];
    }
}

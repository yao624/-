<?php

namespace App\Http\Requests;

class StoreMetaUserMenuRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'menu_ids' => 'nullable|array',
            'menu_ids.*' => 'integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'menu_ids.required' => '菜单ID列表不能为空',
            'menu_ids.array' => '菜单ID列表格式错误',
        ];
    }
}

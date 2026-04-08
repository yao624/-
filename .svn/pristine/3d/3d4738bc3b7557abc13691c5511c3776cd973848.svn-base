<?php

namespace App\Http\Requests;

class StoreMetaTagRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'remark' => 'nullable|string|max:500',
            'folder_id' => 'nullable|integer|min:0',
            'tag_object' => 'nullable|string|max:50',
            'tag_object_level1' => 'nullable|string|max:50',
            'sort' => 'nullable|integer|min:0',
            'options' => 'nullable|array',
            'options.*.name' => 'required|string|max:100',
            'options.*.description' => 'nullable|string|max:500',
            'options.*.url' => 'nullable|string|max:500',
            'options.*.remark1' => 'nullable|string|max:255',
            'options.*.remark2' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '标签名称不能为空',
            'name.max' => '标签名称最多100个字符',
            'folder_id.required' => '文件夹ID不能为空',
            'folder_id.min' => '文件夹ID无效',
        ];
    }
}

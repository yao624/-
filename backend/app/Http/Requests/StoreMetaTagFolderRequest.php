<?php

namespace App\Http\Requests;

class StoreMetaTagFolderRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|integer|min:0',
            'sort' => 'nullable|integer|min:0',
            'is_del' => 'nullable|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '文件夹名称不能为空',
            'name.max' => '文件夹名称最多100个字符',
            'parent_id.min' => '父级ID无效',
            'sort.min' => '排序值无效',
            'is_del.in' => '删除状态值无效',
        ];
    }
}

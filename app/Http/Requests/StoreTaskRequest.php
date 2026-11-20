<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id'  => ['required', 'integer', 'exists:projects,id'],
            'parent_id'   => ['nullable', 'integer', 'exists:tasks,id'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status'      => ['nullable', 'in:pending,in_progress,completed'],
            'priority'    => ['nullable', 'in:low,medium,high'],
            'due_date'    => ['nullable', 'date'],
            'tag_ids'     => ['nullable', 'array'],
            'tag_ids.*'   => ['integer', 'exists:tags,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.exists' => 'المشروع المحدد غير موجود.',
            'title.required'    => 'عنوان المهمة مطلوب.',
            'tag_ids.*.exists'  => 'أحد التاجات غير موجود.',
        ];
    }
}
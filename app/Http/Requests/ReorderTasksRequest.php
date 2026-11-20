<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderTasksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tasks'              => ['required', 'array', 'min:1'],
            'tasks.*.id'         => ['required', 'integer', 'exists:tasks,id'],
            'tasks.*.status'     => ['sometimes', 'in:pending,in_progress,completed'],
            'tasks.*.position'   => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'tasks.required'          => 'قائمة المهام مطلوبة.',
            'tasks.*.id.exists'       => 'إحدى المهام غير موجودة.',
            'tasks.*.position.min'    => 'الترتيب يجب أن يكون صفراً أو أكبر.',
            'tasks.*.status.in'       => 'قيمة الحالة غير صحيحة.',
        ];
    }
}
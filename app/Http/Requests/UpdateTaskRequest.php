<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id'  => ['sometimes', 'integer', 'exists:projects,id'],
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status'      => ['sometimes', 'in:pending,in_progress,completed'],
            'priority'    => ['sometimes', 'in:low,medium,high'],
            'due_date'    => ['nullable', 'date'],
            'position'    => ['sometimes', 'integer', 'min:0'],
            'tag_ids'     => ['nullable', 'array'],
            'tag_ids.*'   => ['integer', 'exists:tags,id'],
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:5120', // 5MB = 5120 KB
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,zip',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'الملف مطلوب.',
            'file.max'      => 'حجم الملف يجب ألا يتجاوز 5 ميجابايت.',
            'file.mimes'    => 'نوع الملف غير مدعوم.',
        ];
    }
}
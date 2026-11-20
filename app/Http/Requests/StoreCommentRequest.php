<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'لا يمكن إرسال تعليق فارغ.',
            'body.max'      => 'التعليق طويل جداً (الحد الأقصى 2000 حرف).',
        ];
    }
}
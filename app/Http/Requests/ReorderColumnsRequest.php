<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderColumnsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'columns' => 'required|array',
            'columns.*.id' => [
                'required',
                Rule::exists('columns', 'id')->where('user_id', $this->user()?->id),
            ],
            'columns.*.position' => 'required|integer',
        ];
    }
}

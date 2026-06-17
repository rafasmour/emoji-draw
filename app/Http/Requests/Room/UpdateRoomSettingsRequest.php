<?php

namespace App\Http\Requests\Room;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cap' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'public' => ['sometimes', 'boolean'],
            'timeLimit' => ['sometimes', 'integer', Rule::in([30, 60, 120])],
            'difficulty' => ['sometimes', 'string', Rule::in(['easy', 'medium', 'hard'])],
            'categories' => ['sometimes', 'array'],
            'rounds' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ];
    }
}

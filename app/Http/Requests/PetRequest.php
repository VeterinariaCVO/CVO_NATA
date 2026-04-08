<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class PetRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $user     = auth()->user();
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');
        $prefix   = $isUpdate ? 'sometimes' : 'required';

        return [
            'name'          => "{$prefix}|string|max:255",
            'species'       => "{$prefix}|string|max:100",
            'breed'         => 'nullable|string|max:100',
            'color'         => 'nullable|string|max:100',
            'special_marks' => 'nullable|string|max:255',
            'weight'        => 'nullable|numeric|min:0',
            'sex'           => "{$prefix}|in:male,female",
            'age'           => 'nullable|integer|min:0|max:360',
            'photo'         => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'active'        => 'nullable|boolean',
            'owner_id'      => in_array($user?->role_id, [1, 2, 4])
                                ? "{$prefix}|integer|exists:users,id"
                                : 'nullable',
        ];
    }
}

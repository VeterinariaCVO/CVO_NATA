<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $userId = $this->route('id');
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'name'          => ($isUpdate ? 'sometimes' : 'required') . '|string|max:255',
            'email'         => ($isUpdate ? 'sometimes' : 'required') . '|email|unique:users,email,' . $userId,
            'password'      => $isUpdate ? 'nullable|string|min:6' : 'required|string|min:6',
            'role_id'       => ($isUpdate ? 'sometimes' : 'required') . '|in:1,2,3,4',
            'phone'         => 'nullable|string|max:20',
            'address'       => 'nullable|string|max:255',
            'active'        => 'nullable|boolean',
            'gender'        => 'nullable|in:masculino,femenino,otro',
            'birth_date'    => 'nullable|date|before:-18 years',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }
}

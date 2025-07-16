<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse;

class UpdateProfileRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'full_name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                'unique:users,email,' . $userId
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.min' => 'نام و نام خانوادگی باید حداقل 2 کاراکتر باشد.',
            'full_name.max' => 'نام و نام خانوادگی نباید بیش از 100 کاراکتر باشد.',
            'email.email' => 'فرمت ایمیل نامعتبر است.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->validationErrorResponse($validator->errors())
        );
    }
}

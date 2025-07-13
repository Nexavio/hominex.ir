<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse;

class RegisterRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
                'unique:users,phone'
            ],
            'full_name' => [
                'required',
                'string',
                'min:2',
                'max:100'
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:users,email'
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:255'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'شماره تماس الزامی است.',
            'phone.regex' => 'فرمت شماره تماس نامعتبر است. (مثال: 09123456789)',
            'phone.unique' => 'این شماره تماس قبلاً ثبت شده است.',
            'full_name.required' => 'نام و نام خانوادگی الزامی است.',
            'full_name.min' => 'نام و نام خانوادگی باید حداقل 2 کاراکتر باشد.',
            'full_name.max' => 'نام و نام خانوادگی نباید بیش از 100 کاراکتر باشد.',
            'email.email' => 'فرمت ایمیل نامعتبر است.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'password.required' => 'رمز عبور الزامی است.',
            'password.min' => 'رمز عبور باید حداقل 8 کاراکتر باشد.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->validationErrorResponse($validator->errors())
        );
    }
}


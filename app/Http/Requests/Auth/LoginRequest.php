<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse;

// LoginRequest
class LoginRequest extends FormRequest
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
                'regex:/^09[0-9]{9}$/'
            ],
            'password' => [
                'nullable',
                'string',
                'min:8'
            ],
            'login_type' => [
                'required',
                'string',
                'in:password,otp'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'شماره تماس الزامی است.',
            'phone.regex' => 'فرمت شماره تماس نامعتبر است.',
            'password.min' => 'رمز عبور باید حداقل 8 کاراکتر باشد.',
            'login_type.required' => 'نوع ورود الزامی است.',
            'login_type.in' => 'نوع ورود باید password یا otp باشد.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->validationErrorResponse($validator->errors())
        );
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->login_type === 'password' && empty($this->password)) {
                $validator->errors()->add('password', 'رمز عبور برای ورود با پسورد الزامی است.');
            }
        });
    }
}

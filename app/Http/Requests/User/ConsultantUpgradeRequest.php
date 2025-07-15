<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse;

class ConsultantUpgradeRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return auth()->check() &&
               auth()->user()->canRequestConsultantUpgrade();
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'min:2', 'max:100'],
            'bio' => ['required', 'string', 'min:20', 'max:1000'],
            'contact_phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'contact_whatsapp' => ['nullable', 'string', 'regex:/^09[0-9]{9}$/'],
            'contact_telegram' => ['nullable', 'string', 'max:50', 'regex:/^@?[a-zA-Z0-9_]+$/'],
            'contact_instagram' => ['nullable', 'string', 'max:50', 'regex:/^@?[a-zA-Z0-9_.]+$/'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'نام شرکت الزامی است.',
            'company_name.min' => 'نام شرکت باید حداقل 2 کاراکتر باشد.',
            'bio.required' => 'معرفی و بیوگرافی الزامی است.',
            'bio.min' => 'بیوگرافی باید حداقل 20 کاراکتر باشد.',
            'contact_phone.required' => 'شماره تماس الزامی است.',
            'contact_phone.regex' => 'فرمت شماره تماس نامعتبر است.',
            'contact_whatsapp.regex' => 'فرمت شماره واتساپ نامعتبر است.',
            'contact_telegram.regex' => 'فرمت تلگرام نامعتبر است.',
            'contact_instagram.regex' => 'فرمت اینستاگرام نامعتبر است.',
            'profile_image.image' => 'فایل باید تصویر باشد.',
            'profile_image.max' => 'حجم تصویر نباید بیش از 2 مگابایت باشد.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->validationErrorResponse($validator->errors())
        );
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            $this->errorResponse(
                message: 'شما مجاز به ارسال درخواست ارتقا نیستید.',
                statusCode: 403
            )
        );
    }
}

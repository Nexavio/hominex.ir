<?php
// VerifyOtpRequest
namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse;

class VerifyOtpRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                'size:6',
                'regex:/^[0-9]{6}$/'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'شماره تماس الزامی است.',
            'phone.regex' => 'فرمت شماره تماس نامعتبر است.',
            'code.required' => 'کد تأیید الزامی است.',
            'code.size' => 'کد تأیید باید 6 رقم باشد.',
            'code.regex' => 'کد تأیید باید شامل اعداد باشد.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->validationErrorResponse($validator->errors())
        );
    }
}

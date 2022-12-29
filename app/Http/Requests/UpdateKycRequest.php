<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKycRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'fname' => 'required|string|min:2',
            'lname' => 'required|string|min:2',
            'melli_code' => 'required|ir_national_code',
            'birthdate' => 'required|shamsi_date',
            'father_name' => 'required|string',
            'province' => 'required|string',
            'city' => 'required|string',
            'address' => 'required',
            'postal_code' => 'required|ir_postal_code',
            'number' => 'required|integer',
            'site' => 'nullable|url',
            'melli_card' => 'nullable|image|max:1024',
            'prove_picture' => 'nullable|image|max:1024',
            'resume' => 'nullable|image|max:1024',
        ];
    }

    public function messages()
    {
        return [
            'shaba.required' => 'شماره شبا را وارد کنید',
            'shaba.ir_sheba' => 'شماره شباصحیح نمی باشد',
            'bank.required' => 'نام بانک را وارد کنید',
            'melli_card.required' => 'تصویر کارت ملی را بارگذاری کنید',
            'melli_card.file' => 'تصویر کارت باید فایل باشد',
            'melli_card.mime' => 'تصویر کارت ملی باید با یکی از فرمتهای jpb یا png باشد',
            'prove_picture.required' => 'تصویر احراز مستند را بارگذاری کنید',
            'prove_picture.file' => 'تصویر احراز مستند باید فایل باشد',
            'resume.mime' => 'تصویر احراز مستند باید با یکی از فرمتهای jpb یا png باشد',
            'resume.file' => 'تصویر رزومه باید فایل باشد',
            'resume.mime' => 'تصویر رزمه باید با یکی از فرمتهای jpb یا png باشد',
            'fname.required' => 'نام خود را وارد کنید',
            'fname.string' => 'نام وارد شده صحیح نیست',
            'lname.required' => 'نام خانوادگی خود را وارد کنید',
            'lname.string' => 'نام  خانوادگی وارد شده صحیح نیست',
            'father_name.required' => 'نام پدر را وارد کنید',
            'father_name.string' => 'نام پدر وارد شده صحیح نیست',
            'melli_code.required' => 'کد ملی را وارد کنید',
            'melli_code.ir_national_code' => 'کد ملی صحیح نیست',
            'province.required' => 'نام استان خود را وارد کنید',
            'province.string' => 'نام استان صحیح نیست',
            'city.required' => 'نام شهر خود را وارد کنید',
            'city.string' => 'نام شهر صحیح نیست',
            'number.required' => 'شماره پلاک منزل را وارد کنید',
            'number.integer' => 'شماره پلاک صحیح نیست',
            'postal_code.required' => 'کد پستی را وارد کنید',
            'postal_code.ir_postal_code' => 'کد پستی صحیح نیست',
            'site.url' => 'آدرس سایت وارد شده صحیح نمی باشد',
            'address.required' => 'آدرس را وارد کنید',
        ];
    }
}

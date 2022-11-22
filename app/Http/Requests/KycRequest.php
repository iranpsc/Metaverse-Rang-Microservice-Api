<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KycRequest extends FormRequest
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
     *
     *
     */


    public function rules()
    {
        return [
            'shaba' => 'required|ir_sheba',
            'bank' => 'required|string',
            'melli_card' => 'required|file|mimes:png,jpg',
            'prove_picture' => 'required|file|mimes:png,jpg',
            'resume' => 'nullable|file|mimes:png,jpg',
            'fname' => 'required|string|min:2',
            'lname' => 'required|string|min:2',
            'father_name' => 'required|string',
            'birthdate' => 'required|shamsi_date',
            'melli_code' => 'required|ir_national_code',
            'province' => 'required|string',
            'city' => 'required|string',
            'number' => 'required|integer',
            'postal_code' => 'required|ir_postal_code',
            'address' => 'required',
            'site' => 'nullable|url'
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

    /**
     * @return mixed
     */
    public function getFirstName(): mixed
    {
        return $this->get('fname');
    }

    /**
     * @return mixed
     */
    public function getLastName(): mixed
    {
        return $this->get('lname');
    }

    /**
     * @return mixed
     */
    public function getFatherName(): mixed
    {
        return $this->get('father_name');
    }

    /**
     * @return mixed
     */
    public function getMeliCode(): mixed
    {
        return $this->get('melli_code');
    }

    /**
     * @return mixed
     */
    public function getProvience(): mixed
    {
        return $this->get('province');
    }

    /**
     * @return mixed
     */
    public function getCity(): mixed
    {
        return $this->get('city');
    }

    /**
     * @return mixed
     */
    public function getNumber(): mixed
    {
        return $this->get('number');
    }

    /**
     * @return mixed
     */
    public function getPostalCode(): mixed
    {
        return $this->get('postal_code');
    }

    /**
     * @return mixed
     */
    public function getAddress(): mixed
    {
        return $this->get('address');
    }

    /**
     * @return mixed
     */
    public function getSite(): mixed
    {
        return $this->get('site');
    }

    /**
     * @return mixed
     */
    public function getShaba(): mixed
    {
        return $this->get('shaba');
    }

    /**
     * @return mixed
     */
    public function getBank(): mixed
    {
        return $this->get('bank');
    }

    public function getBirthdate()
    {
        return $this->get('birthdate');
    }
}

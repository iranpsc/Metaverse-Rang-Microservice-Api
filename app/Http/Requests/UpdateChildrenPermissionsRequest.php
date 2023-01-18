<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChildrenPermissionsRequest extends FormRequest
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
            'permission' => 'required|string|in:BFR,SF,W,JU,DM,PIUP,PITC,PIC,ESOO,COTB',
            'status'     => 'required_with:permission|boolean',
        ];
    }
}

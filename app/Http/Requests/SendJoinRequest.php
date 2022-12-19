<?php

namespace App\Http\Requests;

use App\Constants\FamilyMembersType;
use ChildrenPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendJoinRequest extends FormRequest
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
        $data = [
            'to_user' => 'required|string',
            'relationship' => ['required', Rule::in(FamilyMembersType::toArray())],
            'permissions' => 'required_if:relationship,offspring|array|',
            'permissions.*' => [
                'distinct',
                Rule::in(ChildrenPermissions::toArray()),
                'required_if:relationship,offspring'
            ],
        ];
        if ($this->get('relationship') == FamilyMembersType::FATHER) {
            if ($this->has('no_father')) {
                return array_merge($data, [
                    'death_license' => 'required|file|mimes:jpg,png,pdf|max:1024',
                    'mother_code' => 'required'
                ]);
            }
            return $data;
        }
        return $data;
    }

    public function getRelationship()
    {
        return $this->get('relationship');
    }

    public function getToUser()
    {
        return $this->get('to_user');
    }

    public function getNoFather()
    {
        return $this->get('no_father');
    }

    public function getDeathLicense()
    {
        return $this->death_license->getClientOriginalName();
    }

    public function getMotherCode()
    {
        return $this->get('mother_code');
    }
}

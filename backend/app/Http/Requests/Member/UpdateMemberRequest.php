<?php

namespace App\Http\Requests\Member;

use Illuminate\Validation\Rule;

class UpdateMemberRequest extends StoreMemberRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['last_name'] = ['sometimes', 'required', 'string', 'max:100'];
        $rules['first_name'] = ['sometimes', 'required', 'string', 'max:100'];
        $rules['phone'] = [
            'sometimes', 'required', 'string', 'max:20',
            Rule::unique('members', 'phone')->ignore($this->route('member'))->whereNull('deleted_at'),
        ];

        // Non modifiables après création
        unset($rules['joined_at']);

        return $rules;
    }
}

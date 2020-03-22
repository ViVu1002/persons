<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class UpdatePassword extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'cu_password' => 'required',
            'password' => 'required|min:6|max:15',
            're_password' => 'required',
        ];
    }

    public function withValidator($validator){
        $validator->after(function ($validator){
           if (!Hash::check($this->cu_password, $this->user()->password)){
               $validator->errors()->add('cu_password','You cu_password is incorrect');
           }

           if (empty($this->password == $this->re_password)){
               $validator->errors()->add('re_password','You re_password is incorrect');
           }
        });
        return;
    }
}

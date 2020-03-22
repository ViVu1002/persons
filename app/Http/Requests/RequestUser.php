<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestUser extends FormRequest
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
        $validation = [
            'email' => 'required|email|unique:users',
            'admin' => 'required',
            'password' => 'required|min:5',
            're-password' => 'required'
        ];
        if (!empty($this->id)) {
            $validation['email'] = 'required|email|unique:users,id,' . $this->id;
        }
        return $validation;
    }

    public function messages()
    {
        return [
            'email.required' => 'Trường này không được bỏ trống',
            'password.required' => 'Trường này không được bỏ trống',
            'password.min' => 'Trường này có ít nhất 5 kí tự',
            're-password' => 'Trường này không được bỏ trống',
        ];
    }
}

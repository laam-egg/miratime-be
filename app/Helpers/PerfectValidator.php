<?php

namespace App\Helpers;

use App\Exceptions\HttpException;
use Illuminate\Http\Request;

function _merge(array &$a, array $b) {
    $a = array_merge($a, $b);
    return $a;
}

class PerfectValidator {
    public static function validate(Request $request, array $fields) {
        $rules = [];
        $messages = [];

        foreach ($fields as $fieldName => $fieldGlobalName) {
            switch ($fieldGlobalName) {
                case 'User.email':
                    $rules[$fieldName] = ['required', 'string', 'email', 'unique:users,email'];
                    _merge($messages, [
                        "$fieldName.required" => 'EMAIL_REQUIRED',
                        "$fieldName.string" => 'EMAIL_INVALID',
                        "$fieldName.email" => 'EMAIL_INVALID',
                        "$fieldName.unique" => 'EMAIL_TAKEN'
                    ]);
                    break;
                
                case 'User.password':
                    $rules[$fieldName] = ['required', 'string', 'min:8'];
                    _merge($messages, [
                        "$fieldName.required" => 'PASSWORD_REQUIRED',
                        "$fieldName.string" => 'PASSWORD_INVALID',
                        "$fieldName.min" => 'PASSWORD_MIN_8'
                    ]);
                    break;
                
                case 'User.name':
                    $rules[$fieldName] = ['required', 'string', 'max:250'];
                    _merge($messages, [
                        "$fieldName.required" => 'NAME_REQUIRED',
                        "$fieldName.string" => 'NAME_INVALID',
                        "$fieldName.max" => 'NAME_MAX_250',
                    ]);
                    break;
                
                default:
                    throw new HttpException(500, "Invalid field global name: $fieldGlobalName");
            }
        }

        $fieldData = $request->validate($rules, $messages);
        return $fieldData;
    }
}

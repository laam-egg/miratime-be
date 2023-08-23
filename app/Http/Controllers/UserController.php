<?php

namespace App\Http\Controllers;

use App\Helpers\PerfectValidator;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function signup(Request $request) {
        $userInfo = PerfectValidator::validate($request, [
            'email' => 'User.email',
            'password' => 'User.password',
            'name' => 'User.name'
        ]);
        $user = User::create($userInfo);
        return response()->json($user, 200);
    }

    public function index(Request $request) {
        return response()->json($request->user(), 200);
    }
}

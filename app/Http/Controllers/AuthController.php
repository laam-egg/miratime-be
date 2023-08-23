<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use App\Exceptions\HttpException;
use App\Helpers\PerfectValidator;
use stdClass;

class AuthController extends Controller
{
    private function _forgetRefreshToken(Request $request) {
        if (Cookie::get('refreshToken')) {
            Cookie::queue(Cookie::forget('refreshToken'));
        }
    }

    private function _sendRefreshToken(Request $request) {
        $user = $request->user(); // or Auth::user

        $this->_forgetRefreshToken($request);

        Cookie::queue(
            Cookie::make(
                'refreshToken',
                $user->createToken('refreshToken')->plainTextToken,
                0, // 60 * 24 * 365, // minutes to expire
                '/api/auth/refresh',
                // TODO: Set the following values based on environment (dev vs prod):
                null,  // domain
                false, // secure
                true   // httpOnly
            )
        );
    }

    private function _respondAccessToken(Request $request) {
        $user = $request->user();

        return response()->json([
            'accessToken' => $user->createToken('accessToken')->plainTextToken
        ], 200);
    }

    public function login(Request $request) {
        $credentials = PerfectValidator::validate($request, [
            'email' => 'User.email',
            'password' => 'User.password'
        ]);

        if (Auth::attempt($credentials)) {
            $this->_sendRefreshToken($request);
            return $this->_respondAccessToken($request);
        }

        throw new HttpException(401, 'CREDENTIALS_INVALID');
    }

    /**
     * Refreshes both access and refresh tokens
     */
    public function refresh(Request $request) {
        $user = $request->user();
        $user->tokens()->delete();

        $this->_sendRefreshToken($request);
        return $this->_respondAccessToken($request);
    }

    public function logout(Request $request) {
        $user = $request->user();
        $user->tokens()->delete();

        $this->_forgetRefreshToken($request);
        return response()->json(new stdClass, 200);
    }
}

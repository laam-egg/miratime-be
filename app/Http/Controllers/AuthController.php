<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use App\Exceptions\HttpException;
use App\Helpers\PerfectValidator;
use App\Helpers\RefreshTokenManager;
use App\Models\User;
use stdClass;

class AuthController extends Controller
{
    private function _issueAccessToken(User $user): string {
        return $user->createToken('accessToken')->plainTextToken;
    }

    private function _verifyRefreshToken(Request $request): User {
        $refreshToken = $request->cookie('refreshToken');
        if (!$refreshToken) throw new HttpException(401, 'UNAUTHENTICATED');

        $user = RefreshTokenManager::verify($request, $refreshToken);
        return $user;
    }

    public function login(Request $request) {
        $credentials = PerfectValidator::validate($request, [
            'email' => 'User.email',
            'password' => 'User.password'
        ]);

        if (Auth::attempt($credentials)) {
            $user = $request->user();

            $accessToken = $this->_issueAccessToken($user);

            $refreshToken = RefreshTokenManager::issueAuth($request);

            Cookie::queue(
                Cookie::forever(
                    'refreshToken',                          // name
                    $refreshToken,                           // value
                    route('auth.index'),                     // path - so that the cookie can be read by all auth routes, including auth.login, auth.refresh, and auth.logout. For more info, visit $APP_URL/api/auth.
                    null,                                    // domain
                    (config('app.env') === 'production'),    // secure
                    true,                                    // httpOnly
                )
            );
    
            return response()->json(compact('accessToken'), 200);
        }

        throw new HttpException(401, 'CREDENTIALS_UNRECOGNIZED');
    }

    /**
     * Yields the access token acquired at the last login time by the user agent 
     */
    public function refresh(Request $request) {
        $user = $this->_verifyRefreshToken($request);

        // In case the user logs in on multiple devices, the following command still keeps
        // him logged in since the refresh tokens on other devices remain, thanks to which
        // the client could obtain a new access token. 
        $user->tokens()->delete();

        return response()->json([
            'accessToken' => $this->_issueAccessToken($user)
        ], 200);
    }

    public function logout(Request $request) {
        $user = $request->user();

        $userWithRefreshToken = $this->_verifyRefreshToken($request);

        if ($user->id != $userWithRefreshToken->id) {
            throw new HttpException(401, 'UNAUTHENTICATED');
        }

        // https://laravel.com/docs/10.x/sanctum#revoking-tokens
        $user->currentAccessToken()->delete();

        Cookie::queue(
            Cookie::forget(
                'refreshToken',      // name
                route('auth.index'), // path
                null                 // domain
            )
        );

        return response()->json(new stdClass, 200);
    }

    // TODO: Log out all/all other devices
}

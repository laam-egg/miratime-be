<?php

namespace App\Helpers;

use App\Exceptions\HttpException;
use App\Models\RefreshTokenValidation;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;

class RefreshTokenManager {
    private static function _getCommonMetadata() {
        return [
            'iss' => config('app.url'),
            // TODO: aud may be left blank when third-party apps consume the API
            'aud' => config('app.frontend_url'),
        ];
    }

    private static function _getSecret() {
        return config('jwt.secret');
    }

    private static function _getEncodeAlgorithm() {
        return 'HS256';
    }

    private static function _issue(Request $request, User $user) {
        $now = time();

        $metadata = array_merge(
            self::_getCommonMetadata(),
            [
                'sub' => $user->id,
                'iat' => $now,
                'nbf' => $now,
                'jti' => RefreshTokenValidation::create(['user_id' => $user->id])->id,
            ]
        );

        $payload = array_merge($metadata, [
            'ua' => $request->header('user-agent'),
        ]);

        $refreshToken = JWT::encode($payload, self::_getSecret(), self::_getEncodeAlgorithm());

        return $refreshToken;
    }

    private static function _verify(Request $request, string $refreshToken): array {
        $now = time();

        $decoded = (array)(JWT::decode(
            $refreshToken,
            new Key(self::_getSecret(), self::_getEncodeAlgorithm())
        ));

        // Check common metadata
        foreach (self::_getCommonMetadata() as $key => $value) {
            if (!array_key_exists($key, $decoded) || $decoded[$key] !== $value) {
                goto ERROR;
            }
        }

        // Check individual data availability
        foreach(['sub', 'iat', 'nbf', 'jti', 'ua'] as $key) {
            if (!array_key_exists($key, $decoded) || !$decoded[$key]) goto ERROR;
        }

        // Detect user
        $user = User::find($decoded['sub']);
        if (!$user) goto ERROR;
        // $userAuthedViaAccessToken = $request->user();
        // if ($userAuthedViaAccessToken && $userAuthedViaAccessToken->id !== $user->id) goto ERROR;

        // Check validity
        $validation = RefreshTokenValidation::find($decoded['jti']);
        if (!$validation) goto ERROR;

        // Check user
        if ($validation->user_id != $user->id) goto ERROR;

        // Check time
        if ($decoded['iat'] !== $decoded['nbf']) goto ERROR;
        if ($decoded['nbf'] >= $now) goto ERROR;

        // Check user agent
        if ($decoded['ua'] !== $request->header('user-agent')) goto ERROR;

        SUCCESS : return compact('decoded', 'user', 'validation');
        ERROR   : throw new HttpException(401, 'UNAUTHENTICATED');
    }

    public static function issue(Request $request, User $user) {
        if (!$user) {
            throw new HttpException(500, 'User not explicitly specified.');
        }
        return self::_issue($request, $user);
    }

    public static function issueAuth(Request $request) {
        $user = $request->user();
        return self::_issue($request, $user);
    }

    public static function verify(Request $request, string $refreshToken): User {
        return self::_verify($request, $refreshToken)['user'];
    }

    public static function revoke(Request $request, string $refreshToken): void {
        self::_verify($request, $refreshToken)['validation']->delete();
    }

    public static function revokeAll(Request $request, string $refreshToken): void {
        $user_id = self::_verify($request, $refreshToken)['user']->id;
        RefreshTokenValidation::where('user_id', $user_id)->delete();
    }
}

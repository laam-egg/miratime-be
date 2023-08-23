<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (ValidationException $throwable, Request $request) {
            $json = [
                'status' => 400
            ];

            $errorMessages = [];
            foreach ($throwable->errors() as $field => $currentErrorMessageList) {
                $errorMessages = array_merge($errorMessages, $currentErrorMessageList);
            }
            switch (count($errorMessages)) {
                case 0:
                    $json['message'] = 'UNKNOWN';
                    break;
                case 1:
                    $json['message'] = $errorMessages[0];
                    break;
                default:
                    $json['message'] = 'MULTIPLE';
                    $json['messages'] = $errorMessages;
                    break;
            }

            return response()->json($json, $json['status']);
        });

        $this->renderable(function (AuthenticationException $throwable, Request $request) {
            return $this->render($request, new HttpException(401, 'UNAUTHENTICATED'));
        });

        $this->renderable(function (HttpException $throwable, Request $request) {
            return response()->json([
                'status' => $throwable->getStatus(),
                'message' => $throwable->getMessage()
            ], $throwable->getStatus());
        });
    }

    // public function render($request, Throwable $throwable) {
}

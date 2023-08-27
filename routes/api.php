<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;


Route::middleware('api')->group(function () {
    Route::name('auth.')->prefix('/auth')->group(function () {
        Route::any('/', function () {
            return response()->json([
                'status' => 200,
                'message' => 'The purpose of this route is to get its path set as the path of the refresh token cookie, so that the cookie can be accessed by all routes in this AUTH route group. (1) (2) THIS ROUTE HAS NO OTHER PURPOSE SO IT DOES NOT MEAN TO BE CONSUMED BY THE FRONTEND OR ANY THIRD-PARTY CLIENT.',
                'references' => [
                    '1' => 'In Laravel, we call route(<route_name>) to get the path of a route.',
                    '2' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies#path_attribute',
                ],
            ]);
        })->name('index');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
    });

    Route::name('user.')->prefix('/user')->group(function () {
        Route::post('/', [UserController::class, 'signup'])->name('signup');
        Route::get('/', [UserController::class, 'index'])->middleware('auth:sanctum')->name('index');
    });
});

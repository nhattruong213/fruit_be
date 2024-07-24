<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordResetController;
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

Route::group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'auth'
 
], function ($router) {
    Route::post('/login', 'Api\AuthController@login');
    Route::post('/register','Api\AuthController@register');
    Route::post('/refresh', 'Api\AuthController@refresh');
});

Route::post('password/send-reset-code', [PasswordResetController::class, 'sendResetCode']);
Route::post('password/verify-reset-code', [PasswordResetController::class, 'verifyResetCode']);
Route::post('password/reset', [PasswordResetController::class, 'resetPassword']);


Route::group([
    'middleware' => 'jwt.verify',
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'auth'
 
], function ($router) {
    Route::post('/logout', 'Api\AuthController@logout');
    Route::get('/user-profile','Api\AuthController@userProfile');
});




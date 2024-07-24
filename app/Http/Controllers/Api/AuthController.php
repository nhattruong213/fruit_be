<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Exception;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;

class AuthController extends Controller
{
 
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
 
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
 
        if (! $token = auth('api')->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $refreshToken = $this->createRefreshToken();
 
        return $this->createNewToken($token, $refreshToken);
    }
 
    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {
        auth('api')->logout();
 
        return response()->json(['message' => 'User successfully signed out']);
    }
 
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {
        $refreshToken = request()->token;
        try {
            $decode = JWTAuth::getJWTProvider()->decode($refreshToken);
            $user = User::find($decode['sub']);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 401);
            }
            $token = auth('api')->login($user);
            $refreshToken = $this->createRefreshToken();
            return $this->createNewToken($token, $refreshToken);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Refresh Token Invalid'], 500);
        }
    }
 
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile() {
        return response()->json(['data' => auth('api')->user()]);
    }
 
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token, $refreshToken){
        return response()->json([
            'data' => [
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ]
        ]);
    }
 
    public function changePassWord(Request $request) {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string|min:6',
            'new_password' => 'required|string|confirmed|min:6',
        ]);
 
        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        $userId =auth('api')->user()->id;
 
        $user = User::where('id', $userId)->update(
                    ['password' => bcrypt($request->new_password)]
                );
 
        return response()->json([
            'message' => 'User successfully changed password',
            'user' => $user,
        ], 201);
    }

    private function createRefreshToken() {
        $data = [
            'sub' => auth('api')->user()->id,
            'random' => rand() . time(),
            'exp' => time() +  config('jwt.refresh_ttl'),
        ];

        return JWTAuth::getJWTProvider()->encode($data);
    }

}

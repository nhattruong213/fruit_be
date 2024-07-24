<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendResetCodeRequest;
use App\Models\PasswordReset;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function sendResetCode(SendResetCodeRequest $request)
    {

        $resetRecord = PasswordReset::where('email', $request->email)->first();
        if ($resetRecord && Carbon::now()->diffInMinutes($resetRecord->created_at) < 5) {
            return response()->json(['errors' => ['message' => 'Please wait before requesting a new reset code']], 429);
        }
        $resetCode = rand(100000, 999999);
        PasswordReset::updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $resetCode,
                'created_at' => Carbon::now()
            ]
        );
    
        Mail::raw("Your password reset code is $resetCode", function ($message) use ($request) {
            $message->to($request->email)
                    ->subject('Password Reset Code');
        });
    
        return response()->json([
            'data' => ['message' => 'Reset code sent to your email',
                'email' => $request->email
            ]
        ]);
    }

    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|numeric|digits:6'
        ]);
    
        $resetRecord = PasswordReset::where('email', $request->email)->first();
    
        if (!$resetRecord || $resetRecord->token != $request->code) {
            return response()->json(['errors' => ['message' => 'Invalid reset code']], 400);
        }
    
        $createdAt = Carbon::parse($resetRecord->created_at);
        if (Carbon::now()->diffInMinutes($createdAt) > 5) {
            PasswordReset::where('email', $request->email)->delete();
            return response()->json(['errors' => ['message' => 'Reset code has expired']], 400);
        }
        PasswordReset::where('email', $request->email)->delete();
        
        do {
            $token = Str::random(60);
        } while (User::where('reset_password_token', $token)->exists());

        $user = User::where('email', $request->email)->first();
        $user->reset_password_token = Hash::make($token);
        $user->save();
        return response()->json([
            'data' => ['token' => $token]
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|min:6',
        ]);

        $user = User::where('reset_password_token', $request->token)->first();

        if (!$user || !Hash::check($request->token, $user->reset_password_token)) {
            return response()->json(['errors' => ['message' => 'Invalid or expired reset token']], 400);
        }

        $user->password = Hash::make($request->password);
        $user->reset_password_token = null;
        $user->save();

        return response()->json([
            'data' => ['message' => 'Password has been updated successfully']
        ]);
    }
}

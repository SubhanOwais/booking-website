<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EmailVerificationController extends Controller
{
    /**
     * Verify email with token
     */
    public function verify($token)
    {
        $user = User::where('email_verification_token', $token)->first();

        if (!$user) {
            return redirect('/login?verified=invalid');
        }

        // Check if token is expired (24 hours)
        if ($user->updated_at && $user->updated_at->addHours(24)->isPast()) {
            return redirect('/login?verified=expired');
        }

        // Verify the user
        $user->update([
            'email_verified_at' => Carbon::now(),
            'is_email_verified' => true,
            'email_verification_token' => null,
            'Is_Active' => true // Activate the user
        ]);

        return redirect('/login?verified=success');
    }

    /**
     * Resend verification email
     */
    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,Email'
        ]);

        $user = User::where('Email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified'
            ], 400);
        }

        // Send verification email
        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent successfully'
        ]);
    }
}

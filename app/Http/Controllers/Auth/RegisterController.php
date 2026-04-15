<?php

namespace App\Http\Controllers\Auth;


use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'status' => session('status'),
        ]);
    }

    public function register(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,Email',
            'phone_number' => 'required|string|max:20|unique:users,Phone_Number',
            'password' => 'required|string|min:6|confirmed',
            'cnic' => 'required|string|max:15',
            'address' => 'nullable|max:500',
            'user_type' => 'required|string|in:WebCustomer',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'avatar' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Auto Username from Full Name
            $baseUsername = Str::lower(
                preg_replace('/\s+/', '', $request->name)
            );

            $username = $baseUsername;
            $count = User::where('User_Name', 'LIKE', $baseUsername . '%')->count();

            if ($count > 0) {
                $username = $baseUsername . str_pad($count, 3, '0', STR_PAD_LEFT);
            }

            // Handle profile picture upload
            $profilePicturePath = null;

            if ($request->hasFile('profile_picture') && $request->file('profile_picture')->isValid()) {

                $file = $request->file('profile_picture');

                // Make folder if not exists
                $destination = public_path('uploads/profiles');
                if (!file_exists($destination)) {
                    mkdir($destination, 0755, true);
                }

                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($destination, $filename);

                $profilePicturePath = 'uploads/profiles/' . $filename;

            } elseif ($request->filled('avatar')) {

                $profilePicturePath = 'images/avatars/' . $request->avatar;
            }

            // Generate email verification token
            $verificationToken = Str::random(64);

            // Create user (NOT verified, NOT active)
            $user = User::create([
                'Full_Name' => $request->name,
                'User_Name' => $username,
                'Email' => $request->email,
                'CNIC' => $request->cnic,
                'Phone_Number' => $request->phone_number,
                'Password' => Hash::make($request->password),
                'Address' => $request->address,
                'User_Type' => 'WebCustomer',
                'Profile_Picture' => $profilePicturePath,
                'Is_Active' => false, // User is NOT active until email verified
                'email_verified_at' => null, // NOT verified
                'is_email_verified' => false,
                'email_verification_token' => $verificationToken,
            ]);

            // Send verification email
            event(new Registered($user));
            $user->sendEmailVerificationNotification();

            return response()->json([
                'message' => 'Registration successful! Please check your email to verify your account.',
                'email' => $user->Email,
                'needs_verification' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->Full_Name,
                    'email' => $user->Email,
                ]
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Registration error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Registration failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}

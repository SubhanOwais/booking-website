<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required'    => 'Username, phone number, or email is required.',
            'password.required' => 'Password is required.',
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login    = trim($this->login);
        $password = $this->password;

        // ✅ Do NOT filter by Is_Active here – we want to detect inactive accounts
        $user = User::where(function ($query) use ($login) {
            $query->where('Phone_Number', $login)
                ->orWhere('Email', $login)
                ->orWhere('User_Name', $login);
        })->first();

        // 1. User not found
        if (!$user) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'login' => 'The credentials you entered are incorrect.',
            ]);
        }

        // 2. Password incorrect
        if (!Hash::check($password, $user->Password)) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'login' => 'The credentials you entered are incorrect.',
            ]);
        }

        // 3. Account inactive
        if (!$user->Is_Active) {
            throw ValidationException::withMessages([
                'login' => 'Your account has been deactivated. Please contact the company owner.',
            ]);
        }

        // 4. Allowed user types (SuperAdmin is also allowed but already handled earlier)
        $allowedTypes = ['WebCustomer', 'CompanyOwner', 'CompanyUser'];

        if (!$user->IsSuperAdmin && !in_array($user->User_Type, $allowedTypes)) {
            throw ValidationException::withMessages([
                'login' => 'You do not have permission to login here.',
            ]);
        }

        // 5. CompanyOwner / CompanyUser must have a company_id
        if (in_array($user->User_Type, ['CompanyOwner', 'CompanyUser']) && !$user->Company_Id) {
            throw ValidationException::withMessages([
                'login' => 'Your account is not linked to any company. Please contact support.',
            ]);
        }

        // All checks passed – log the user in
        Auth::login($user, $this->boolean('remember'));
        RateLimiter::clear($this->throttleKey());
    }

    public function getRedirectRoute(): string
    {
        $user = Auth::user();

        if ($user->IsSuperAdmin) {
            return route('admin.dashboard');
        }

        if (in_array($user->User_Type, ['CompanyOwner', 'CompanyUser'])) {
            return route('company.dashboard');
        }

        if ($user->User_Type === 'WebCustomer') {
            return route('profile.index');
        }

        return route('home');
    }

    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}

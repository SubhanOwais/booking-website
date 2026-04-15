<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // ✅ Laravel expects string or int, NOT bigint
    protected $keyType = 'int';

    // ✅ Custom timestamps
    const CREATED_AT = 'Created_On';
    const UPDATED_AT = 'Changed_On';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'Full_Name',
        'User_Name',
        'Email',
        'Phone_Number',
        'Profile_Picture',
        'CNIC',
        'Emergency_Number',
        'Address',
        'Password',
        'IsSuperAdmin',
        'LastLogin',
        'Is_Active',
        'Created_By',
        'Changed_By',
        'User_Type',
        'email_verification_token',
        'is_email_verified',
        'email_verified_at',
        'Permissions',
        'Company_Id',
    ];

    protected $appends = ['profile_picture_url'];

    public function getProfilePictureUrlAttribute()
    {
        if ($this->profile_picture) {
            if (str_starts_with($this->profile_picture, 'http')) {
                return $this->profile_picture;
            }
            return asset('storage/' . $this->profile_picture);
        }
        return asset('images/default-avatar.png');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'Password',
        'remember_token',
        'email_verification_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_email_verified' => 'boolean',
        'Is_Active' => 'boolean',
        'IsSuperAdmin' => 'boolean',
        'IsAdmin' => 'boolean',
    ];


    /**
     * Get the name of the password attribute.
     *
     * @return string
     */
    public function getAuthPasswordName()
    {
        return 'Password';
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->Password;
    }

    /**
     * Scope for Web Customers
     */
    public function scopeWebCustomers($query)
    {
        return $query->where('User_Type', 'WebCustomer');
    }

    /**
     * Scope for Super Admins
     */
    public function scopeSuperAdmins($query)
    {
        return $query->where('IsSuperAdmin', true);
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin()
    {
        return $this->IsSuperAdmin === true;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->IsAdmin === true;
    }

    /**
     * Check if user is web customer
     */
    public function isWebCustomer()
    {
        return $this->User_Type === 'WebCustomer';
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin()
    {
        $this->update(['LastLogin' => now()]);
    }

    /**
     * Find user by phone, email, or username for login
     */
    public static function findForLogin($identifier)
    {
        return static::where(function($query) use ($identifier) {
            $query->where('Phone_Number', $identifier)
                  ->orWhere('Email', $identifier)
                  ->orWhere('User_Name', $identifier);
        })->where('Is_Active', true)->first();
    }

    // Check if email is verified
    public function hasVerifiedEmail()
    {
        return ! is_null($this->email_verified_at);
    }

    // Mark email as verified
    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
            'is_email_verified' => true,
            'email_verification_token' => null,
        ])->save();
    }

    // Send email verification notification
    public function sendEmailVerificationNotification()
    {
        $token = \Str::random(64);

        $this->forceFill([
            'email_verification_token' => $token,
        ])->save();

        \Mail::to($this->Email)->send(new \App\Mail\VerifyEmail($this, $token));
    }
}

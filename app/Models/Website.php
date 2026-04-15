<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'description',
        'helpline_number',
        'email',
        'address',
        'facebook',
        'twitter',
        'instagram',
        'linkedin',
        'youtube',
        'tiktok',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];
}

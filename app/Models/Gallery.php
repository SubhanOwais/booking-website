<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    use HasFactory;

    // protected $table = 'gallery';

    protected $fillable = [
        'ServiceType',
        'ServiceName',
        'MainImage',
        'Images',
        'Paragraph',
        'Type',
        'active',
        'Tags',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    // Accessor to get tags as array
    public function getTagsArrayAttribute()
    {
        return $this->Tags ? explode(',', $this->Tags) : [];
    }

    // Accessor to get images as array
    public function getImagesArrayAttribute()
    {
        return $this->Images ? explode(', ', $this->Images) : [];
    }
}

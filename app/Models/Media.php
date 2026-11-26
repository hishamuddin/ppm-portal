<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $guarded = ['id'];

    protected $table = 'media';

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }
}

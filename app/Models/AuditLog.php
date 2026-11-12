<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $guarded = ['id'];

    public static function record(string $action, string $subject): void
    {
        static::create(['user_id' => auth()->id(), 'action' => $action, 'subject' => $subject]);
    }
}

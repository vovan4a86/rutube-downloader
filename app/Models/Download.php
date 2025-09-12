<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Download extends Model
{

    protected $fillable = [
        'url',
        'video_id',
        'title',
        'status',
        'file_path',
        'error_message',
        'progress'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = ['cancelled_at'];

    public function isCancelled()
    {
        return !is_null($this->cancelled_at);
    }
}

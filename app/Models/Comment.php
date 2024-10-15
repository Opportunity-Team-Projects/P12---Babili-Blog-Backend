<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'commentContent',
        'user_id',
        'post_id',
    ];

    // Beziehung zum Benutzer
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Beziehung zum Post
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likable');
    }
}

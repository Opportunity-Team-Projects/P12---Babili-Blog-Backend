<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth; // Importieren von Auth

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'contentTitle',
        'content',
        'contentImg',
        'slug',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likable');
    }

    /**
     * Fügt das is_liked Attribut zur Serialisierung hinzu
     */
    protected $appends = ['is_liked'];

    /**
     * Accessor für das is_liked Attribut.
     * Gibt zurück, ob der aktuell authentifizierte Benutzer den Post bereits geliked hat.
     */
    public function getIsLikedAttribute()
    {
        if (Auth::check()) {
            return $this->likes()->where('user_id', Auth::id())->exists();
        }

        return false;
    }

    public function getAuthorProfilePicUrlAttribute()
    {
        return $this->user->profile_pic_url;
    }

    public function bookmarkedBy()
    {
        return $this->belongsToMany(User::class, 'bookmarks', 'post_id', 'user_id')->withTimestamps();
    }
}

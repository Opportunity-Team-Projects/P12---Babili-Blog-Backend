<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    public function bookmarkPost($postId)
    {
        $post = Post::findOrFail($postId);
        $user = Auth::user();

        // Überprüfen, ob der Post bereits gebookmarkt wurde
        if (!$user->bookmarks()->where('post_id', $postId)->exists()) {
            $user->bookmarks()->attach($postId);
        }

        return response()->json(['message' => 'Post gebookmarkt'], 200);
    }

    public function unbookmarkPost($postId)
    {
        $post = Post::findOrFail($postId);
        $user = Auth::user();

        $user->bookmarks()->detach($postId);

        return response()->json(['message' => 'Bookmark entfernt'], 200);
    }

    public function getBookmarkedPosts()
    {
        $user = Auth::user();
        $bookmarkedPosts = $user->bookmarks()->with('user', 'likes')->get();

        // Fügen Sie 'likes_count' und 'is_liked' hinzu
        $bookmarkedPosts->transform(function ($post) use ($user) {
            $post->likes_count = $post->likes()->count();
            $post->is_liked = $post->likes()->where('user_id', $user->id)->exists();
            $post->is_bookmarked = true; // Da es sich um gebookmarkte Posts handelt
            return $post;
        });

        return response()->json(['bookmarked_posts' => $bookmarkedPosts], 200);
    }
}

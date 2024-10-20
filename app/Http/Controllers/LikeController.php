<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    public function likePost($postId)
    {
        $post = Post::findOrFail($postId);
        $userId = Auth::id();

        // Überprüfen, ob der User den Post bereits geliked hat
        if (!$post->likes()->where('user_id', $userId)->exists()) {
            $post->likes()->create(['user_id' => $userId]);
        }

        // Aktualisieren der Like-Anzahl
        $likes_count = $post->likes()->count();

        // Überprüfen, ob der User den Post jetzt liked
        $is_liked = $post->likes()->where('user_id', $userId)->exists();

        return response()->json([
            'message' => 'Post geliked',
            'likes_count' => $likes_count,
            'is_liked' => $is_liked
        ], 200);
    }

    public function unlikePost($postId)
    {
        $post = Post::findOrFail($postId);
        $userId = Auth::id();

        // Entfernen des Likes, falls vorhanden
        $post->likes()->where('user_id', $userId)->delete();

        // Aktualisieren der Like-Anzahl
        $likes_count = $post->likes()->count();

        // Überprüfen, ob der User den Post noch liked
        $is_liked = $post->likes()->where('user_id', $userId)->exists();

        return response()->json([
            'message' => 'Like entfernt',
            'likes_count' => $likes_count,
            'is_liked' => $is_liked
        ], 200);
    }

    public function likeComment($commentId)
    {
        $comment = Comment::findOrFail($commentId);
        $userId = Auth::id();

        // Verhindern, dass Benutzer ihren eigenen Kommentar liken
        if ($comment->user_id == $userId) {
            return response()->json(['message' => 'Du kannst deinen eigenen Kommentar nicht liken'], 403);
        }

        // Überprüfen, ob der User den Kommentar bereits geliked hat
        if (!$comment->likes()->where('user_id', $userId)->exists()) {
            $comment->likes()->create(['user_id' => $userId]);
        }

        // Aktualisieren der Like-Anzahl
        $likes_count = $comment->likes()->count();

        // Überprüfen, ob der User den Kommentar jetzt liked
        $is_liked = $comment->likes()->where('user_id', $userId)->exists();

        return response()->json([
            'message' => 'Kommentar geliked',
            'likes_count' => $likes_count,
            'is_liked' => $is_liked
        ], 200);
    }

    public function unlikeComment($commentId)
    {
        $comment = Comment::findOrFail($commentId);
        $userId = auth()->id();

        $comment->likes()->where('user_id', $userId)->delete();

        // Aktualisieren der Like-Anzahl
        $likes_count = $comment->likes()->count();

        // Überprüfen, ob der User den Kommentar noch liked
        $is_liked = $comment->likes()->where('user_id', $userId)->exists();

        return response()->json([
            'message' => 'Like entfernt',
            'likes_count' => $likes_count,
            'is_liked' => $is_liked
        ], 200);
    }
    public function getLikedPosts()


    {
        $user = auth()->user();
        $likedPosts = Post::whereHas('likes', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();

        return response()->json($likedPosts, 200);
    }
}

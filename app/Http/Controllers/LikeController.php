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

        // Überprüfen, ob der User den Post bereits geliked hat
        if (!$post->likes()->where('user_id', Auth::id())->exists()) {
            $post->likes()->create(['user_id' => Auth::id()]);
        }

        return response()->json(['message' => 'Post geliked'], 200);
    }

    public function unlikePost($postId)
    {
        $post = Post::findOrFail($postId);

        $post->likes()->where('user_id', Auth::id())->delete();

        return response()->json(['message' => 'Like entfernt'], 200);
    }

    public function likeComment($commentId)
    {
        $comment = Comment::findOrFail($commentId);

        // Überprüfen, ob der User den Kommentar bereits geliked hat
        if (!$comment->likes()->where('user_id', Auth::id())->exists()) {
            $comment->likes()->create(['user_id' => Auth::id()]);
        }

        return response()->json(['message' => 'Kommentar geliked'], 200);
    }

    public function unlikeComment($commentId)
    {
        $comment = Comment::findOrFail($commentId);

        $comment->likes()->where('user_id', Auth::id())->delete();

        return response()->json(['message' => 'Like entfernt'], 200);
    }

    public function getLikedPosts()


    {
        $user = Auth::user();
        $likedPosts = Post::whereHas('likes', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();

        return response()->json($likedPosts, 200);
    }
}

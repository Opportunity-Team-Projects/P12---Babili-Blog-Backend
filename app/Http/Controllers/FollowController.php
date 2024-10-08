<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class FollowController extends Controller
{
    // Benutzer folgen
    public function follow($userId)
    {
        $user = User::findOrFail($userId);
        $currentUser = Auth::user();

        if ($currentUser->id !== $user->id) {
            if (!$currentUser->followings()->where('followed_id', $user->id)->exists()) {
                $currentUser->followings()->attach($user);
                return response()->json(['message' => 'You are now following ' . $user->name], 200);
            } else {
                return response()->json(['message' => 'You are already following ' . $user->name], 400);
            }
        }

        return response()->json(['message' => 'You cannot follow yourself.'], 400);
    }

    // Benutzer entfolgen
    public function unfollow($userId)
    {
        $user = User::findOrFail($userId);
        $currentUser = Auth::user();

        if ($currentUser->id !== $user->id) {
            if ($currentUser->followings()->where('followed_id', $user->id)->exists()) {
                $currentUser->followings()->detach($user);
                return response()->json(['message' => 'You have unfollowed ' . $user->name], 200);
            } else {
                return response()->json(['message' => 'You are not following ' . $user->name], 400);
            }
        }

        return response()->json(['message' => 'You cannot unfollow yourself.'], 400);
    }

    // Liste der Benutzer, denen der aktuelle Benutzer folgt
    public function following()
    {
        $user = Auth::user();
        $following = $user->followings()->get();

        return response()->json($following);
    }

    // Liste der Follower des aktuellen Benutzers
    public function followers()
    {
        $user = Auth::user();
        $followers = $user->followers()->get();

        return response()->json($followers);
    }
}

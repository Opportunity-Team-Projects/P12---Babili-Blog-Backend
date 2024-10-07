<?php

namespace App\Http\Controllers;


use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\Request;


class CommentController extends Controller
{
    public function store(Request $request, $postId)
    {
        // Validierung der Eingaben
        $validated = $request->validate([
            'commentTitle' => 'required|string|max:255', // Kommentar-Titel erforderlich
            'commentContent' => 'required|string',       // Kommentar-Inhalt erforderlich
        ]);

        // Prüfen, ob der Post existiert
        $post = Post::findOrFail($postId);

        // Neuen Kommentar erstellen
        $comment = new Comment();
        $comment->commentTitle = $validated['commentTitle'];
        $comment->commentContent = $validated['commentContent'];
        $comment->user_id = auth()->id(); // Der eingeloggte Benutzer
        $comment->post_id = $post->id;    // Der Post, zu dem der Kommentar gehört
        $comment->save();

        return response()->json(['message' => 'Comment created successfully!', 'comment' => $comment], 201);
    }

    public function destroy($id)
    {
        // Kommentar anhand der ID finden
        $comment = Comment::findOrFail($id);

        // Prüfen, ob der eingeloggte Benutzer der Autor des Kommentars ist
        if ($comment->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Kommentar löschen
        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully'], 200);
    }
}

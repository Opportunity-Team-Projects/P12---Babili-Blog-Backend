<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = Post::all();  // Alle Posts abrufen
        return response()->json($posts);
    }

    public function myPosts()
    {
        $posts = Post::where('user_id', auth()->user()->id)->get();  // Nur Posts des aktuell eingeloggten Benutzers
        return response()->json($posts);
    }

    public function getPostsByUsername($username)
    {
        $user = User::where('name', 'like', '%' . $username . '%')->first();

        if ($user) {
            $posts = Post::where('user_id', $user->id)->get();
            return response()->json($posts);
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }
    }

    /**

     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Log für Debugging
        Log::info('store() wurde aufgerufen', ['request' => $request->all()]);

        // Validierung
        $validatedData = $request->validate([
            'contentTitle' => 'required|max:255',
            'content' => 'required',
            'contentPreview' => 'max:100',
        ]);

        // Post erstellen
        $post = new Post($validatedData);
        $post->user_id = auth()->id();
        $post->save();

        // Rückmeldung
        return response()->json([
            'message' => 'Post erfolgreich erstellt!',
            'post' => $post
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(post $post)
    {
        //
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        // Überprüfen, ob der Benutzer autorisiert ist, den Post zu aktualisieren
        if (auth()->id() !== $post->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validierung: Bei PATCH nur teilweise Felder erforderlich, bei PUT alle
        $rules = [
            'contentTitle' => $request->isMethod('patch') ? 'sometimes|max:255' : 'required|max:255',
            'content' => $request->isMethod('patch') ? 'sometimes' : 'required',
            'contentPreview' => 'max:100',
        ];

        $validatedData = $request->validate($rules);

        // Post aktualisieren
        $post->update($validatedData);

        // Erfolgreiche Antwort zurückgeben
        return response()->json([
            'message' => 'Post erfolgreich aktualisiert!',
            'post' => $post
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        // Überprüfen, ob der Benutzer autorisiert ist, den Post zu löschen
        if (auth()->id() !== $post->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Post löschen
        $post->delete();

        // Erfolgreiche Antwort zurückgeben
        return response()->json(['message' => 'Post erfolgreich gelöscht!'], 200);
    }
}

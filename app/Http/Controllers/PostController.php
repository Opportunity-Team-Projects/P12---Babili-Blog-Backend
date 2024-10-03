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
        $posts = Post::with('categories')->get(); // Alle Posts abrufen
        return response()->json($posts);
    }

    public function myPosts()
    {
        $posts = Post::with('categories')->where('user_id', auth()->user()->id)->get();  // Nur Posts des aktuell eingeloggten Benutzers
        return response()->json($posts);
    }

    public function search(Request $request)
    {
        $query = $request->input('query'); // Der Suchbegriff
        $keywords = explode(' ', $query); // Suchbegriff in einzelne Wörter aufteilen

        $posts = Post::where(function ($query) use ($keywords) {
            foreach ($keywords as $word) {
                $query->orWhereHas('user', function ($q) use ($word) {
                    $q->where('name', 'LIKE', '%' . $word . '%'); // Benutzername
                })
                    ->orWhereHas('categories', function ($q) use ($word) {
                        $q->where('categoryName', 'LIKE', '%' . $word . '%'); // Kategoriename
                    })
                    ->orWhere('contentTitle', 'LIKE', '%' . $word . '%'); // contentTitle in der Posts-Tabelle
            }
        })->get();

        return response()->json($posts);
    }




    public function getPostsByUser($userId)
    {
        $user = User::find($userId);

        if ($user) {
            // Lade die Posts des Benutzers zusammen mit den Kategorien
            $posts = Post::with('categories')->where('user_id', $user->id)->get();
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
        $validated = $request->validate([
            'contentTitle' => 'required|string|max:255',
            'content' => 'required',
            'contentPreview' => 'nullable|string|max:100', // Optionaler Vorschautext
            'contentImg' => 'nullable|string', // Optionale Bild-URL
            'category_ids' => 'required|array', // Validierung für ein Array von Kategorie-IDs
            'category_ids.*' => 'exists:categories,id', // Jede Kategorie-ID muss in der Kategorie-Tabelle existieren
        ]);

        $post = new Post();
        $post->contentTitle = $validated['contentTitle'];
        $post->content = $validated['content'];
        $post->contentPreview = $validated['contentPreview'] ?? null; // Optional, falls vorhanden
        $post->contentImg = $validated['contentImg'] ?? null; // Optional, falls vorhanden
        $post->user_id = auth()->id(); // Der eingeloggte Benutzer

        $post->save();

        // Verknüpft den Post mit den Kategorien
        $post->categories()->attach($validated['category_ids']);

        return response()->json(['message' => 'Post created successfully!']);
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
            'contentImg' => 'nullable|string', // Optionale Bild-URL
            'category_ids' => 'sometimes|array', // Kategorie-IDs als Array, falls vorhanden
            'category_ids.*' => 'exists:categories,id' // Jede Kategorie-ID muss existieren
        ];

        $validatedData = $request->validate($rules);

        // Post aktualisieren
        $post->update($validatedData);

        // Wenn Kategorien übermittelt wurden, aktualisiere die Pivot-Tabelle
        if (isset($validatedData['category_ids'])) {
            $post->categories()->sync($validatedData['category_ids']); // Kategorien synchronisieren
        }

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

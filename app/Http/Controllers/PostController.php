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

    public function getPostsByCategory($categoryId)
    {
        // Hole alle Posts, die zur angegebenen Kategorie gehören
        $posts = Post::whereHas('categories', function ($query) use ($categoryId) {
            $query->where('categories.id', $categoryId);
        })->get();

        // Rückgabe der Posts im JSON-Format
        return response()->json(['posts' => $posts], 200);
    }

    /**

     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contentTitle' => 'required|string|max:255',
            'content' => 'required',
            'contentImg' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:4096', // Optionales Bild
            'category_ids' => 'required|array', // Validierung für ein Array von Kategorie-IDs
            'category_ids.*' => 'exists:categories,id', // Jede Kategorie-ID muss in der Kategorie-Tabelle existieren
        ]);

        // Neuen Post erstellen
        $post = new Post();
        $post->contentTitle = $validated['contentTitle'];
        $post->content = $validated['content'];
        $post->user_id = auth()->id(); // Der eingeloggte Benutzer

        // Speichere das contentImg, falls es vorhanden ist
        if ($request->hasFile('contentImg')) {
            $contentImgPath = $request->file('contentImg')->store('content_images', 'public');
            $post->contentImg = $contentImgPath; // Speichere den Pfad in der Datenbank
        }

        $post->save();

        // Verknüpft den Post mit den Kategorien
        $post->categories()->attach($validated['category_ids']);

        return response()->json(['message' => 'Post created successfully!', 'post' => $post], 201);
    }



    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Post anhand der ID finden, inklusive Benutzer und Kommentare (Eager Loading)
        $post = Post::with(['user', 'comments.user', 'categories'])->findOrFail($id);

        return response()->json(['post' => $post], 200);
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

    public function countLikes($postId)
    {
        $post = Post::findOrFail($postId);
        $likeCount = $post->likes()->count();

        return response()->json(['post_id' => $postId, 'likes' => $likeCount]);
    }
}

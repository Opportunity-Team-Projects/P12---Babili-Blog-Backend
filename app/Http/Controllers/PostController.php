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
        $posts = Post::with('categories', 'user', 'likes')->get();

        $userId = auth()->id();

        $posts->transform(function ($post) use ($userId) {
            $post->likes_count = $post->likes()->count();
            $post->is_liked = $userId ? $post->likes()->where('user_id', $userId)->exists() : false;
            $post->is_bookmarked = $userId ? $post->bookmarkedBy()->where('user_id', $userId)->exists() : false;
            return $post;
        });

        return response()->json(['posts' => $posts]);
    }
    public function myPosts()
    {
        $posts = Post::with('categories')->where('user_id', auth()->user()->id)->get();  // Nur Posts des aktuell eingeloggten Benutzers
        return response()->json($posts);
    }

    public function search(Request $request)
    {
        $userId = auth()->id(); // Authentifizierten Benutzer abrufen

        if (!$userId) {
            return response()->json(['message' => 'Nicht authentifiziert'], 401);
        }

        $query = $request->input('query'); // Der Suchbegriff
        $keywords = explode(' ', $query); // Suchbegriff in einzelne Wörter aufteilen

        $posts = Post::with(['user', 'likes', 'bookmarkedBy']) // Benutzer, Likes und Bookmarks laden
            ->where(function ($query) use ($keywords) {
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

        // Transformation der Posts, um zusätzliche Felder hinzuzufügen
        $posts->transform(function ($post) use ($userId) {
            $post->likes_count = $post->likes()->count();
            $post->is_liked = $post->likes()->where('user_id', $userId)->exists();
            $post->is_bookmarked = $post->bookmarkedBy()->where('user_id', $userId)->exists();
            return $post;
        });

        return response()->json($posts);
    }


    public function searchPostsInUserCategories(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Nicht authentifiziert'], 401);
        }

        $queryInput = $request->input('query'); // Der Suchbegriff
        $keywords = explode(' ', $queryInput); // Suchbegriff in einzelne Wörter aufteilen
        $categoryIds = $user->categories()->pluck('categories.id');

        $posts = Post::with(['user', 'likes', 'bookmarkedBy']) // Benutzer, Likes und Bookmarks laden
            ->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            })
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $word) {
                    $query->orWhereHas('user', function ($q) use ($word) {
                        $q->where('name', 'LIKE', '%' . $word . '%'); // Benutzername
                    })
                        ->orWhereHas('categories', function ($q) use ($word) {
                            $q->where('categoryName', 'LIKE', '%' . $word . '%'); // Kategoriename
                        })
                        ->orWhere('contentTitle', 'LIKE', '%' . $word . '%'); // contentTitle
                }
            })
            ->get();

        // Transformation der Posts, um zusätzliche Felder hinzuzufügen
        $posts->transform(function ($post) use ($user) {
            $post->likes_count = $post->likes()->count();
            $post->is_liked = $post->likes()->where('user_id', $user->id)->exists();
            $post->is_bookmarked = $post->bookmarkedBy()->where('user_id', $user->id)->exists();
            return $post;
        });


        return response()->json($posts);
    }

    public function searchBookmarkedPosts(Request $request)
    {
        $userId = auth()->id();

        if (!$userId) {
            return response()->json(['message' => 'Nicht authentifiziert'], 401);
        }

        $queryInput = $request->input('query'); // Der Suchbegriff
        $keywords = explode(' ', $queryInput); // Suchbegriff in einzelne Wörter aufteilen

        $posts = Post::whereHas('bookmarkedBy', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->with(['user', 'likes', 'categories'])
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhereHas('user', function ($q2) use ($word) {
                        $q2->where('name', 'LIKE', '%' . $word . '%'); // Benutzername
                    })
                        ->orWhereHas('categories', function ($q2) use ($word) {
                            $q2->where('categoryName', 'LIKE', '%' . $word . '%'); // Kategoriename
                        })
                        ->orWhere('contentTitle', 'LIKE', '%' . $word . '%'); // contentTitle in der Posts-Tabelle
                }
            })
            ->get();

        $posts->transform(function ($post) use ($userId) {
            $post->likes_count = $post->likes()->count();
            $post->is_liked = $post->likes()->where('user_id', $userId)->exists();
            $post->is_bookmarked = true; // Da es sich um gebookmarkte Posts handelt
            return $post;
        });

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

    public function getPostsByUserCategories()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Nicht authentifiziert'], 401);
        }

        // Hole die IDs der Kategorien, die der Benutzer ausgewählt hat
        $categoryIds = $user->categories()->pluck('categories.id');

        // Hole die Posts, die zu diesen Kategorien gehören
        $posts = Post::whereHas('categories', function ($query) use ($categoryIds) {
            $query->whereIn('categories.id', $categoryIds);
        })->with('user')->get();

        return response()->json($posts);
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
        $post = Post::with(['user', 'comments.user', 'likes'])->findOrFail($id);


        $likes_count = $post->likes()->count();
        $is_liked = auth()->check() ? $post->likes()->where('user_id', auth()->id())->exists() : false;
        $is_bookmarked = auth()->check() ? $post->bookmarkedBy()->where('user_id', auth()->id())->exists() : false;

        // Kommentare mit Like-Informationen
        $comments = $post->comments->map(function ($comment) {
            $likes_count = $comment->likes()->count();
            $is_liked = auth()->check() ? $comment->likes()->where('user_id', auth()->id())->exists() : false;

            return [
                'id' => $comment->id,
                'commentContent' => $comment->commentContent,
                'created_at' => $comment->created_at,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'profile_photo_url' => $comment->user->profile_photo_url,
                ],
                'likes_count' => $likes_count,
                'is_liked' => $is_liked,

            ];
        });

        return response()->json([
            'post' => [
                'id' => $post->id,
                'contentTitle' => $post->contentTitle,
                'content' => $post->content,
                'contentImg' => $post->contentImg,
                'created_at' => $post->created_at,
                'user' => [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                    'profile_photo_url' => $post->user->profile_photo_url,
                ],
                'likes_count' => $likes_count,
                'is_liked' => $is_liked,
                'comments' => $comments,
                'is_bookmarked' => $is_bookmarked,
            ]
        ]);
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

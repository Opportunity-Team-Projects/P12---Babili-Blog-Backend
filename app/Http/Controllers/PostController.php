<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    /**
     * Display a listing of all posts.
     */
    public function index()
    {
        $posts = Post::with('categories', 'user', 'likes')->get();

        $userId = auth()->id();

        $posts->transform(function ($post) use ($userId) {

            $post->is_liked = $userId ? $post->likes()->where('user_id', $userId)->exists() : false;
            $post->is_bookmarked = $userId ? $post->bookmarkedBy()->where('user_id', $userId)->exists() : false;
            $post->comments_count = $post->comments()->count(); // Added comments_count
            $post->likes_count = $post->likes()->count();

            return $post;
        });

        return response()->json(['posts' => $posts]);
    }

    /**
     * Display posts of the authenticated user.
     */
    public function myPosts()
    {
        $posts = Post::with('categories')
            ->where('user_id', auth()->user()->id)
            ->get();

        $posts->transform(function ($post) {
            $post->comments_count = $post->comments()->count(); // Added comments_count
            return $post;
        });

        return response()->json($posts);
    }

    /**
     * Search for posts based on a query.
     */
    public function search(Request $request)
    {
        $userId = auth()->id(); // Authentifizierten Benutzer abrufen



        $query = $request->input('query'); // Der Suchbegriff
        $keywords = explode(' ', $query); // Suchbegriff in einzelne Wörter aufteilen

        $posts = Post::with(['user', 'likes', 'bookmarkedBy']) // Benutzer, Likes und Bookmarks laden

            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $word) {
                    $query->orWhereHas('user', function ($q) use ($word) {
                        $q->where('name', 'LIKE', '%' . $word . '%');
                    })
                        ->orWhereHas('categories', function ($q) use ($word) {
                            $q->where('categoryName', 'LIKE', '%' . $word . '%');
                        })
                        ->orWhere('contentTitle', 'LIKE', '%' . $word . '%')
                        ->orWhere('content', 'LIKE', '%' . $word . '%');
                }
            })
            ->get();

        $posts->transform(function ($post) {
            $post->likes_count = $post->likes()->count();
            $post->is_liked = auth()->check()
                ? $post->likes()->where('user_id', auth()->id())->exists()
                : false;
            $post->comments_count = $post->comments()->count(); // Added comments_count
            return $post;
        });

        // Transformation der Posts, um zusätzliche Felder hinzuzufügen
        $posts->transform(function ($post) use ($userId) {
            $post->likes_count = $post->likes()->count();
            $post->is_liked = $post->likes()->where('user_id', $userId)->exists();
            $post->is_bookmarked = $post->bookmarkedBy()->where('user_id', $userId)->exists();
            return $post;
        });

        return response()->json($posts);
    }

    /**
     * Search for posts within the user's selected categories.
     */

    public function searchPostsInUserCategories(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Nicht authentifiziert'], 401);
        }

        $queryInput = $request->input('query');
        $keywords = explode(' ', $queryInput);
        $categoryIds = $user->categories()->pluck('categories.id');

        $posts = Post::with(['user', 'likes', 'bookmarkedBy']) // Benutzer, Likes und Bookmarks laden
            ->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            })
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $word) {
                    $query->orWhereHas('user', function ($q) use ($word) {
                        $q->where('name', 'LIKE', '%' . $word . '%');
                    })
                        ->orWhereHas('categories', function ($q) use ($word) {
                            $q->where('categoryName', 'LIKE', '%' . $word . '%');
                        })
                        ->orWhere('contentTitle', 'LIKE', '%' . $word . '%')
                        ->orWhere('content', 'LIKE', '%' . $word . '%');
                }
            })
            ->get();

        // Transformation der Posts, um zusätzliche Felder hinzuzufügen
        $posts->transform(function ($post) use ($user) {
            $post->likes_count = $post->likes()->count();
            $post->comments_count = $post->comments()->count();
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
                        ->orWhere('contentTitle', 'LIKE', '%' . $word . '%')
                        ->orWhere('content', 'LIKE', '%' . $word . '%');
                }
            })
            ->get();

        $posts->transform(function ($post) use ($userId) {
            $post->likes_count = $post->likes()->count();
            $post->comments_count = $post->comments()->count();
            $post->is_liked = $post->likes()->where('user_id', $userId)->exists();
            $post->is_bookmarked = true; // Da es sich um gebookmarkte Posts handelt

            return $post;
        });

        return response()->json($posts);
    }

    /**
     * Get posts by a specific user.
     */
    public function getPostsByUser($userId)
    {
        $user = User::find($userId);

        if ($user) {
            $posts = Post::with('categories')
                ->where('user_id', $user->id)
                ->get();

            $posts->transform(function ($post) {
                $post->comments_count = $post->comments()->count(); // Added comments_count
                return $post;
            });

            return response()->json($posts);
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }
    }

    /**
     * Get posts by a specific category.
     */
    public function getPostsByCategory($categoryId)
    {
        $posts = Post::whereHas('categories', function ($query) use ($categoryId) {
            $query->where('categories.id', $categoryId);
        })
            ->get();

        $posts->transform(function ($post) {
            $post->comments_count = $post->comments()->count(); // Added comments_count
            return $post;
        });

        return response()->json(['posts' => $posts], 200);
    }

    /**
     * Get posts based on the user's selected categories.
     */
    public function getPostsByUserCategories()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Nicht authentifiziert'], 401);
        }

        $categoryIds = $user->categories()->pluck('categories.id');

        $posts = Post::whereHas('categories', function ($query) use ($categoryIds) {
            $query->whereIn('categories.id', $categoryIds);
        })
            ->with('user')
            ->get();

        $posts->transform(function ($post) {
            $post->likes_count = $post->likes()->count();
            $post->is_liked = auth()->check()
                ? $post->likes()->where('user_id', auth()->id())->exists()
                : false;
            $post->comments_count = $post->comments()->count(); // Added comments_count
            return $post;
        });

        return response()->json($posts);
    }

    /**
     * Store a newly created post in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contentTitle' => 'required|string|max:255',
            'content' => 'required',
            'contentImg' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $post = new Post();
        $post->contentTitle = $validated['contentTitle'];
        $post->content = $validated['content'];
        $post->user_id = auth()->id();

        if ($request->hasFile('contentImg')) {
            $contentImgPath = $request->file('contentImg')->store('content_images', 'public');
            $post->contentImg = $contentImgPath;
        }

        $post->save();

        $post->categories()->attach($validated['category_ids']);

        return response()->json(['message' => 'Post created successfully!', 'post' => $post], 201);
    }

    /**
     * Display a specific post.
     */
    public function show($id)
    {
        $post = Post::with(['user', 'comments.user', 'likes'])->findOrFail($id);


        $likes_count = $post->likes()->count();

        $is_liked = auth()->check() ? $post->likes()->where('user_id', auth()->id())->exists() : false;
        $is_bookmarked = auth()->check() ? $post->bookmarkedBy()->where('user_id', auth()->id())->exists() : false;

        // Comments with like information
        $comments = $post->comments->map(function ($comment) {
            $likes_count = $comment->likes()->count();
            $is_liked = auth()->check()
                ? $comment->likes()->where('user_id', auth()->id())->exists()
                : false;

            return [
                'id' => $comment->id,
                'commentContent' => $comment->commentContent,
                'created_at' => $comment->created_at,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'profile_pic_url' => $comment->user->profile_pic_url,
                ],
                'likes_count' => $likes_count,
                'is_liked' => $is_liked,

            ];
        });

        return response()->json(
            [
                'post' => [
                    'id' => $post->id,
                    'contentTitle' => $post->contentTitle,
                    'content' => $post->content,
                    'contentImg' => $post->contentImg,
                    'created_at' => $post->created_at,
                    'user' => [
                        'id' => $post->user->id,
                        'name' => $post->user->name,
                        'profile_pic_url' => $post->user->profile_pic_url,
                    ],
                    'likes_count' => $likes_count,
                    'is_liked' => $is_liked,
                    'comments_count' => $post->comments()->count(), // Added comments_count
                    'comments' => $comments,

                    'is_bookmarked' => $is_bookmarked,
                ]

            ],
        );
    }

    /**
     * Update a specific post.
     */
    public function update(Request $request, Post $post)
    {
        if (auth()->id() !== $post->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $rules = [
            'contentTitle' => $request->isMethod('patch') ? 'sometimes|max:255' : 'required|max:255',
            'content' => $request->isMethod('patch') ? 'sometimes' : 'required',
            'contentImg' => 'nullable|string',
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'exists:categories,id',
        ];

        $validatedData = $request->validate($rules);

        $post->update($validatedData);

        if (isset($validatedData['category_ids'])) {
            $post->categories()->sync($validatedData['category_ids']);
        }

        return response()->json([
            'message' => 'Post erfolgreich aktualisiert!',
            'post' => $post,
        ], 200);
    }

    /**
     * Remove a specific post.
     */
    public function destroy(Post $post)
    {
        if (auth()->id() !== $post->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $post->delete();

        return response()->json(['message' => 'Post erfolgreich gelöscht!'], 200);
    }
}

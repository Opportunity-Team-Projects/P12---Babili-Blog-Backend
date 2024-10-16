<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\UserCategoriesController;
//use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
//TODO Falls Pw Reset nicht mehr nötig löschen


// Öffentliche Route für alle Posts (ohne Authentifizierung)

//Posts

Route::get('/index', [PostController::class, 'index']);  // Alle Posts anzeigen
Route::get('/posts/user/{userId}', [PostController::class, 'getPostsByUser']);
Route::get('/posts/category/{categoryId}', [PostController::class, 'getPostsByCategory']);
Route::get('/search', [PostController::class, 'search'])->name('search');
Route::get('/posts/{id}', [PostController::class, 'show']);

// Likes zählen für einen bestimmten Post
Route::get('/posts/{postId}/likes', [PostController::class, 'countLikes']);

// Likes zählen für einen bestimmten Kommentar
Route::get('/comments/{commentId}/likes', [CommentController::class, 'countLikes']);

//User

Route::post('/register', [UserController::class, 'register']);

//Password

/* Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
Route::get('/reset-password/{token}', function ($token) {
    return view('auth.reset-password', ['token' => $token]);
})->name('password.reset'); */





// Geschützte Routen für CRUD-Operationen auf /posts
Route::middleware('auth:sanctum')->group(function () {

    //Posts
    Route::get('/myposts', [PostController::class, 'myPosts']);  // Posts des eingeloggten Benutzers
    Route::resource('posts', PostController::class);  // CRUD für Posts (Erstellen, Bearbeiten, Löschen)

    //Comment
    Route::post('/posts/{postid}/comments', [CommentController::class, 'store']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);


    //Category
    Route::get('/categories', [CategoryController::class, 'index']);

    //User
    Route::post('/user/update/name', [UserController::class, 'updateUsername']);
    Route::post('/user/update/password', [UserController::class, 'updatePassword']);
    Route::post('/user/update/email', [UserController::class, 'updateEmail']);
    Route::post('/user/update/pic', [UserController::class, 'updateProfilePic']);
    Route::delete('/user/delete', [UserController::class, 'deleteAccount']);
    Route::get('/user', [UserController::class, 'getUser']);

    //Follow
    Route::post('/follow/{userId}', [FollowController::class, 'follow']);
    Route::post('/unfollow/{userId}', [FollowController::class, 'unfollow']);
    Route::get('/following', [FollowController::class, 'following']);
    Route::get('/followers', [FollowController::class, 'followers']);

    //Like
    Route::post('/posts/{postId}/like', [LikeController::class, 'likePost']);
    Route::delete('/posts/{postId}/unlike', [LikeController::class, 'unlikePost']);
    Route::post('/comments/{commentId}/like', [LikeController::class, 'likeComment']);
    Route::delete('/comments/{commentId}/unlike', [LikeController::class, 'unlikeComment']);
    Route::get('/liked-posts', [LikeController::class, 'getLikedPosts']);

    //Contact
    Route::post('/contact', [ContactController::class, 'send']);

    //CustomFeed
    Route::post('/user/preferences', [UserCategoriesController::class, 'savePreferences']);
    Route::get('/user/preferences', [UserCategoriesController::class, 'getPreferences']);
});

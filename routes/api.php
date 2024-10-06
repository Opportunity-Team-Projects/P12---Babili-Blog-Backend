<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;

// Öffentliche Route für alle Posts (ohne Authentifizierung)

//Posts

Route::get('/index', [PostController::class, 'index']);  // Alle Posts anzeigen
Route::get('/posts/user/{userId}', [PostController::class, 'getPostsByUser']);
Route::get('/search', [PostController::class, 'search'])->name('search');

//User

Route::post('/register', [UserController::class, 'register']);




// Geschützte Routen für CRUD-Operationen auf /posts
Route::middleware('auth:sanctum')->group(function () {

    //Posts
    Route::get('/myposts', [PostController::class, 'myPosts']);  // Posts des eingeloggten Benutzers
    Route::resource('posts', PostController::class);  // CRUD für Posts (Erstellen, Bearbeiten, Löschen)
    Route::get('/categories', [CategoryController::class, 'index']);

    //User
    Route::post('/user/reset', [UserController::class, 'updatePassword']);
});

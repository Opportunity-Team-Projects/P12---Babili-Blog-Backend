<?php

namespace App\Http\Controllers;

use App\Models\post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = Post::all();  // Alle Posts abrufen
        return view('posts.index', compact('posts'));  // An die View 'posts.index' weitergeben
    }

    public function myPosts()
    {
        $posts = Post::where('user_id', auth()->user()->id)->get();  // Nur Posts des aktuell eingeloggten Benutzers

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validierung der Eingabedaten
        $validatedData = $request->validate([
            'contentTitle' => 'required|max:255',
            'content' => 'required',
            'contentPreview' => 'max:100',
        ]);

        // Einen neuen Post erstellen und die user_id automatisch vom eingeloggten Benutzer setzen
        $post = new Post($validatedData);
        $post->user_id = auth()->id();  // Die ID des aktuell eingeloggten Benutzers setzen
        $post->save();

        // Weiterleiten oder Rückgabe
        return redirect()->route('posts.index')->with('success', 'Post erstellt!');
    }

    /**
     * Display the specified resource.
     */
    public function show(post $post)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(post $post)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, post $post)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(post $post)
    {
        //
    }
}

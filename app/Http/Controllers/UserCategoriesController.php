<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Category;
use Illuminate\Http\Request;

class UserCategoriesController extends Controller
{

    public function savePreferences(Request $request)
    {
        $user = auth()->user();
        $categoryIds = $request->input('categories', []);
        $user->categories()->sync($categoryIds);

        return response()->json(['message' => 'Präferenzen erfolgreich gespeichert'], 200);
    }

    public function getPreferences()
    {
        $user = auth()->user();
        $categoryIds = $user->categories()->pluck('categories.id');

        return response()->json(['categories' => $categoryIds], 200);
    }
}

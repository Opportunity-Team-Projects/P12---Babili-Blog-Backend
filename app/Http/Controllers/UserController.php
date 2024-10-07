<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;



class UserController extends Controller
{

    public function getUser()
{
    $user = Auth::user();

    if ($user) {
        return response()->json(['user' => $user], 200);
    } else {
        return response()->json(['message' => 'User not authenticated'], 401);
    }
}
public function register(Request $request)
{
    // Validierung der Eingabedaten, einschließlich des Profilbildes
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
        'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Optionales Profilbild
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Speichere den Benutzer
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    // Speichere das Profilbild, falls es vorhanden ist
    if ($request->hasFile('profile_pic')) {
        $path = $request->file('profile_pic')->store('profile_pics', 'public');
        $user->profile_pic = $path; // Speichere den Pfad in der Datenbank
        $user->save();
    }

    // Generiere die öffentliche URL des Profilbildes
    $profilePicUrl = $user->profile_pic ? asset('storage/' . $user->profile_pic) : null;

    // Rückgabe der Benutzerdaten, einschließlich der Profilbild-URL
    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_pic_url' => $profilePicUrl,
        ],
    ], 201);
}


    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Überprüfe das aktuelle Passwort
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Das aktuelle Passwort ist falsch'], 401);
        }

        // Aktualisiere das Passwort
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Passwort erfolgreich aktualisiert'], 200);
    }

    public function updateEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_email' => 'required|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Überprüfe das aktuelle Passwort
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Das Passwort ist falsch'], 401);
        }

        // Aktualisiere die E-Mail-Adresse
        $user->email = $request->new_email;
        $user->save();

        return response()->json(['message' => 'E-Mail-Adresse erfolgreich aktualisiert'], 200);
    }

    public function updateProfilePic(Request $request)
    {
        $request->validate([
            'profile_pic' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
    
        $user = Auth::user();
    
        // Wenn bereits ein Profilbild vorhanden ist, lösche es
        if ($user->profile_pic) {
            Storage::delete($user->profile_pic);
        }
    
        // Speichere das neue Bild im 'public/profile_pics' Ordner
        $path = $request->file('profile_pic')->store('profile_pics', 'public');
    
        // Aktualisiere die `profile_pic`-Spalte in der Datenbank (nur den relevanten Pfad speichern)
        $user->profile_pic = str_replace('public/', '', $path); // Speichere nur 'profile_pics/...'
        $user->save();
    
        // Generiere die öffentliche URL zum Bild
        $profilePicUrl = asset('storage/' . $user->profile_pic);
    
        return response()->json(['message' => 'Profilbild erfolgreich aktualisiert', 'profile_pic_url' => $profilePicUrl], 200);
    }
    
}



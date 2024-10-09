<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        // Überprüfen, ob der Benutzer eingeloggt ist
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Benutzer nicht authentifiziert.'], 401);
        }

        // Validierung der Nachricht
        $request->validate([
            'message' => 'required|string',
        ]);

        // E-Mail senden
        Mail::raw($request->message, function ($message) use ($user) {
            $message->to(env('CONTACT_MAIL_RECEIVER'))
                ->subject('Kontaktformular-Nachricht von ' . $user->name)
                ->replyTo($user->email);
        });

        return response()->json(['message' => 'Nachricht erfolgreich gesendet!']);
    }
}

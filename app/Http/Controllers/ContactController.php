<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        // Validierung der Nachricht
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string',
            'agreeToTerms' => 'accepted',
        ]);

        $name = $request->input('name');
        $email = $request->input('email');
        $messageContent = $request->input('message');

        try {
            // E-Mail an den Seitenbetreiber senden
            Mail::raw($messageContent, function ($message) use ($name, $email) {
                $message->to(env('CONTACT_MAIL_RECEIVER'))
                    ->subject('Kontaktformular-Nachricht von ' . $name)
                    ->replyTo($email);
            });

            // Bestätigungs-E-Mail an den Benutzer senden
            $confirmationMessage = "Wir haben deine Anfrage erhalten und werden uns schnellst möglich darum kümmern..\n\nDein Tech&Games Team";

            Mail::raw($confirmationMessage, function ($message) use ($name, $email) {
                $message->to($email)
                    ->subject('Wir haben Ihre Nachricht erhalten')
                    ->replyTo(env('CONTACT_MAIL_RECEIVER'));
            });

            return response()->json(['message' => 'Nachricht erfolgreich gesendet!']);
        } catch (\Exception $e) {
            // Fehler protokollieren
            Log::error('Fehler beim Senden der Kontaktformular-E-Mail: ' . $e->getMessage());

            return response()->json(['message' => 'Fehler beim Senden der Nachricht. Bitte versuchen Sie es später erneut.'], 500);
        }
    }
}

<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends Notification
{
    /**
     * Das Passwort-Reset-Token.
     *
     * @var string
     */
    public $token;

    /**
     * Erstelle eine neue Benachrichtigungsinstanz.
     *
     * @param  string  $token
     * @return void
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Bestimme die Zustellkanäle der Benachrichtigung.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Erstelle die Mail-Darstellung der Benachrichtigung.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // Deine Frontend-URL für das Passwort-Reset
        $frontendUrl = env('APP_FRONTEND_URL') . '/reset-password';

        // Baue die vollständige URL mit Token und E-Mail als Query-Parameter
        $url = $frontendUrl . '?token=' . $this->token . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject(Lang::get('Passwort zurücksetzen'))
            ->line(Lang::get('Du erhältst diese E-Mail, weil wir eine Anfrage zum Zurücksetzen deines Passworts erhalten haben.'))
            ->action(Lang::get('Passwort zurücksetzen'), $url)
            ->line(Lang::get('Dieses Passwort-Reset-Token läuft in :count Minuten ab.', ['count' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire')]))
            ->line(Lang::get('Wenn du kein Passwort-Reset angefordert hast, ist keine weitere Aktion erforderlich.'));
    }
}

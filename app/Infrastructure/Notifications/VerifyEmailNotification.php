<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

final class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your email address')
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email Address', $this->frontendVerificationUrl($notifiable))
            ->line('If you did not create an account, no further action is required.');
    }

    /**
     * Builds a temporary signed URL against this API's own verification route
     * (required so the signature is computed over the correct URI), then lifts
     * only the id/hash/expires/signature values out of it to build a frontend
     * page URL. The frontend calls the real API endpoint with those exact
     * params, reconstructing the request the signature was generated for.
     */
    private function frontendVerificationUrl(object $notifiable): string
    {
        $apiSignedUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );

        $query = (string) parse_url($apiSignedUrl, PHP_URL_QUERY);

        return sprintf(
            '%s/verify-email/%s/%s?%s',
            rtrim((string) config('app.frontend_url'), '/'),
            $notifiable->getKey(),
            sha1($notifiable->getEmailForVerification()),
            $query,
        );
    }
}

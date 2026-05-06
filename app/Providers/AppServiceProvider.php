<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // The default Registered → SendEmailVerificationNotification flow generates
        // a signed URL for the route named `verification.verify`, which does not
        // exist here — our route is `api.v1.auth.verification.verify`. Without
        // this hook, every B2C registration crashes with RouteNotFoundException.
        VerifyEmail::createUrlUsing(static function (MustVerifyEmail $notifiable): string {
            \assert($notifiable instanceof Model, 'MustVerifyEmail notifiable must be an Eloquent model.');

            return URL::temporarySignedRoute(
                'api.v1.auth.verification.verify',
                now()->addMinutes((int) config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1((string) $notifiable->getEmailForVerification()),
                ],
            );
        });
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        // Google isn't set up (no client id) — don't bounce the user to a Google 400.
        if (! config('services.google.client_id')) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Google sign-in is not configured yet. Please use email and password.']);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'email_verified_at' => now(),
            ],
        );

        Auth::login($user, remember: true);

        return redirect('/dashboard');
    }
}

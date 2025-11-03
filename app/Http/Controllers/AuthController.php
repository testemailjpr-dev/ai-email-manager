<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\GmailAccount;
use Illuminate\Support\Facades\Auth;
use Exception;

class AuthController extends Controller
{
	public function redirectToGoogle()
	{
		return Socialite::driver('google')
			->scopes([
				'https://www.googleapis.com/auth/gmail.readonly',
				'https://www.googleapis.com/auth/gmail.modify',
				'https://www.googleapis.com/auth/userinfo.email',
				'https://www.googleapis.com/auth/userinfo.profile',
				'openid',
			])
			->with(['access_type' => 'offline', 'prompt' => 'consent'])
			->redirect();
	}

	public function handleGoogleCallback()
	{
		$user = null;
		if (Auth::check()) {
			$user = Auth::user();
		}
		
		try {
			$googleUser = Socialite::driver('google')->stateless()->user();
		} catch (Exception $e) {
			if( $user ){
				return redirect('/dashboard')->with('error', 'Google login failed: ' . $e->getMessage());
			} else {
				return redirect('/login')->with('error', 'Google login failed: ' . $e->getMessage());
			}
		}
	
		// Get full token data
		$tokenData = [
			'access_token' => $googleUser->token,
			'refresh_token' => $googleUser->refreshToken ?? null,
			'expires_in' => $googleUser->expiresIn ?? 3600,
			'created' => time(),
			'token_type' => 'Bearer',
		];

		// CASE 1: Google account already linked to a user
		if ( !$user ) {
			$existingGmailAccount = GmailAccount::where('google_id', $googleUser->getId())->first();
	
			if ($existingGmailAccount) {
				$user = $existingGmailAccount->user;
				$existingGmailAccount->update([
					'google_token' => json_encode($tokenData),
					'google_refresh_token' => $googleUser->refreshToken ?? $existingGmailAccount->google_refresh_token,
				]);
		
				Auth::login($user, true);
				return redirect('/dashboard')->with('success', 'Logged in with linked Google account.');
			}
		}
	
		// CASE 2: User is already logged in
		if ($user) {
			// Ensure the same Gmail email isn't linked under another user
			$existingEmailAccount = GmailAccount::where('email', $googleUser->getEmail())->first();
			if ($existingEmailAccount && $existingEmailAccount->user_id !== $user->id) {
				return redirect('/dashboard')->with('error', "This Gmail address is already linked to another account.");
			}

			// Store new Gmail account under logged-in user
			GmailAccount::updateOrCreate(
				['google_id' => $googleUser->getId()],
				[
					'user_id' => $user->id,
					'email' => $googleUser->getEmail(),
					'google_token' => json_encode($tokenData),
					'google_refresh_token' => $googleUser->refreshToken ?? null,
				]
			);
	
			return redirect('/dashboard')->with('success', 'Google account linked successfully.');
		}
	
		// CASE 3: User not logged in
		// Create or update local user account
		$user = User::updateOrCreate(
			['email' => $googleUser->getEmail()],
			[
				'name' => $googleUser->getName(),
				'email' => $googleUser->getEmail(),
				'password' => bcrypt(str()->random(16)),
			]
		);
	
		// Save Google account info separately
		GmailAccount::updateOrCreate(
			['google_id' => $googleUser->getId()],
			[
				'user_id' => $user->id,
				'email' => $googleUser->getEmail(),
				'google_token' => json_encode($tokenData),
				'google_refresh_token' => $googleUser->refreshToken ?? null,
			]
		);
	
		// Log in user automatically
		Auth::login($user, true);
	
		return redirect('/dashboard')->with('success', 'Logged in with Google successfully.');
	}
	
    public function login()
    {
        return view('auth.login');
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }
}

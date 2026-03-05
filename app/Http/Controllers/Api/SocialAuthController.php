<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectGoogle()
    {
        \Log::info('Google redirect initiated', [
            'redirect_url' => config('services.google.redirect'),
            'client_id' => config('services.google.client_id') ? 'SET' : 'NOT SET',
            'request_url' => request()->fullUrl()
        ]);
        
        // Ensure SSL verification is properly handled
        $driver = Socialite::driver('google');
        if (config('services.google.ssl_verify') === false) {
            $driver->with(['verify' => false]);
        }
        
        return $driver->stateless()->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function googleCallback()
    {
        try {
            // Log the incoming request for debugging
            \Log::info('Google callback received', [
                'query_params' => request()->query(),
                'full_url' => request()->fullUrl(),
                'server_params' => [
                    'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? null,
                    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
                    'REDIRECT_URL' => $_SERVER['REDIRECT_URL'] ?? null,
                ]
            ]);
            
            // Handle SSL verification based on config
            $driver = Socialite::driver('google')->stateless();
            if (config('services.google.ssl_verify') === false) {
                $driver->with(['verify' => false]);
            }
            
            $googleUser = $driver->user();

            \Log::info('Google user retrieved', [
                'id' => $googleUser->getId(),
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'avatar' => $googleUser->getAvatar()
            ]);

            // Find or create user based on provider and provider_id
            $user = User::where('provider', 'google')
                        ->where('provider_id', $googleUser->getId())
                        ->first();

            if (!$user) {
                // Check if a user with this email already exists (not from Google)
                $existingUser = User::where('email', $googleUser->getEmail())->first();
                
                if ($existingUser) {
                    // If user exists with same email but different provider, update with Google info
                    $existingUser->update([
                        'provider' => 'google',
                        'provider_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                    ]);
                    $user = $existingUser;
                } else {
                    // Create new user
                    $user = User::create([
                        'full_name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'provider' => 'google',
                        'provider_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                        'password' => bcrypt(Str::random(16)),
                    ]);
                }
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            \Log::error('Google authentication client error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Google authentication failed due to client error',
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Google authentication failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            // Check if it's an SSL error
            if (strpos($e->getMessage(), 'SSL') !== false || strpos($e->getMessage(), 'cURL error 60') !== false) {
                return response()->json([
                    'error' => 'Google authentication failed due to SSL certificate verification',
                    'message' => 'Please check your SSL configuration. You may need to set GOOGLE_SSL_VERIFY=true in your .env file or update your CA certificates.',
                    'hint' => 'Consider setting GOOGLE_SSL_VERIFY=false for development (not recommended for production)'
                ], 400);
            }
            return response()->json([
                'error' => 'Google authentication failed',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Redirect the user to the Facebook authentication page.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectFacebook()
    {
        \Log::info('Facebook redirect initiated', [
            'redirect_url' => config('services.facebook.redirect'),
            'client_id' => config('services.facebook.client_id') ? 'SET' : 'NOT SET',
            'request_url' => request()->fullUrl()
        ]);
        
        // Handle SSL verification based on config
        $driver = Socialite::driver('facebook')
            ->scopes(['email', 'public_profile'])
            ->stateless();
            
        if (config('services.facebook.ssl_verify') === false) {
            $driver->with(['verify' => false]);
        }
        
        return $driver->redirect();
    }

    /**
     * Obtain the user information from Facebook.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function facebookCallback()
    {
        try {
            // Log the incoming request for debugging
            \Log::info('Facebook callback received', [
                'query_params' => request()->query(),
                'full_url' => request()->fullUrl(),
            ]);
            
            // Handle SSL verification based on config
            $driver = Socialite::driver('facebook')->stateless();
            if (config('services.facebook.ssl_verify') === false) {
                $driver->with(['verify' => false]);
            }
            
            $fbUser = $driver->user();

            \Log::info('Facebook user retrieved', [
                'id' => $fbUser->getId(),
                'name' => $fbUser->getName(),
                'email' => $fbUser->getEmail(),
                'avatar' => $fbUser->getAvatar()
            ]);

            // Find or create user based on provider and provider_id
            $user = User::where('provider', 'facebook')
                        ->where('provider_id', $fbUser->getId())
                        ->first();

            if (!$user) {
                // Check if a user with this email already exists (not from Facebook)
                $existingUser = User::where('email', $fbUser->getEmail())->first();
                
                if ($existingUser) {
                    // If user exists with same email but different provider, update with Facebook info
                    $existingUser->update([
                        'provider' => 'facebook',
                        'provider_id' => $fbUser->getId(),
                        'avatar' => $fbUser->getAvatar(),
                    ]);
                    $user = $existingUser;
                } else {
                    // Create new user
                    $user = User::create([
                        'full_name' => $fbUser->getName(),
                        'email' => $fbUser->getEmail(),
                        'provider' => 'facebook',
                        'provider_id' => $fbUser->getId(),
                        'avatar' => $fbUser->getAvatar(),
                        'password' => bcrypt(Str::random(16)),
                    ]);
                }
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            \Log::error('Facebook authentication client error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Facebook authentication failed due to client error',
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Facebook authentication failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            // Check if it's an SSL error
            if (strpos($e->getMessage(), 'SSL') !== false || strpos($e->getMessage(), 'cURL error 60') !== false) {
                return response()->json([
                    'error' => 'Facebook authentication failed due to SSL certificate verification',
                    'message' => 'Please check your SSL configuration. You may need to set FACEBOOK_SSL_VERIFY=true in your .env file or update your CA certificates.',
                    'hint' => 'Consider setting FACEBOOK_SSL_VERIFY=false for development (not recommended for production)'
                ], 400);
            }
            return response()->json([
                'error' => 'Facebook authentication failed',
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Get configuration information for debugging
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConfigInfo()
    {
        return response()->json([
            'google_client_id' => config('services.google.client_id') ? 'SET' : 'NOT SET',
            'google_redirect_uri' => config('services.google.redirect'),
            'google_ssl_verify' => config('services.google.ssl_verify', true),
            'facebook_client_id' => config('services.facebook.client_id') ? 'SET' : 'NOT SET',
            'facebook_redirect_uri' => config('services.facebook.redirect'),
            'facebook_ssl_verify' => config('services.facebook.ssl_verify', true),
            'server_time' => now()->toISOString(),
            'app_url' => config('app.url'),
            'environment' => app()->environment(),
        ]);
    }
}

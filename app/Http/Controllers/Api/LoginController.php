<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache; // Import Cache facade

class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        // Set validation
        $validator = Validator::make($request->all(), [
            'email'     => 'required',
            'password'  => 'required'
        ]);

        // If validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Get credentials from request
        $credentials = $request->only('email', 'password');

        // Check if the user data exists in Redis (cache)
        $cacheKey = 'user_email:' . $request->email;
        $user = Cache::get($cacheKey);

        if (!$user) {
            // If user data is not found in cache, proceed with authentication
            if (!$token = auth()->guard('api')->attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email atau Password Anda salah'
                ], 401);
            }

            // After successful login, get user data
            $user = auth()->guard('api')->user();

            // Cache the user data and token for future requests (e.g., 60 minutes)
            Cache::put($cacheKey, $user, 60); // Save user data in cache for 60 minutes
            Cache::put('user_token:' . $request->email, $token, 60); // Save token in cache
        } else {
            // If user data is found in cache, just get the token from cache
            $token = Cache::get('user_token:' . $request->email);
        }

        // If authentication is successful
        return response()->json([
            'success' => true,
            'user'    => $user,
            'token'   => $token
        ], 200);
    }
}

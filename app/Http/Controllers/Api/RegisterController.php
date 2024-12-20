<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache; // Import Cache facade

class RegisterController extends Controller
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
            'name'      => 'required',
            'email'     => 'required|email|unique:users',
            'password'  => 'required|min:8|confirmed'
        ]);

        // If validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Check if user exists in Redis (cache) before creating
        $cacheKey = 'user_email:' . $request->email;
        $existingUser = Cache::get($cacheKey);

        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'User already exists in the cache.',
            ], 409);
        }

        // Create user in the database
        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => bcrypt($request->password)
        ]);

        // Cache the newly created user in Redis for faster access
        if ($user) {
            // Store the user in Redis with a cache time (e.g., 60 minutes)
            Cache::put($cacheKey, $user, 60);

            return response()->json([
                'success' => true,
                'user'    => $user,  
            ], 201);
        }

        // Return JSON process insert failed
        return response()->json([
            'success' => false,
            'message' => 'User creation failed.',
        ], 409);
    }
}

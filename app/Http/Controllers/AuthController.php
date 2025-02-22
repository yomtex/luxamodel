<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate input fields
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'dob' => 'required|date|before:' . Carbon::now()->subYears(18)->format('Y-m-d'),
            'gender' => 'required|in:male,female,other',
            'country' => 'required|string',
            'state' => 'required|string',
            'city' => 'required|string',
            'category' => 'required|string',
            'plan_type' => 'required|in:free,basic,vip',
            'profile_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'bio' => 'nullable|string',
            'card_details' => 'required_if:plan_type,basic,vip|array',
            'card_details.card_number' => 'required_if:plan_type,basic,vip|string|min:13|max:19',
            'card_details.expiry_date' => 'required_if:plan_type,basic,vip|string|min:5|max:7',
            'card_details.cvc' => 'required_if:plan_type,basic,vip|string|min:3|max:4',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Handle Profile Photo Upload
        $profilePhotoPath = null;
        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $profilePhotoPath = $file->storeAs('profile_photos', $fileName, 'public');
        }

        // ✅ First, create the user
        $user = User::create([
            'company_name' => $request->company_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'dob' => Carbon::createFromFormat('m/d/Y', $request->dob)->format('Y-m-d'),
            'gender' => $request->gender,
            'country' => $request->country,
            'state' => $request->state,
            'city' => $request->city,
            'category' => $request->category,
            'plan_type' => $request->plan_type,
            'profile_photo' => $profilePhotoPath,
            'bio' => $request->bio,
            'status' => 'pending', // Set default status
        ]);

        // ✅ Now that the user exists, charge the card if necessary
        if (in_array($request->plan_type, ['basic', 'vip'])) {
            $paymentController = new PaymentController();
            $chargeResponse = $paymentController->charge($request, $user);

            if ($chargeResponse->getStatusCode() !== 200) {
                return response()->json(['error' => 'Payment processing failed'], 400);
            }
        }

        // Create a JWT Token
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }



    // Login method to authenticate user and return a JWT token
    public function login(Request $request)
    {
        // Validate input fields
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Attempt to retrieve the user
        $user = User::where('email', $request->email)->first();

        // Check if user exists
        if (!$user) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Check if user status is "pending"
        if ($user->status === 'pending') {
            return response()->json(['error' => 'Account approval is pending. Please wait for verification.'], 403);
        }

        // Check password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Generate token
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ], 200);
    }


    // Logout method to invalidate the JWT token
    public function logout(Request $request)
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(['message' => 'Successfully logged out'], 200);
    }

    // Get user method to retrieve the authenticated user's details
    public function user(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        return response()->json([
            'user' => $user
        ], 200);
    }


    // update user profile
    public function updateProfile(Request $request)
    {
        $user = Auth::user(); // Get the authenticated user
        $gender = $user->gender; // Get the saved gender from the database

        $validator = Validator::make($request->all(), [
            // Personal Details (Required)
            'height_feet' => 'required|numeric|min:1|max:8', // Feet (1-8 feet)
            'height_inches' => 'required|numeric|min:0|max:11', // Inches (0-11 inches)
            'weight' => 'required|numeric|min:30|max:300',
            'skin_color' => 'required|string',
            'hair_color' => 'required|string',
            'hair_length' => 'required|string',
            'eye_color' => 'required|string',
            'ethnicity' => 'required|string',
            'tattoos' => 'required|boolean',
            'piercing' => 'required|boolean',
            'compensation' => 'required|string',
            'experience' => 'required|string',
            'shoot_nudes' => 'required|boolean',

            // Female-Specific Fields (Only if user is female)
            'bust' => $gender === 'female' ? 'required|numeric' : 'nullable',
            'hips' => $gender === 'female' ? 'required|numeric' : 'nullable',
            'dresswaist' => $gender === 'female' ? 'required|numeric' : 'nullable',
            'cup' => $gender === 'female' ? 'required|string' : 'nullable',
            'shoe' => $gender === 'female' ? 'required|numeric' : 'nullable',

            // Male-Specific Fields (Only if user is male)
            'chest' => $gender === 'male' ? 'required|numeric' : 'nullable',
            'inseam' => $gender === 'male' ? 'required|numeric' : 'nullable',
            'neck' => $gender === 'male' ? 'required|numeric' : 'nullable',
            'sleeve' => $gender === 'male' ? 'required|numeric' : 'nullable',
            'waist' => $gender === 'male' ? 'required|numeric' : 'nullable',
            'shoe' => $gender === 'male' ? 'required|numeric' : 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Convert height (feet & inches) to cm
        $height_cm = ($request->height_feet * 30.48) + ($request->height_inches * 2.54);

        // Update user details
        $user->update([
            'height' => round($height_cm, 2), // Store height in cm
            'weight' => $request->weight,
            'skin_color' => $request->skin_color,
            'hair_color' => $request->hair_color,
            'hair_length' => $request->hair_length,
            'eye_color' => $request->eye_color,
            'ethnicity' => $request->ethnicity,
            'tattoos' => $request->tattoos,
            'piercing' => $request->piercing,
            'compensation' => $request->compensation,
            'experience' => $request->experience,
            'shoot_nudes' => $request->shoot_nudes,
            'bust' => $gender === 'female' ? $request->bust : null,
            'hips' => $gender === 'female' ? $request->hips : null,
            'dresswaist' => $gender === 'female' ? $request->dresswaist : null,
            'cup' => $gender === 'female' ? $request->cup : null,
            'shoe' => $gender === 'female' ? $request->shoe : null,
            'chest' => $gender === 'male' ? $request->chest : null,
            'inseam' => $gender === 'male' ? $request->inseam : null,
            'neck' => $gender === 'male' ? $request->neck : null,
            'sleeve' => $gender === 'male' ? $request->sleeve : null,
            'waist' => $gender === 'male' ? $request->waist : null,
        ]);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ], 200);
    }

}

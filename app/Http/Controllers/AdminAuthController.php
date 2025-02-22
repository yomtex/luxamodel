<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Models\User;
use App\Models\News;
use App\Models\Agency;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
class AdminAuthController extends Controller
{
    /**
     * Handle admin login and return a JWT token if successful.
     */
    public function login(Request $request)
    {
        // \Log::info("Admin login method called");

        $credentials = $request->only('email', 'password');
        // \Log::info("Credentials received: ", $credentials);  // Log the credentials directly

        // Attempt to validate credentials manually before trying the guard
        try {
            $request->validate([
        'email' => 'required|email',
        'password' => 'required|string|min:6',
    ]);
            // \Log::info("Validation passed.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            // \Log::error("Validation failed: " . $e->getMessage());
            return response()->json(['error' => 'Validation failed', 'message' => $e->getMessage()], 422);
        }

        // Continue with authentication
        if (!$token = auth('admin')->attempt($credentials)) {
            // \Log::info("Authentication failed.");
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // \Log::info("Token generated: ", ['token' => $token]);

        return $this->respondWithToken($token);
    }




    /**
     * Handle the response with the generated JWT token.
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('admin')->factory()->getTTL() * 60,
            'admin' => Auth::guard('admin')->user() // Return the logged-in admin
        ]);
    }


    /**
     * List all agencies with their verification status.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function listAgencies()
    {
        // Retrieve all agencies, ordered by created date or status
        $agencies = Agency::all(); // You can modify this with pagination, filters, etc.
        
        return response()->json([
            'agencies' => $agencies
        ], 200);
    }

    /**
     * Verify an agency by changing its status from 'pending' to 'verified'.
     * 
     * @param int $agencyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyAgency($agencyId)
    {
        // Find the agency by its ID
        $agency = Agency::find($agencyId);

        if (!$agency) {
            return response()->json(['error' => 'Agency not found.'], 404);
        }

        // Check if the agency is already verified
        if ($agency->status === 'verified') {
            return response()->json(['message' => 'Agency is already verified.'], 400);
        }

        // Update the agency's status to 'verified'
        $agency->status = 'verified';
        $agency->save();

        return response()->json(['message' => 'Agency verified successfully.'], 200);
    }


     /**
     * Post news with image, caption text, and caption message.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postNews(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'caption_text' => 'required|string|max:255',
            'caption_message' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Store the image in the 'public' disk, which will place it in 'storage/app/public/images'
        $imageFile = $request->file('image');
        $imagePath = $imageFile->store('images', 'public'); // store the image

        // Generate the full URL to the image
        $imageUrl = url('storage/' . $imagePath);  // This will give you the full URL, including host

        // Create the news entry
        $news = News::create([
            'caption_text' => $request->caption_text,
            'caption_message' => $request->caption_message,
            'image' => $imageUrl, // Save relative path in the database
        ]);

        return response()->json([
            'message' => 'News posted successfully',
            'news' => $news,
            'image_url' => $imageUrl,  // Full image URL including the host (base URL)
        ], 201);
    }

    public function getAllNews()
    {
        // Fetch all news
        $news = News::all();

        // Return the news data as a JSON response
        return response()->json([
            'news' => $news
        ], 200);
    }

    public function editNews(Request $request, $newsId)
    {
        // Validate the incoming data
        $validator = Validator::make($request->all(), [
            'caption_text' => 'required|string|max:255',
            'caption_message' => 'required|string',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Find the news by its ID
        $news = News::find($newsId);

        // If news not found, return error
        if (!$news) {
            return response()->json(['error' => 'News not found.'], 404);
        }

        // Update news data
        $news->caption_text = $request->caption_text;
        $news->caption_message = $request->caption_message;

        // Check if there's an image to upload
        if ($request->hasFile('image')) {
            $imageFile = $request->file('image');
            $imagePath = $imageFile->store('images', 'public');
            $news->image = $imagePath;  // Save new image path
        }

        // Save the updated news
        $news->save();

        return response()->json([
            'message' => 'News updated successfully.',
            'news' => $news
        ], 200);
    }

    public function deleteNews($newsId)
    {
        // Find the news by its ID
        $news = News::find($newsId);

        // If news not found, return error
        if (!$news) {
            return response()->json(['error' => 'News not found.'], 404);
        }

        // Delete the news
        $news->delete();

        return response()->json([
            'message' => 'News deleted successfully.'
        ], 200);
    }


    public function getAllUsers()
    {
        $users = User::all(); // Fetch all users
        return response()->json(['users' => $users], 200);
    }

    public function deleteUser($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }

    public function verifyUser($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Update the user's status to 'verified'
        $user->update(['status' => 'verified']);

        return response()->json(['message' => 'User verified successfully'], 200);
    }





}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agency;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class AgencyAuthController extends Controller
{
  

    // Agency Registration
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agency_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:agencies',
            'official_mail' => 'required|string|email|max:255|unique:agencies',
            'category' => 'required|string',
            'phone_number' => 'required|string|max:20',
            'website' => 'nullable|string|url',
            'about_agency' => 'required|string',
            'address' => 'required|string',
            'country' => 'required|string',
            'agency_logo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // Max 5MB
            'contact_details' => 'required|array',
            'contact_details.full_name' => 'required|string|max:255',
            'contact_details.email' => 'required|string|email|max:255',
            'contact_details.position' => 'required|string|max:255',
            'contact_details.whatsapp' => 'nullable|string|max:20',
            'contact_details.telegram' => 'nullable|string|max:20',
            'contact_details.wechat' => 'nullable|string|max:20',
            'contact_details.instagram' => 'nullable|url',
            'contact_details.facebook' => 'nullable|url',
            'contact_details.tiktok' => 'nullable|url',
            'contact_details.linkedin' => 'nullable|url',
            'contact_details.twitter' => 'nullable|url',
            'contact_details.vx' => 'nullable|url',
            'contact_details.youtube' => 'nullable|url',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Handle agency logo upload
        $agencyLogoPath = null;
        if ($request->hasFile('agency_logo')) {
            $agencyLogoPath = $request->file('agency_logo')->store('agency_logos', 'public');
        }

        $agency = Agency::create([
            'agency_name' => $request->agency_name,
            'email' => $request->email,
            'official_mail' => $request->official_mail,
            'category' => $request->category,
            'phone_number' => $request->phone_number,
            'website' => $request->website,
            'about_agency' => $request->about_agency,
            'address' => $request->address,
            'country' => $request->country,
            'agency_logo' => $agencyLogoPath,
            'contact_details' => json_encode($request->contact_details),
            'status' => 'pending', // Default status: pending (Admin must verify)
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Agency registered successfully. Waiting for admin verification.'], 201);
    }

    // Agency Login
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        // Attempt to authenticate the agency
        if (!$token = auth('agency')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get the authenticated agency
        $agency = auth('agency')->user();

        // Check if agency status is "pending"
        if ($agency->status === 'pending') {
            return response()->json([
                'error' => 'Your account is pending approval. Please wait for admin approval.'
            ], 403);
        }

        return $this->respondWithToken($token);
    }



    // Get Authenticated Agency
    public function me()
    {   
        return response()->json(auth('agency')->user());
    }
    // Logout
    public function logout()
    {
        auth('agency')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    // Refresh JWT Token
    public function refresh()
    {
         return $this->respondWithToken(auth('agency')->refresh());
    }

    // Format Token Response
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('agency')->factory()->getTTL() * 60
        ]);
    }


    // search agency

    public function searchAgencies(Request $request)
    {
        $search = $request->query('search');

        if (!$search) {
            return response()->json(['error' => 'Search term is required.'], 400);
        }

        // Query only approved agencies that match the search
        $agencies = Agency::where('status', 'verified')
            ->where(function ($query) use ($search) {
                $query->where('agency_name', 'LIKE', "%{$search}%")
                      ->orWhere('category', 'LIKE', "%{$search}%");
            })
            ->get();

        if ($agencies->isEmpty()) {
            return response()->json(['message' => 'No agency found.'], 404);
        }

        return response()->json(['agencies' => $agencies], 200);
    }

    /**
     * Get all verified agencies filtered by category.
     */
    public function getVerifiedAgenciesByCategory(Request $request)
    {
        $category = $request->query('category');

        if (!$category) {
            return response()->json(['error' => 'Category is required.'], 400);
        }

        $agencies = Agency::where('status', 'approved')
            ->where('category', $category)
            ->get();

        if ($agencies->isEmpty()) {
            return response()->json(['message' => 'No verified agencies found for this category.'], 404);
        }

        return response()->json(['agencies' => $agencies], 200);
    }

    /**
     * Get all verified agencies.
     */
    public function getAllVerifiedAgencies()
    {
        $agencies = Agency::where('status', 'approved')->get();

        if ($agencies->isEmpty()) {
            return response()->json(['message' => 'No verified agencies found.'], 404);
        }

        return response()->json(['agencies' => $agencies], 200);
    }



}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Album;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AlbumController extends Controller
{
    /**
     * Create a new album
     */
    public function create(Request $request)
    {
        $user = Auth::user();

        if ($user->plan_type === 'free') {
            return response()->json(['error' => 'Free plan users cannot create albums. Upgrade your plan.'], 403);
        }

        $albumCount = Album::where('user_id', $user->id)->count();
        $albumLimit = ($user->plan_type === 'basic') ? 8 : 16;

        if ($albumCount >= $albumLimit) {
            return response()->json(['error' => 'You have reached the maximum album limit for your plan.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'hidden' => 'required|boolean',
            'privacy' => 'required|in:public,password',
            'password' => 'required_if:privacy,password|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }


        $album = Album::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'hidden' => $request->hidden,
            'privacy' => $request->privacy,
            'password' => $request->privacy === 'password' ? bcrypt($request->password) : null,
        ]);

        return response()->json(['message' => 'Album created successfully', 'album' => $album], 201);
    }

    /**
     * Get all albums for the authenticated user
     */
    public function getUserAlbums()
    {
        $user = Auth::user();
        $albums = Album::where('user_id', $user->id)->get();

        return response()->json(['albums' => $albums], 200);
    }

    /**
     * Edit an album
     */
    public function edit(Request $request, $id)
    {
        $user = Auth::user();
        $album = Album::where('user_id', $user->id)->find($id);

        if (!$album) {
            return response()->json(['error' => 'Album not found or unauthorized access.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'hidden' => 'sometimes|boolean',
            'privacy' => 'sometimes|in:public,password',
            'password' => 'required_if:privacy,password|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Update album fields if provided
        if ($request->has('name')) $album->name = $request->name;
        if ($request->has('hidden')) $album->hidden = $request->hidden;
        if ($request->has('privacy')) {
            $album->privacy = $request->privacy;
            if ($request->privacy === 'password' && $request->has('password')) {
                $album->password = bcrypt($request->password);
            }
        }

        $album->save();

        return response()->json(['message' => 'Album updated successfully', 'album' => $album], 200);
    }

    /**
     * Delete an album
     */
    public function delete($id)
    {
        $user = Auth::user();
        $album = Album::where('user_id', $user->id)->find($id);

        if (!$album) {
            return response()->json(['error' => 'Album not found or unauthorized access.'], 404);
        }

        $album->delete();

        return response()->json(['message' => 'Album deleted successfully'], 200);
    }
}

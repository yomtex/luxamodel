<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Image;
use App\Models\Album;
use App\Models\Agency;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    /**
     * Upload an image to an album
     */
    public function upload(Request $request, $albumId)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array',
            'images.*' => 'file|image|mimes:jpeg,png,jpg|max:5120', // Ensure it's a valid image file
            'agency_ids' => 'nullable|array',
            'agency_ids.*' => 'array', // Ensure each agency_id set is an array
            'agency_ids.*.*' => 'exists:agencies,id', // Validate each agency_id
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Check if any of the selected agencies are still pending
        $agencyTags = $request->input('agency_ids', []);
        foreach ($agencyTags as $agencySet) {
            foreach ($agencySet as $agencyId) {
                $agency = Agency::find($agencyId);
                if ($agency && $agency->status === 'pending') { // Check if agency is pending
                    return response()->json([
                        'error' => 'One or more selected agencies are still pending approval.'
                    ], 403);
                }
            }
        }

        // Check if album exists, otherwise create a default one
        $album = Album::where('id', $albumId)->where('user_id', Auth::id())->first();
        if (!$album) {
            $album = Album::create([
                'user_id' => Auth::id(),
                'name' => 'Default Album ' . now()->format('Ymd_His'),
            ]);
        }

        $uploadedImages = [];

        // Convert images input to an array
        $imageFiles = $request->file('images');

        foreach ($imageFiles as $index => $imageFile) {
            $imagePath = $imageFile->store('images', 'public');

            $image = Image::create([
                'file_path' => $imagePath,
                'album_id' => $album->id,
                'user_id' => Auth::id(),
            ]);

            // Attach agencies if they exist for this index
            if (isset($agencyTags[$index]) && is_array($agencyTags[$index])) {
                $image->agencies()->attach($agencyTags[$index]);
            }

            $uploadedImages[] = [
                'id' => $image->id,
                'file_path' => asset('storage/' . $image->file_path),
                'agencies' => $image->agencies()->get(['id', 'name']),
            ];
        }

        return response()->json([
            'message' => 'Images uploaded and agencies tagged successfully',
            'images' => $uploadedImages
        ], 201);
    }




    /**
     * Get all images from an album
     */
    public function getAlbumImages($albumId)
    {
        $album = Album::where('id', $albumId)->where('user_id', Auth::id())->first();
        if (!$album) {
            return response()->json(['error' => 'Album not found or unauthorized'], 404);
        }

        return response()->json(['images' => $album->images]);
    }

    /**
     * Edit an image (rename or update file)
     */
    public function edit(Request $request, $imageId)
    {
        $user = Auth::user();
        $image = Image::where('id', $imageId)->where('user_id', $user->id)->first();

        if (!$image) {
            return response()->json(['error' => 'Image not found or unauthorized'], 404);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // Optional new image
            'new_name' => 'nullable|string|max:255', // Optional new name
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // If a new image is uploaded, delete the old one and save the new one
        if ($request->hasFile('image')) {
            // Delete old image
            Storage::disk('public')->delete($image->file_path);

            // Store new image
            $newImagePath = $request->file('image')->store('images', 'public');
            $image->file_path = $newImagePath;
        }

        // Update image name if provided
        if ($request->has('new_name')) {
            $image->name = $request->new_name;
        }

        $image->save();

        return response()->json(['message' => 'Image updated successfully', 'image' => $image], 200);
    }

    /**
     * Delete an image
     */
    public function delete($imageId)
    {
        $user = Auth::user();
        $image = Image::where('id', $imageId)->where('user_id', $user->id)->first();

        if (!$image) {
            return response()->json(['error' => 'Image not found or unauthorized'], 404);
        }

        // Delete image file from storage
        Storage::disk('public')->delete($image->file_path);

        // Delete image record
        $image->delete();

        return response()->json(['message' => 'Image deleted successfully'], 200);
    }

    // show tagged images

    public function showTaggedAgencies($imageId)
    {
        // Fetch the image
        $image = Image::with('agencies')->find($imageId);

        if (!$image) {
            return response()->json(['error' => 'Image not found.'], 404);
        }

        // Return the agencies tagged to this image
        return response()->json([
            'image' => $image,
            'agencies' => $image->agencies,
        ], 200);
    }

    public function getTaggedImages()
    {
        $user = Auth::user();

        // Get all images where this model is tagged by any agency
        $images = Image::whereHas('agencies', function ($query) use ($user) {
            $query->where('model_id', $user->id);
        })->with(['album', 'agencies'])->get();

        return response()->json([
            'message' => 'Tagged images retrieved successfully',
            'images' => $images
        ], 200);
    }

    // model tagged agency
    public function getImagesWithTaggedAgencies()
    {
        $user = Auth::user();

        // Get all images where the authenticated model (user) has tagged agencies
        $images = Image::where('user_id', $user->id)
                       ->with('agencies') // Fetch associated agencies
                       ->get();

        return response()->json([
            'message' => 'Images with tagged agencies retrieved successfully',
            'images' => $images
        ], 200);
    }


}

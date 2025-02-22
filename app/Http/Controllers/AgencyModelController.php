<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Agency;
use App\Models\AgencyModelRequest;
use App\Models\User;

class AgencyModelController extends Controller
{
    /**
     * Search for agencies by name or category.
     */
    public function searchAgencies(Request $request)
    {
        $agencies = Agency::where('agency_name', 'LIKE', "%{$request->query('search')}%")
            ->orWhere('category', 'LIKE', "%{$request->query('search')}%")
            ->get();

        return response()->json(['agencies' => $agencies], 200);
    }

    /**
     * Model sends a request to join or claim an agency.
     */
    public function requestToJoin(Request $request, $agencyId)
    {
        $user = Auth::user();

        $request->validate([
            'request_type' => 'required|in:discover,claim',
        ]);

        if (AgencyModelRequest::where('model_id', $user->id)->where('agency_id', $agencyId)->exists()) {
            return response()->json(['error' => 'You have already submitted a request for this agency.'], 400);
        }

        AgencyModelRequest::create([
            'model_id' => $user->id,
            'agency_id' => $agencyId,
            'status' => 'pending',
            'request_type' => $request->request_type,  // Store request type
        ]);

        return response()->json(['message' => 'Request sent successfully.'], 201);
    }

    /**
     * Agency views pending requests with request type.
     */
    public function viewRequests()
    {
        $agency = Auth::user();

        $requests = AgencyModelRequest::where('agency_id', $agency->id)
            ->where('status', 'pending')
            ->with('model')
            ->get();

        return response()->json(['requests' => $requests], 200);
    }

    /**
     * Agency approves or declines a model/claim request.
     */
    public function respondToRequest(Request $request, $requestId)
    {
        $agency = Auth::user();
        $agencyRequest = AgencyModelRequest::where('id', $requestId)->where('agency_id', $agency->id)->first();

        if (!$agencyRequest) {
            return response()->json(['error' => 'Request not found.'], 404);
        }

        if (!in_array($request->status, ['accepted', 'declined'])) {
            return response()->json(['error' => 'Invalid status.'], 400);
        }

        $agencyRequest->update(['status' => $request->status]);

        if ($agencyRequest->request_type === 'claim' && $request->status === 'accepted') {
            // Assign the agency to the user
            $agency->update(['owner_id' => $agencyRequest->model_id]);
        }

        return response()->json(['message' => "Request {$request->status} successfully."], 200);
    }

    /**
     * Get models belonging to an agency.
     */
    public function getAgencyModels($agencyId)
    {
        $models = User::whereHas('agencyModelRequests', function ($query) use ($agencyId) {
            $query->where('agency_id', $agencyId)->where('status', 'accepted');
        })->get();

        return response()->json(['models' => $models], 200);
    }

    // delete request
    public function deleteRequest($requestId)
    {
        $user = Auth::user();

        // Check if the request exists and belongs to the authenticated model
        $agencyRequest = AgencyModelRequest::where('id', $requestId)
                                           ->where('model_id', $user->id)
                                           ->first();

        if (!$agencyRequest) {
            return response()->json(['error' => 'Request not found or unauthorized access.'], 404);
        }

        // Delete the request
        $agencyRequest->delete();

        return response()->json(['message' => 'Request deleted successfully.'], 200);
    }


    // get agency request 
    public function getAgencyRequests()
    {
        $user = Auth::user();

        // Fetch all requests sent by this model (authenticated user) to agencies
        $agencyRequests = AgencyModelRequest::where('model_id', $user->id)
                                            ->with('agency') // Assuming 'agency' is the relation defined on the AgencyModelRequest
                                            ->get();

        // Return the list of agencies
        return response()->json([
            'agency_requests' => $agencyRequests,
        ], 200);
    }



}



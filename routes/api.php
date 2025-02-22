<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AddCardsMoney;
use App\Http\Controllers\AgencyAuthController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\AdminAuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// agency search route takes search parameter
Route::get('/agencies/search', [AgencyAuthController::class, 'searchAgencies']);

// get all verified agencies
Route::get('/agencies/verified', [AgencyController::class, 'getAllVerifiedAgencies']);

// get all verified agencies based on categories
Route::get('/agencies/verified/category', [AgencyController::class, 'getVerifiedAgenciesByCategory']);

// User Authentication Routes
Route::group(['middleware' => 'api', 'prefix' => 'auth'], function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware(['auth:api', 'role:user'])->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/addmoney', [AddCardsMoney::class, 'processPayment']);
        Route::post('/addmoneyPin', [AddCardsMoney::class, 'addMoneyPin']);
        Route::post('/submitOtp', [AddCardsMoney::class, 'handleOtp']);

        // AlbumController
        Route::post('/albums/create', [AlbumController::class, 'create']);
        Route::get('/albums', [AlbumController::class, 'getUserAlbums']);
        Route::put('/albums/{id}', [AlbumController::class, 'edit']);
        Route::delete('/albums/{id}', [AlbumController::class, 'delete']);

        // image route
        Route::post('/albums/{albumId}/upload', [ImageController::class, 'upload']);
        Route::get('/albums/{albumId}/images', [ImageController::class, 'getAlbumImages']);
        Route::put('/images/{imageId}', [ImageController::class, 'edit']);
        Route::delete('/images/{imageId}', [ImageController::class, 'delete']);
        Route::get('/images/{imageId}/tagged-agencies', [ImageController::class, 'showTaggedAgencies']);
        Route::get('/tagged-images', [ImageController::class, 'getTaggedImages']);
        Route::get('/images-with-tagged-agencies', [ImageController::class, 'getImagesWithTaggedAgencies']);

        // agency model request
        Route::get('/agencies/search', [AgencyModelController::class, 'searchAgencies']);
        Route::post('/agencies/{agencyId}/request', [AgencyModelController::class, 'requestToJoin']);
        Route::delete('/requests/{requestId}', [AgencyModelController::class, 'deleteRequest']);
        Route::get('/my-agency-requests', [AgencyModelController::class, 'getAgencyRequests']);
    });
});

// Agency Authentication Routes
Route::group(['middleware' => 'api', 'prefix' => 'agency'], function () {
    Route::post('/register', [AgencyAuthController::class, 'register']);
    Route::post('/login', [AgencyAuthController::class, 'login']);

    Route::middleware(['auth:agency', 'role:agency'])->group(function () {
        Route::get('/me', [AgencyAuthController::class, 'me']);
        Route::post('/logout', [AgencyAuthController::class, 'logout']);
        Route::post('/refresh', [AgencyAuthController::class, 'refresh']);

        // agency model request

        Route::get('/agency/requests', [AgencyModelController::class, 'viewRequests']);
        Route::post('/agency/requests/{requestId}/respond', [AgencyModelController::class, 'respondToRequest']);
        Route::get('/agency/{agencyId}/models', [AgencyModelController::class, 'getAgencyModels']);

    });
});


// Admin Route

// Admin Route
Route::group(['middleware' => 'api', 'prefix' => 'admin'], function () {
    // Admin login route
    Route::post('/login', [AdminAuthController::class, 'login']);
    
    // Admin routes for authenticated admins only
    Route::middleware('auth:admin')->group(function () {
        // Admin info route
        Route::get('/me', [AdminAuthController::class, 'me']);
        
        // Admin logout route
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        
        // Admin refresh token route
        Route::post('/refresh', [AdminAuthController::class, 'refresh']);
        
        // Admin routes to manage agencies
        Route::get('/agencies', [AdminAuthController::class, 'listAgencies']); // List all agencies
        Route::put('/agencies/{agencyId}/verify', [AdminAuthController::class, 'verifyAgency']); // Verify an agency
        
        // Admin route to post news
        Route::post('/news', [AdminAuthController::class, 'postNews']); // Post news

          // Route to get all news
        Route::get('/news', [AdminAuthController::class, 'getAllNews']);

        // Route to edit news (put method for updating)
        Route::put('/news/{newsId}', [AdminAuthController::class, 'editNews']);

        // Route to delete news
        Route::delete('/news/{newsId}', [AdminAuthController::class, 'deleteNews']);

        Route::get('/users', [AdminAuthController::class, 'getAllUsers']);
        
        Route::delete('/users/{userId}', [AdminAuthController::class, 'deleteUser']);
        Route::put('/users/{userId}/verify', [AdminAuthController::class, 'verifyUser']); // Verify user


    });
});


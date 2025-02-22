<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentHistory;
use App\Models\User;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function charge(Request $request, User $user) // Accept user object
    {
        $planType = $request->plan_type;
        $amount = 0;

        if ($planType == 'basic') {
            $amount = 10;
        } elseif ($planType == 'vip') {
            $amount = 20;
        } else {
            return response()->json(['message' => 'Invalid plan type'], 400);
        }

        // Simulated payment processing
        $paymentStatus = 'success';

        if ($paymentStatus == 'success') {
            // ✅ Now, user_id exists, so this will work
            $paymentHistory = PaymentHistory::create([
                'user_id' => $user->id,
                'payment_method' => 'Credit Card',
                'amount' => $amount,
                'status' => $paymentStatus,
                'plan_type' => $planType,
            ]);

            return response()->json([
                'message' => 'Payment has been successfully processed.',
                'payment_status' => $paymentStatus,
                'payment_history' => $paymentHistory,
            ], 200);
        }

        return response()->json([
            'message' => 'Payment failed.',
            'payment_status' => 'failed',
        ], 400);
    }


}

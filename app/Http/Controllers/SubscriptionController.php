<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscriber;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{

    public function getPaymentDetailsForPlan($plan_id)
    {

    }

    public function orderPlaced(Request $req)
    {
        //Update Subscription table and Transaction table
    }

    public function subscribe(Request $request)
    {
        // Validate the request data
        $this->validate($request, [
            't_id' => 'required|exist:transactions,id',
            'user_id' => 'required|exists:users,id',
            'subscription_type' => 'required|in:Starter,Professional,Enterprise',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Create the subscription
        $subscription = Subscriber::create([
            'u_id' => $request->user_id,
            'subscription_type' => $request->subscription_type,
        ]);

        return response()->json(['message' => 'User subscribed successfully', 'data' => $subscription], 201);
    }

}

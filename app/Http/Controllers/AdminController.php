<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;

class AdminController extends Controller
{
    
    // public function getAllSubscriptions(Request $request)
    // {
    //     // Set default values for page and limit
    //     $page = $request->input('page', 1); // Default page 1
    //     $limit = $request->input('limit', 3); // Default 3 items per page

    //     // Fetch paginated results
    //     $subscriptions = Subscription::paginate($limit, ['*'], 'page', $page);

    //     // Append the 'limit' parameter to the pagination links
    //     $subscriptions->appends(['limit' => $limit]);

    //     return response()->json([
    //         "status" => "success",
    //         "message" => "Subscriptions fetched successfully",
    //         "data" => $subscriptions
    //     ]);
    // }

   public function getAllSubscriptions(Request $request){
        // Extract DataTables parameters from the request
        $draw = $request->input('draw', 1);
        $start = $request->input('start', 0);
        $length = $request->input('length', 2);
        $searchValue = $request->input('search.value', '');
        $orderColumnIndex = $request->input('order.0.column', 0); // Index of the column to sort
        $orderDirection = $request->input('order.0.dir', 'asc'); // Sort direction (asc/desc)

        // Define column mapping for sorting (should match table column names in database)
        $columns = [
            0 => 'subscription_id',
            1 => 't_id',
            2 => 'u_id',
            3 => 'plan_id',
            4 => 'expiry',
            5 => 'created_at',
        ];

        // Build the query
        $query = Subscription::query()
            ->join('transactions', 'subscriptions.t_id', '=', 'transactions.transaction_id')
            ->join('users', 'subscriptions.u_id', '=', 'users.id')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.plan_id')
            ->select(
                'subscriptions.subscription_id',
                'transactions.transaction_id',
                'users.name as user_name',
                'plans.name as plan_name',
                'subscriptions.expiry',
                'subscriptions.created_at'
            );

        // Apply search filter
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('transactions.transaction_id', 'like', "%{$searchValue}%")
                ->orWhere('users.name', 'like', "%{$searchValue}%")
                ->orWhere('plans.name', 'like', "%{$searchValue}%");
            });
        }

        // Apply sorting
        if (isset($columns[$orderColumnIndex])) {
            $query->orderBy($columns[$orderColumnIndex], $orderDirection);
        }

        // Get total records count before applying pagination
        $totalRecords = $query->count();

        // Apply pagination
        $subscriptions = $query->skip($start)->take($length)->get();

        // Prepare the response
        return response()->json([
            "draw" => $draw,
            "recordsTotal" => Subscription::count(), // Total records in the table
            "recordsFiltered" => $totalRecords, // Total records after filtering
            "data" => $subscriptions, // Paginated and filtered data
        ]);
    }


}
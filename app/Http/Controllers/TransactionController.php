<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Plan;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendSubscriptionEmail;
use App\Mail\SubscriptionMail;
use Illuminate\Support\Facades\Mail;


class TransactionController extends Controller
{
    public function createTransaction(Request $request)
    {
        try{
                // return response()->json($request);
                $this->validate($request,[
                    'user_id' => 'required|exists:users,id',
                    'payment_type' => 'required|in:UPI,Credit Card,Bank Transfer',
                    'plan_id' => 'required|exists:plans,plan_id',
                    'payment_option_details' => [
                        'required',
                        'json',
                        function ($attribute, $value, $fail) {
                            // Decode the JSON string
                            $decoded = json_decode($value, true);
                
                            // Check if decoding failed or the required key is missing
                            if (!is_array($decoded) || !isset($decoded['validity'])) {
                                return $fail('The ' . $attribute . ' must be a valid JSON object containing a "validity" property.');
                            }
                
                            // Optional: Validate the "validity" property (if it should be specific values)
                            if (!in_array($decoded['validity'], ['monthly', 'yearly'])) {
                                return $fail('The "validity" property in ' . $attribute . ' must be either "monthly" or "yearly".');
                            }
                        }
                    ]
                ]);
    
                $user_id = $request->input('user_id');
                $payment_type = $request->input('payment_type');
                $plan_id = $request->input('plan_id');
                $payment_option_details = $request->input('payment_option_details');
                
                $transaction = Transaction::create([
                    'user_id' => $user_id,
                    'payment_type' => $payment_type, 
                    'plan_id' => $plan_id, 
                    'payment_option_details' => $payment_option_details
                ]);
    
                if($transaction)
                {
                    // $plan_details = DB::select("select * from plans where plan_id = ?",[$transaction->plan_id]);
                    $plan_details = Plan::find($transaction->plan_id);
    
                    // Decode the JSON string to an associative array
                    $paymentOptionDetails = json_decode($transaction->payment_option_details, true);
    
                    if(is_array($paymentOptionDetails))
                    {
                        $validity = $paymentOptionDetails['validity']; // it will contain 'monthly' or 'yearly'
    
                        if($validity === 'monthly')
                        {
                            // Calculate the expiry date by adding `$validity` months to the current date
                            $expiryDate = Carbon::now()->addMonths(1)->toDateString(); // Format as YYYY-MM-DD   
                        }
                        else
                        {
                            // Calculate the expiry date by adding `$validity` months to the current date
                            $expiryDate = Carbon::now()->addMonths(12)->toDateString(); // Format as YYYY-MM-DD
                        }
                    }
    
    
                    $subscription = Subscription::create([
                        't_id' => $transaction->transaction_id,
                        'u_id' => $transaction->user_id,
                        'plan_id' => $transaction->plan_id,
                        'expiry' => $expiryDate
                    ]);

                    if($subscription)
                    {
                        $user = User::find($transaction->user_id);

                        $details = [ 
                            'title' => 'User Subscribed', 
                            'body' => 'User Subscribed Successfully to '.$plan_details->name,
                            'email' => $user->email
                        ];

                        
                        // Mail::to($user->email)->send(new WelcomeEmail($details));
                        dispatch(new SendSubscriptionEmail($details));

                        return response()->json([
                            'status' => 'success', 
                            'message' => 'User subscribed successfully',
                            'data' => [
                                'transaction' => $transaction,
                                'subscription' => $subscription,
                            ]
                        ], 201);
                    }
                    else{
                        return response()->json([
                            'status' => 'error',
                            'message' => ' Transaction created but failed to create subscription',
                            'data' => [
                                'transaction' => $transaction
                            ],
                        ], 500);
                    }
    
                }else
                {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to create transaction',
                        'data' => null,
                    ], 500);
                }
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        }catch(Exception $e){
            // Log the exception for debugging
            Log::error('Error in createTransaction: ' . $e->getMessage());

            // Return a generic error response
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}

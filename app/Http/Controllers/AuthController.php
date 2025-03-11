<?php

		namespace App\Http\Controllers;

		use Illuminate\Support\Facades\Auth;
		use Tymon\JWTAuth\Facades\JWTAuth;
		use App\Http\Controllers\Controller;
		use Illuminate\Http\Request;
		use Illuminate\Support\Facades\Hash;
		use App\Models\User;
		use App\Mail\WelcomeEmail; 
		use Illuminate\Support\Facades\Mail;
		use App\Jobs\SendWelcomeEmail;
		use Illuminate\Support\Facades\DB;
		use Illuminate\Support\Facades\Log;

		class AuthController extends Controller
		{
   
    			public function __construct()
    			{}

   			    public function register(Request $req)
    			{

        			try{
							$this->validate($req, [
							'name' => 'required|string',
							'email' => 'required|unique:users,email',
							'mobile' => 'required|digits:10|unique:users,mobile',
							'password' => 'required|confirmed'
							],[
								'email.unique' => 'The email address is already registered.',
								'mobile.unique' => 'The mobile number is already registered.',
							]);
	
							$name = $req->input('name');
							$email = $req->input('email');	
							$mobile = $req->input('mobile');
							$password  = Hash::make($req->input('password'));
	
								$user = User::create(['name'=>$name, 'email'=>$email, 'mobile'=>$mobile, 'password'=>$password]);
	
							if($user)
							{
								$details = [ 
									'title' => 'Welcome to User Subscription Management System', 
									'body' => $user->name.', your account is successfully created in User Subscription System.' ,
									'email' => $user->email
								];
	
								try{
									// Mail::to($user->email)->send(new WelcomeEmail($details));
									dispatch(new SendWelcomeEmail($details));
								}catch (\Exception $e) {
									Log::error("Failed to send welcome email: " . $e->getMessage());
									return response()->json([
										'status' => 'error', 
										'message' => 'User created but email could not be sent',
										'data' => null
									],500);
								}
								
								return response()->json([
									'status' => 'success', 
									'message' => 'User created successfully',
									'data' => [
										'email' => $user->email
									]
								], 201);						
							}else
							{
								return response()->json([
									'status' => 'error', 
									'message' => 'Could not create user',
									'data' => null
								],500);
							}
					}catch (\Illuminate\Validation\ValidationException $e) {
						return response()->json([
							'status' => 'error',
							'message' => 'Validation error',
							'errors' => $e->errors(),
						], 422);
					}catch (\Exception $e) {
						Log::error("Registration error: " . $e->getMessage());
						return response()->json([
							'status' => 'error',
							'message' => 'Could not create user',
							'data' => null
						], 500);
					}

    			}
				
				public function login(Request $req)
    			{
					try{
						$email = $req->input('email');
						$user = User::where('email', $email)->first();
						if(!$user)
						{
							return response()->json([
								'status' => 'error', 
								'message' => 'User not found',
								'data' => null
							], 404);
						}
						else
						{
							$user_id = DB::select('select id from users where email = ?',[$email]);
							$userId = $user_id[0]->id;
							$subscription = DB::select('select plan_id, expiry from subscriptions where u_id = ?',[$userId]);
						}
						
        				$credentials = $req->only(['email', 'password']);

						// Attempt to verify the credentials and create an access token
						if (!$accessToken = JWTAuth::claims(['token_type' => 'access','email' => $email,'subscription' => $subscription ])->attempt($credentials)) {
							return response()->json([
								'status' => 'error', 
								'message' => [
									'code' => '401',
									'msg' => 'Invalid credentials or token generation failed'
								],
								'data' => null
							], 401);
						}
				
						// Generate a refresh token (could be longer-lived and distinct from the access token)
						$user = JWTAuth::user();
						$refreshToken = JWTAuth::claims(['token_type' => 'refresh'])->fromUser($user);  // Using a separate token for refreshing

				
						// Return both tokens
						return response()->json([
							'status' => 'success', 
							'message' => "Token created successfully",
							'data' => [
								'access_token' => $accessToken,
								'refresh_token' => $refreshToken,
								'token_type' => 'bearer',
								'expires_in' => auth('api')->factory()->getTTL() * 60  // Time in seconds when token expires
							]
						], 201);
					}catch (JWTException $e) {
						return response()->json([
							'status' => 'error', 
							'message' => 'could not create token',
							'data' => null
						], 500);
					}catch (\Exception $e) {
						Log::error("Login error: " . $e->getMessage());
						return response()->json([
							'status' => 'error',
							'message' => 'An error occurred during login',
							'data' => null
						], 500);
					}
    			}

				public function me()
    			{
					try{
						return response()->json([
							"status" => "success",
							"message" => "User retrieved successfully.",
							"data" => [auth()->user()]
						],200);
					}catch (\Exception $e) {
						Log::error("User retrieval error: " . $e->getMessage());
						return response()->json([
							'status' => 'error',
							'message' => 'An error occurred while retrieving user details',
							'data' => null
						], 500);
					}
    			}

				public function logout()
    			{
        			try{
						auth()->logout();

        				return response()->json([
							"status" => "success",
							"message" => "User logged out successfully.",
							"data" => null
						],204);
					}catch (\Exception $e) {
						Log::error("Logout error: " . $e->getMessage());
						return response()->json([
							'status' => 'error',
							'message' => 'An error occurred while logging out',
							'data' => null
						], 500);
					}
    			}

				public function refresh()
    			{		
						try {

							if(!JWTAuth::getToken())
							{
								return response()->json([
									'status' => 'error', 
									'message' => 'Token is required',
									'data' => null
								], 400);
							}
							
							$token = JWTAuth::getPayload(JWTAuth::getToken());
							if ($token->get('token_type') !== 'refresh') {
								return response()->json([
									'status' => 'error', 
									'message' => 'Only refresh tokens can be used here',
									'data' => null
								], 403);
							}
					
							
							// Generate a new access token from the refresh token
							$newAccessToken = JWTAuth::claims(['token_type' => 'access'])->fromUser(auth()->user());
							
					
							return response()->json([
								'new_access_token' => $newAccessToken,
								'token_type' => 'bearer',
								'expires_in' => auth()->factory()->getTTL() * 60
							]);
					
						} catch (JWTException $e) {
							return response()->json([
								'status' => 'error', 
								'message' => 'Could not refresh token',
								'data' => null
							], 500);
						}catch (\Exception $e) {
							Log::error("Token refresh error: " . $e->getMessage());
							return response()->json([
								'status' => 'error',
								'message' => 'An error occurred while refreshing token',
								'data' => null
							], 500);
						}
    			}
		}

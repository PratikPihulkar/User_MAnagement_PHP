<?php
			return [
   				 'defaults' => [
        					'guard' => 'api',
        					'passwords' => 'users',
    				],
    				'guards' => [
        					'api' => [
            					'driver' => 'jwt',  // or 'jwt' for JWT auth
            					'provider' => 'users',
        					],
    				],
   				 'providers' => [
       					 'users' => [
            					'driver' => 'eloquent',
            					'model' => App\Models\User::class,
        					],
    				],
			];
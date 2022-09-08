<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

use Carbon\Carbon;

use App\Mail\RegisterMail;

use App\Models\User;

class UserController extends RootController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * @OA\Post(
     *     path="/api/v1/user/register",
     *     summary="Register",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="first_name",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="last_name",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="country_code",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="mobile_number",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="password_confirmation",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="source",
     *                     type="string",
     *                     enum={"signup", "forgot_password"}
     *                 ),
     *                 example={
     *                      "first_name":"abc"
     *                      ,"last_name":"I S"
     *                      ,"email":"abc.is@abc.com"
     *                      ,"country_code":"91"
     *                      ,"mobile_number":"9495542"
     *                      ,"password":123456
     *                      ,"password_confirmation":12345
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="X-Localization",
     *         in="header",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="en",
     *             enum={"en"},
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Unprocessable Entity"),
     * )
     */
    /**
     * Register new user and send otp
     *
     * @param Request $request
     * @return void
     */
    public function register(Request $request)
    {
        #validate record
        $rules = [
            'first_name' => 'required|max:60',
            'last_name' => 'required|max:60',
            'email' => 'required|email:rfc,dns|unique:users',
            'country_code' => 'digits_between:1,5',
            'mobile_number' => 'digits_between:5,15',
            'password' => 'required|confirmed|min:6|max:16',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->apiResponse([], $validator->errors(), 422);
        }
        // if exists then generate 6 digits otp, save into DB, and send the same
        $otp = mt_rand(100000, 999999);
        $otpExpireTimeInSeconds = env('SITE_OTP_EXPIRE_TIME_IN_SECONDS', '180');

        #insert user record
        $user = new User;

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->country_code = $request->country_code ?? null;
        $user->mobile_number = $request->mobile_number ?? null;
        $user->password = app('hash')->make($request->password);
        $user->status = 1;

        $user->otp = $otp; // add OTP
        $user->otp_expire_on = Carbon::now()->addSeconds($otpExpireTimeInSeconds); // add OTP expire date time

        $user->save();

        if (app()->environment(['local', 'testing'])) {
            $response['otp'] =  (string) $user->otp;
        }
        if (!app()->environment(['testing'])) {
            Mail::mailer('smtp')->to($user->email)->send(new RegisterMail($user));
        }

        $response['otp_expire_duration_in_seconds'] =  $otpExpireTimeInSeconds;
        return $this->apiResponse($response, "We have send otp to your email. Please check it.", 201);
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/user/verify-email",
     *     summary="Verify email",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="email",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="otp",
     *                     type="string"
     *                 ),
     *                 example={"email":"abc.is@abc.com","otp":"992248"}
     *             )
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="X-Localization",
     *         in="header",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="en",
     *             enum={"en"},
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=409, description="Conflict"),
     *     @OA\Response(response=422, description="Unprocessable Entity"),
     * )
     */
    /**
     * Verify email address
     *
     * @param Request $request
     * @return void
     */
    public function verifyEmail(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'email' => 'required|email:rfc,dns|exists:users,email',
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse([], $validator->errors(), 422);
        }

        // check email & otp is exists
        $user = User::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('status', 1)
            ->first();

        if (!$user) {
            // if user not exits show 404
            return $this->apiResponse([], "OTP is wrong. Please try again.", 404);
        } elseif ($user->email_verified_at !== null) {
            return $this->apiResponse([], "You have already verified your email address.", 409);
        } else {
            if (Carbon::now()->lte($user->otp_expire_on)) {
                if ($user->email_verified_at === null) {
                    # if email is not verified then mark as verified
                    $user->email_verified_at = Carbon::now();
                    $user->save();
                }
                return $this->apiResponse([], "You have verified your email address.", 200);
            } else {
                return $this->apiResponse([], "OTP is expired.", 409);
            }
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/user/resend-otp",
     *     summary="Resend otp while register",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="email",
     *                     type="string"
     *                 ),
     *                 example={"email":"abc.is@abc.com"}
     *             )
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="X-Localization",
     *         in="header",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="en",
     *             enum={"en"},
     *         )
     *     ),
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=409, description="Conflict"),
     *     @OA\Response(response=422, description="Unprocessable Entity"),
     * )
     */
    /**
     * Resending OTP to email address
     *
     * @param Request $request
     * @return void
     */
    public function resendOtp(Request $request)
    {
        #validate record
        $rules = [
            'email' => 'required|email:rfc,dns|exists:users,email'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->apiResponse([], $validator->errors(), 422);
        }

        $user = User::where('email', $request->email)
            ->where('status', 1)
            ->first();

        if (!$user) {
            return $this->apiResponse([], "Your account is not active. Please contact admin.", 404);
        } elseif ($user->email_verified_at !== null) {
            return $this->apiResponse([], "You have already verified your email address.", 409);
        } else {
            // if exists then generate 6 digits otp, save into DB, and send the same
            $otp = mt_rand(100000, 999999);
            $otpExpireTimeInSeconds = env('SITE_OTP_EXPIRE_TIME_IN_SECONDS', '180');

            $user->otp = $otp; // add OTP
            $user->otp_expire_on = Carbon::now()->addSeconds($otpExpireTimeInSeconds); // add OTP expire date time

            $user->save();

            if (app()->environment(['local', 'testing'])) {
                $response['otp'] =  (string) $user->otp;
            }

            if (!app()->environment(['testing'])) {
                Mail::mailer('smtp')->to($user->email)->send(new RegisterMail($user));
            }

            $response['otp_expire_duration_in_seconds'] =  $otpExpireTimeInSeconds;
            return $this->apiResponse($response, "We have send otp to your email. Please check it.", 200);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/user/login",
     *     summary="Login",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="email",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string"
     *                 ),
     *                 example={"email":"abc.is@abc.com", "password":123456}
     *             )
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="X-Localization",
     *         in="header",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="en",
     *             enum={"en"},
     *         )
     *     ),
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=409, description="Conflict"),
     *     @OA\Response(response=422, description="Unprocessable Entity"),
     * )
     */
    /**
     * login for user
     *
     * @param Request $request
     * @return void
     */
    public function login(Request $request)
    {
        #validate record
        $rules = [
            'email' => 'required|email:rfc,dns',
            'password' => 'required|min:6|max:16',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->apiResponse([], $validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->apiResponse([], "Your email is wrong. Please try again.", 404);
        } elseif ($user->status === 0) {
            return $this->apiResponse([], "Your account is not active. Please contact admin.", 404);
        } elseif ($user->email_verified_at === null) {
            return $this->apiResponse([], "Your email is not verified. Please verify.", 409);
        } elseif (!$token = auth()->attempt($request->all())) {
            return $this->apiResponse([], "Your credentials are wrong. Please try again.", 403);
        } else {
            $response["token"] = $token;
            $response["token_type"] = 'bearer';
            $response["expires_in"] = auth()->factory()->getTTL() * 60;
            $response["user"] = $user;
            return $this->apiResponse($response, "You have successfully logged in.", 200);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user/profile",
     *     summary="Profile",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function profile()
    {
        $response = ["status_code" => 0, "message" => "", "data" => null];
        $user = auth('api')->user();
        return $this->apiResponse($user, '', 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/user/change-password",
     *     summary="Send otp for changing password",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="email",
     *                     type="string"
     *                 ),
     *                 example={"email":"abc.is@abc.com"}
     *             )
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="X-Localization",
     *         in="header",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="en",
     *             enum={"en"},
     *         )
     *     ),
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=409, description="Conflict"),
     *     @OA\Response(response=422, description="Unprocessable Entity"),
     * )
     */
    /**
     * @OA\Patch(
     *     path="/api/v1/user/change-password",
     *     summary="Change Password",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="email",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="otp",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="password_confirmation",
     *                     type="string"
     *                 ),
     *                 example={"email":"abc.is@abc.com","otp":"123456","password":123456,"password_confirmation":12345}
     *             )
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="X-Localization",
     *         in="header",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="en",
     *             enum={"en"},
     *         )
     *     ),
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=409, description="Conflict"),
     *     @OA\Response(response=422, description="Unprocessable Entity"),
     * )
     */
    /**
     * Resending OTP to email address
     *
     * @param Request $request
     * @return void
     */
    public function changePassword(Request $request)
    {
        if ($request->isMethod('post')) {
            #validate record
            $rules = [
                'email' => 'required|email:rfc,dns'
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->apiResponse([], $validator->errors(), 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return $this->apiResponse([], "User not found.", 404);
            } elseif ($user->status === 0) {
                return $this->apiResponse([], "Your account is not active. Please contact admin.", 409);
            } elseif ($user->email_verified_at === null) {
                return $this->apiResponse([], "Please verify your Email Address.", 409);
            } else {
                // sending OTP
                $otp = mt_rand(100000, 999999);
                $otpExpireTimeInSeconds = env('SITE_OTP_EXPIRE_TIME_IN_SECONDS', '180');

                $user->otp = $otp; // add OTP
                $user->otp_expire_on = Carbon::now()->addSeconds($otpExpireTimeInSeconds); // add OTP expire date time
                $user->save();

                if (app()->environment(['testing'])) {
                    $response['otp'] =  (string) $user->otp;
                } else {
                    Mail::mailer('smtp')->to($user->email)->send(new RegisterMail($user));
                }
                $response['otp_expire_duration_in_seconds'] =  $otpExpireTimeInSeconds;
                return $this->apiResponse($response, "We have send otp to your email. Please check it.", 200);
            }
        } elseif ($request->isMethod('patch')) {
            #validate record
            $rules = [
                'email' => 'required|email:rfc,dns|exists:users,email',
                'otp' => 'required|digits:6',
                'password' => 'required|confirmed|min:6|max:16',
            ];

            // Update password
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $response["status_code"] = 422;
                $response["message"] = $validator->errors();
                return $this->apiResponse([], $validator->errors(), 422);
            }
            $user = User::where('email', $request->email)
                ->where('otp', $request->otp)
                ->first();

            if (!$user) {
                // if user not exits show 404
                return $this->apiResponse([], "OTP is wrong. Please try again.", 404);
            } elseif ($user->status === 0) {
                return $this->apiResponse([], "Your account is not active. Please contact admin.", 403);
            } elseif ($user->email_verified_at === null) {
                return $this->apiResponse([], "Please verify your email address.", 409);
            } else {
                $user->password = app('hash')->make($request->password);
                $user->save();

                return $this->apiResponse([], "We have successfully changed your password.", 200);
            }
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/user/update-profile",
     *     summary="Update profile",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="first_name",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="last_name",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="country_code",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="mobile_number",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="old_password",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="password_confirmation",
     *                     type="string"
     *                 ),
     *
     *                 example={"first_name":"Nicholas"
     *                          , "last_name":"Saputra"
     *                          ,"country_code":"91"
     *                          ,"mobile_number":"91"
     *                          ,"old_password":"password"
     *                          ,"password":"123456"
     *                          ,"password_confirmation":"12345"
     *                          }
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(response=422, description="Unprocessable Entity"),
     * )
     */
    /**
     * Update User
     *
     * @param Type $var
     * @return void
     */
    public function updateProfile(Request $request)
    {
        $user = User::find(auth('api')->user()->id);

        if (!$user) {
            return $this->apiResponse([], "User not found", 404);
        }

        #validate record
        $rules = [
            'first_name' => 'required|max:60',
            'last_name' => 'required|max:60',
            'country_code' => 'digits_between:1,5',
            'mobile_number' => 'digits_between:5,15',
            'old_password' => 'required',
            'password' => 'confirmed|min:6|max:16',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->apiResponse([], $validator->errors(), 422);
        }
        if (!app('hash')->check($request->old_password, $user->password)) {
            return $this->apiResponse([], "Please enter valid password", 422);
        }

        # update user record
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->country_code = $request->country_code ?? null;
        $user->mobile_number = $request->mobile_number ?? null;
        if (!empty($request->password)) {
            $user->password = app('hash')->make($request->password);
        }
        $user->updated_by = auth('api')->user()->id;
        $user->save();

        return $this->apiResponse($user, "Updated successfully", 200);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use App\Models\UserDevice;

class UserDeviceController extends RootController
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
     *     path="/api/v1/user-device/store",
     *     summary="store device information",
     *     tags={"User Devices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="app_version",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="fcm_token",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="device_id",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="os_type",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="phone_version",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="phone_model",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="source",
     *                     type="string",
     *                     enum={"signup", "forgot_password"}
     *                 ),
     *                 example={"app_version":"1.0"
     * ,"fcm_token":"e7jnjl2ce0eRhy8P7pVlnk:APA91bHV-KnnfexN7CX-QO17NbUmjqF0StY200FV5-gOs-VnlGW72fKfickOXw30N84Kl3ut7J9wqsFGVuJbghnaz_9I8bKpSNc_Syujyr378bI29_FdvHnqyyGKGLQavMDvBvZi6Kyv"
     * ,"device_id":"530EF2C5-729A-4DE4-B75A-7F524DFFEAD3"
     * ,"os_type":"1"
     * ,"phone_version":"14.2"
     * ,"phone_model":"iPhone XS Max"}
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
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function store(Request $request)
    {
        $input = $request->only([
            'app_version', 'device_id', 'os_type', 'phone_version', 'phone_model', 'fcm_token'
        ]);

        UserDevice::updateOrCreate(
            $input,
            ['user_id' => auth('api')->user()->id]
        );
        return $this->apiResponse([], "User device added successfully.", 200);
    }
}

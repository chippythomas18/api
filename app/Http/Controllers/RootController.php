<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;

class RootController extends BaseController
{
    protected function apiResponse($response = [], $message = "", $error_code = 200)
    {

        switch ($error_code) {
            case 400:
                if (array_key_exists('message', $response)) {
                    $message = $response["message"];
                }
                if ($message == "") {
                    $message = config('constants.ERROR_MSG_VALIDATION');
                }
                $success = 'false';
                break;

            case 401:
                if ($message == "") {
                    $message = config('constants.ERROR_MSG_UNAUTH');
                }
                $success = 'false';
                break;

            case 201:
                if ($message == "") {
                    $message = config('constants.SUCCESS_MSG_REG');
                }
                $success = 'success';
                break;

            case 200:
                if ($message == "") {
                    $message = config('constants.SUCCESS_MSG_OK');
                }
                $success = 'true';
                break;

            case 500:
                if ($message == "") {
                    $message = config('constants.ERROR_MSG_INTERNAL');
                }
                $success = 'false';
                break;

            default:
                if ($message == "") {
                    $message = config('constants.ERROR_MSG_INTERNAL');
                }
                $success = 'false';
                break;
        }
        return response()->json(
            [
                'success' => $success,
                'message' => $message,
                'data' => $response,
            ],
            $error_code
        );
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json(
            [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => Auth::factory()->getTTL() * 60,
            ]
        );
    }

    /**
     * Check permission
     * @return \Illuminate\Http\JsonResponse
     */
    protected function checkPermission($request, array $permissionList)
    {
        $user = Auth::guard('api')->user();
        if ($user) {
            $fullPermission = DB::table('userroles')->select('modules.name as name')
                ->join('rolepermissions', 'rolepermissions.role_id', '=', 'userroles.role_id')
                ->join('modules', 'modules.id', '=', 'rolepermissions.module_id')
                ->where(['userroles.user_id' => $user->id])
                ->whereIn('modules.name', $permissionList)
                ->get()->pluck('name')->toArray();
            if (!empty($fullPermission) && count($fullPermission) > 0) {
                return $fullPermission;
            }
            response()->json(
                [
                    'success' => "false",
                    'message' => "Permission denied :(",
                    'data' => [],
                ],
                403
            )->send();
            die();
        }
    }
}

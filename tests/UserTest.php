<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

use App\Models\User;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    protected $prefix = '/api/v1/';
    protected $url = '/api/v1/user/';

    private function register()
    {
        $formData = [
            "first_name" => "abc",
            "last_name" => "I S",
            "email" => "abc.is@abc.com",
            "password" => 123456,
            "password_confirmation" => 123456
        ];

        $response = $this->json('POST', $this->url . 'register', $formData)
            ->seeStatusCode(201)
            ->response
            ->getContent();

        $response = json_decode($response, true);

        return $response;
    }

    private function login()
    {
        $response = $this->register();
        $formData = [
            "email" => "abc.is@abc.com",
            "otp" => $response['data']['otp'],
        ];

        $response = $this->json('PATCH', $this->url . 'verify-email', $formData)
            ->seeStatusCode(200);
        $formData = [
            "email" => "abc.is@abc.com",
            "password" => "123456",
        ];
        $response = $this->json('POST', $this->url . 'login', $formData)
            ->seeStatusCode(200)
            ->response
            ->getContent();

        return $response;
    }

    public function testRegisterValidation()
    {
        $formData = [];

        $response = $this->json('POST', $this->url . 'register', $formData)
            ->seeStatusCode(422)
            ->seeJsonStructure(['message' => ['first_name', 'last_name', 'email', 'password']]);
    }

    public function testRegister()
    {
        $formData = [
            "first_name" => "abc",
            "last_name" => "I S",
            "email" => "abc.is@abc.com",
            "password" => 123456,
            "password_confirmation" => 123456
        ];

        $response = $this->json('POST', $this->url . 'register', $formData)
            ->seeStatusCode(201);
    }
    public function testVerifyEmailValidation()
    {
        $formData = [];

        $response = $this->json('PATCH', $this->url . 'verify-email', $formData)
            ->seeStatusCode(422);
    }

    public function testVerifyEmail()
    {
        $response = $this->register();

        $formData = [
            "email" => "abc.is@abc.com",
            "otp" => $response['data']['otp'],
        ];

        $response = $this->json('PATCH', $this->url . 'verify-email', $formData)
            ->seeStatusCode(200);
    }

    public function testResendOtpValidation()
    {
        $formData = [];

        $response = $this->json('POST', $this->url . 'resend-otp', $formData)
            ->seeStatusCode(422);
    }
    public function testResendOtp()
    {
        $this->register();
        $formData = [
            "email" => "abc.is@abc.com"
        ];

        $response = $this->json('POST', $this->url . 'resend-otp', $formData)
            ->seeStatusCode(200);
    }

    public function testChangePasswordSendOtpValidation()
    {
        $formData = [];
        $response = $this->json('POST', $this->url . 'change-password', $formData)
            ->seeStatusCode(422)
            ->seeJsonStructure(['message' => ['email']]);
    }
    public function testChangePasswordSendOtp()
    {
        $response = $this->register();

        $formData = [
            "email" => "abc.is@abc.com",
            "otp" => $response['data']['otp'],
        ];

        $response = $this->json('PATCH', $this->url . 'verify-email', $formData)
            ->seeStatusCode(200);

        $formData = [
            "email" => "abc.is@abc.com",
        ];
        $response = $this->json('POST', $this->url . 'change-password', $formData)
            ->seeStatusCode(200);
    }
    public function testChangePasswordValidation()
    {

        $formData = [];
        $response = $this->json('PATCH', $this->url . 'change-password', $formData)
            ->seeStatusCode(422)
            ->seeJsonStructure(['message' => ['email', 'password', 'otp']]);
    }
    public function testChangePassword()
    {
        $response = $this->register();

        $formData = [
            "email" => "abc.is@abc.com",
            "otp" => $response['data']['otp'],
        ];

        $response = $this->json('PATCH', $this->url . 'verify-email', $formData)
            ->seeStatusCode(200);

        $formData = [
            "email" => "abc.is@abc.com",
        ];
        $response = $this->json('POST', $this->url . 'change-password', $formData)
            ->seeStatusCode(200)
            ->response
            ->getContent();

        $response = json_decode($response, true);

        $formData = [
            "email" => "abc.is@abc.com",
            "otp" => $response['data']['otp'],
            "password" => "abcdef",
            "password_confirmation" => "abcdef"
        ];

        $response = $this->json('PATCH', $this->url . 'change-password', $formData)
            ->seeStatusCode(200);
    }
    public function testChangePasswordValidationPassword()
    {
        $response = $this->register();
        $formData = [
            "email" => "abc.is@abc.com",
            "otp" => $response['data']['otp'],
        ];

        $response = $this->json('PATCH', $this->url . 'verify-email', $formData)
            ->seeStatusCode(200);

        $formData = [
            "email" => "abc.is@abc.com",
        ];
        $response = $this->json('POST', $this->url . 'change-password', $formData)
            ->seeStatusCode(200)
            ->response
            ->getContent();

        $response = json_decode($response, true);

        $formData = [
            "email" => "abc.is1@abc.com",
            "otp" => "",
            "password" => "cdef",
            "password_confirmation" => "abcde"
        ];

        $response = $this->json('PATCH', $this->url . 'change-password', $formData)
            ->seeStatusCode(422)
            ->seeJsonStructure(['message' => ['email', 'password', 'otp']]);
    }

    public function testChangePasswordForNotVerifiedEmail()
    {
        $response = $this->register();

        $formData = [
            "email" => "abc.is@abc.com",
        ];
        $this->json('POST', $this->url . 'change-password', $formData)
            ->seeStatusCode(409);

        $formData = [
            "email" => "abc.is@abc.com",
            "otp" => $response['data']['otp'],
            "password" => "abcdef",
            "password_confirmation" => "abcdef"
        ];

        $response = $this->json('POST', $this->url . 'change-password', $formData)
            ->seeStatusCode(409);
    }

    public function testLogin()
    {
        $this->testVerifyEmail();
        $formData = [
            "email" => "abc.is@abc.com",
            "password" => "123456",
        ];

        $this->json('POST', $this->url . 'login', $formData)
            ->seeStatusCode(200)
            ->seeJsonStructure(['data' => ['user']]);
    }

    public function testLoginValidation()
    {
        $this->testVerifyEmail();
        $formData = [];

        $this->json('POST', $this->url . 'login', $formData)
            ->seeStatusCode(422)
            ->seeJsonStructure(['message' => ['email', 'password']]);
    }

    public function testLoginValidationEmailNotVerified()
    {
        $response = $this->register();
        $formData = [
            "email" => "abc.is@abc.com",
            "password" => "123456",
        ];

        $this->json('POST', $this->url . 'login', $formData)
            ->seeStatusCode(409);
    }

    public function testProfile()
    {
        $login = $this->login();
        $login = json_decode($login, true);

        $this->json('GET', $this->url . 'profile', [], ["Authorization" =>  "Bearer " . $login['data']['token']])
        ->seeStatusCode(200)
        ->seeJsonStructure(['data' => ['id', 'first_name', 'last_name', 'email']]);
    }

    public function testProfileUnauthorized()
    {
        $this->json('GET', $this->url . 'profile', [], [])
        ->seeStatusCode(401);
    }

    public function testUpdateProfile()
    {
        $login = $this->login();
        $login = json_decode($login, true);

        $formData = [
            "first_name" => "abc",
            "last_name" => "I S",
            "old_password" => 123456,
            "password" => 123456,
            "password_confirmation" => 123456
        ];
        $x=$this->json('PUT', $this->url . 'update-profile', $formData, ["Authorization" =>  "Bearer " . $login['data']['token']])
        ->seeStatusCode(200)
        ->seeJsonStructure(['data' => ['id', 'first_name', 'last_name', 'email']]);
    }

    public function testUpdateProfileUnauthorized()
    {

        $formData = [
            "first_name" => "abc",
            "last_name" => "I S",
            "old_password" => 123456,
            "password" => 123456,
            "password_confirmation" => 123456
        ];
        $response = $this->json('PUT', $this->url . 'update-profile', $formData, [])
        ->seeStatusCode(401);
    }

    public function testUpdateProfileValidation()
    {
        $login = $this->login();
        $login = json_decode($login, true);

        $formData = [];
        $this->json('PUT', $this->url . 'update-profile', $formData, ["Authorization" =>  "Bearer " . $login['data']['token']])
        ->seeStatusCode(422)
        ->seeJsonStructure(['message' => ['first_name', 'last_name', 'old_password']]);
    }

    public function testUpdateProfileValidationPassword()
    {
        $login = $this->login();
        $login = json_decode($login, true);

        $formData = [
            "first_name" => "abc",
            "last_name" => "I S",
            "old_password" => 123456,
            "password" => 1234567,
            "password_confirmation" => 123456
        ];
        $this->json('PUT', $this->url . 'update-profile', $formData, ["Authorization" =>  "Bearer " . $login['data']['token']])
        ->seeStatusCode(422)
        ->seeJsonStructure(['message' => ['password']]);
    }
}

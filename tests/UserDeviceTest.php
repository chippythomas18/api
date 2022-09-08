<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class UserDeviceTest extends TestCase
{
    use DatabaseTransactions;

    protected $prefix = '/api/v1/';
    protected $url = '/api/v1/user-device/';

    private function register()
    {
        $formData = [
            "first_name" => "abc",
            "last_name" => "I S",
            "email" => "abc.is@abc.com",
            "password" => 123456,
            "password_confirmation" => 123456
        ];

        $response = $this->json('POST', $this->prefix . 'user/register', $formData)
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

        $response = $this->json('PATCH', $this->prefix . 'user/verify-email', $formData)
            ->seeStatusCode(200);
        $formData = [
            "email" => "abc.is@abc.com",
            "password" => "123456",
        ];
        $response = $this->json('POST', $this->prefix . 'user/login', $formData)
            ->seeStatusCode(200)
            ->response
            ->getContent();

        return $response;
    }
  
    public function testStore()
    {
        $login = $this->login();
        $login = json_decode($login, true);

        $formData = [
            "app_version" => "1.0", "fcm_token" => "e7jnjl2ce0eRhy8P7pVlnk:APA91bHV-KnnfexN7CX-QO17NbUmjqF0StY200FV5-gOs-VnlGW72fKfickOXw30N84Kl3ut7J9wqsFGVuJbghnaz_9I8bKpSNc_Syujyr378bI29_FdvHnqyyGKGLQavMDvBvZi6Kyv", "device_id" => "530EF2C5-729A-4DE4-B75A-7F524DFFEAD3", "os_type" => "1", "phone_version" => "14.2", "phone_model" => "iPhone XS Max"
        ];
        $this->json('POST', $this->url . 'store', $formData, ["Authorization" =>  "Bearer " . $login['data']['token']])
            ->seeStatusCode(200);
    }
    public function testStoreUnauthorized()
    {
        $formData = [
            "app_version" => "1.0", "fcm_token" => "e7jnjl2ce0eRhy8P7pVlnk:APA91bHV-KnnfexN7CX-QO17NbUmjqF0StY200FV5-gOs-VnlGW72fKfickOXw30N84Kl3ut7J9wqsFGVuJbghnaz_9I8bKpSNc_Syujyr378bI29_FdvHnqyyGKGLQavMDvBvZi6Kyv", "device_id" => "530EF2C5-729A-4DE4-B75A-7F524DFFEAD3", "os_type" => "1", "phone_version" => "14.2", "phone_model" => "iPhone XS Max"
        ];
        $this->json('POST', $this->url . 'store', $formData, [])
            ->seeStatusCode(401);
    }
}

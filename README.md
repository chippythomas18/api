# Open Source API 

This is [lumen](https://lumen.laravel.com/) based application. 
* The environment variables are local, testing, staging, production. You can set `APP_ENV` in env file
* While development, you can set `APP_DEBUG` as true in env file. Please revert the same before move to production.


## Server Requirements
* PHP >= 7.3
* OpenSSL PHP Extension
* PDO PHP Extension
* Mbstring PHP Extension
* Curl PHP Extension
* Composer
* Git

## Docker
After installed docker, then pull the latest code from repository and run `docker-compose up -d` for project running.

* Run `docker-compose up -d` to up the server
* Run `docker-compose down` to down the server
* Run `docker-compose build` to re-build server 

## Installing & Configuration
* Run `composer install` to install lumen & other packages


## Database: Migrations
To run all migrations, execute the migrate Artisan command
`php artisan migrate`

## Database: Seeders
`php artisan db:seed`

## Database: Migrations & Seeders
This command is used for completely re-building database:

`php artisan migrate:fresh --seed`

## Generate JWT secret key

Run `php artisan jwt:secret` This will update your .env file with something like JWT_SECRET=foobar
[more info](https://jwt-auth.readthedocs.io/en/develop/lumen-installation/)

## Phpcs
`./vendor/bin/phpcs`

## Testing
* Give absolute path at `DB_DATABASE` in .env.testing file
* migrate and seed the test database: `php artisan --env=testing migrate:fresh --seed`
* Run the command `vendor/bin/phpunit`

## HTTP Response

Sample json response from server
```sh
{
	"status_code": 200 // HTTp status code 
	"message": "messsage adddedd successfully" // 
	"data": [
		//null if failed case;	
	]
}
```

List of status codes

| status code | description |
| ------ | ------ |
| 200 | OK |
| 201 | Created |
| 204 | No content(Record deletion) |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 405 | Method Not Allowed |
| 409 | Conflict |
| 422 | Unprocessable Entity (validation error) |
| 500 | Internal Server Error |

Here is the sample json for validation 
```sh
{
  "status_code": 422,
  "message": "",
  "data": {
    "mobile_number": [
      "The mobile number field is required."
    ],
    "password": [
      "The password field is required."
    ]
  }
}
```

## Swagger-ui

Link : http://127.0.0.1:8080/api/documentation

Please use below code for development at env. Make it `false` when site move to production also remove the given file - `storage/api-docs/api-docs.json`

`SWAGGER_GENERATE_ALWAYS=true` 

[Click here](https://github.com/DarkaOnLine/SwaggerLume) for more information.


## Localization
Translation files that use translation strings as keys are stored as JSON files in the resources/lang directory. For example, if your application has a Spanish translation, you should create a resources/lang/es.json file:
```sh
{
    "I love programming.": "Me encanta programar."
}
```
For more info [click here](https://laravel.com/docs/8.x/localization#using-translation-strings-as-keys)

You have to pass localization code (eg. es) through header. Here is the header key `X-Localization`

## Common Response Handler
A common response handler has been created `RootController` which will be used for returning responses for all API request. All controller needs to extend this controller to have access to the below mentioned function.

Format of the function is
```php
apiResponse($response = [], $message = "", $error_code = 200)

1. First argument will be the response part.
2. Second argument will be accepting the message to be sent.
3. Third argument is to send the error code
```

## Permission checking
Function definition - `checkPermission($request, array $permissionList)`
Entire request object received and the required permission list is passed to the function. Details of currently authenticated user is taken and query is executed to check whether user is having any of those permissions. Only granted permissions list will be sent back.

## Serving Laravel/Lumen project locally

To serve using the built-in PHP development server.
Run command - `php -S localhost:8000 -t public`
Port number can be changed according to the need.
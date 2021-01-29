# HTTP
Wrapper around Nyholm\Psr7 library with a few helper methods and a basic emitter. For use in WordPress during ajax calls.


![alt text](https://img.shields.io/badge/Current_Version-0.2.6-yellow.svg?style=flat " ") 
[![Open Source Love](https://badges.frapsoft.com/os/mit/mit.svg?v=102)](https://github.com/ellerbrock/open-source-badge/)

![alt text](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat " ") 
![alt text](https://img.shields.io/badge/PHPUnit-PASSING-brightgreen.svg?style=flat " ") 
![alt text](https://img.shields.io/badge/PHCBF-WP_Extra-brightgreen.svg?style=flat " ") 

For more details please visit our docs.
https://app.gitbook.com/@glynn-quelch/s/pinkcrab/

## Version ##
**Release 0.2.6**

## Why? ##
Throughout a few of our modules we need to handle HTTP requests and responses. The WP_HTTP_* classes are great, but PS7 complient libraries have a lot more to offer.

So this small module acts a wrapper for the Nyholm\Psr7 and Nyholm\Psr7Server libraries and gives a few helper methods. You can easily create and emit either Responses that extend **WP_HTTP_RESPONSE** or implements **ResponseInterface**

## Examples ##

### Creates a WP_HTTP_Response

```php
<?php
use PinkCrab\HTTP\HTTP;
use PinkCrab\HTTP\HTTP;

$http = new HTTP();

$response = $http->wp_response(
    ['some_key'=>'some_value'], 
    200, 
    ['Content-Type' => 'application/json; charset=UTF-8']
);

// OR 

$response = HTTP_Helper::wp_response(
    ['some_key'=>'some_value'], 
    200, 
    ['Content-Type' => 'application/json; charset=UTF-8']
);

// Emit to client
$http->emit_response($response);

```

As both have the same signatures, you can interchange at will. Obviously the PS7 Repsonse has more functionality to fine tune the response.

### Creates a PS7 Response

```php
<?php
use PinkCrab\HTTP\HTTP;
use PinkCrab\HTTP\HTTP;

$http = new HTTP();

$response = $http->ps7_response(
    ['some_key'=>'some_value'], 
    200, 
    ['Content-Type' => 'application/json; charset=UTF-8']
);

// OR 

$response = HTTP_Helper::response(
    ['some_key'=>'some_value'], 
    200, 
    ['Content-Type' => 'application/json; charset=UTF-8']
);

// Emit to client
$http->emit_response($response);

```

### Creates a PS7 Request

```php
<?php
use PinkCrab\HTTP\HTTP;
use PinkCrab\HTTP\HTTP_Helper;

$http = new HTTP();

$request = $http->psr7_request(
    'GET',
    'https://google.com'
);

// OR 

$request = HTTP_Helper::request(
    'GET',
    'https://google.com'
);

```

### Get ServerRequest fromGlobals
Retruns a populated instance of ServerRequestInterface.

```php
<?php
use PinkCrab\HTTP\HTTP_Helper;
use PinkCrab\HTTP\HTTP_Helper;

$server = (new HTTP())->request_from_globals();

// OR 

$server = HTTP_Helper::global_server_request();

```

### Create Stream
The PSR7 HTTP objects work with streams for the body, you can wrap all scalars values which can cast to JSON in a stream.

```php
<?php
use PinkCrab\HTTP\HTTP_Helper;
use PinkCrab\HTTP\HTTP_Helper;

$stream = (new HTTP())->stream_from_scalar($data);

// OR 

$stream = HTTP_Helper::stream_from_scalar($data);

```
> The ````(new HTTP())->create_stream_with_json()```` has been marked as deprecated since 0.2.3. use ````(new HTTP())->stream_from_scalar()```` in its place.

## Testing ##

If you wish to run these tests locally, you will need to create a database table and set your details in /tests/wp-config.php

````php
    // WARNING WARNING WARNING!
    // These tests will DROP ALL TABLES in the database with the prefix named below.
    // DO NOT use a production database or one that is shared with something else.
    define( 'DB_NAME', getenv( 'WP_DB_NAME' ) ?: 'pc_core_tests' ); //<--
    define( 'DB_USER', getenv( 'WP_DB_USER' ) ?: 'root' ); //<--
    define( 'DB_PASSWORD', getenv( 'WP_DB_PASS' ) ?: '' ); //<--
    define( 'DB_HOST', '127.0.0.1' ); //<--
    define( 'DB_CHARSET', 'utf8' );
    define( 'DB_COLLATE', '' );
````

The test suite uses WP_PHPUNIT so requires a valid instance of WordPress to test with. DO NOT TEST WITH YOUR DEVELOPMENT OR PRODUCTION DATABASE AS ALL DATA IS PURGED AFTER EACH TEST!

### PHP Unit ###

````bash
$ phpunit
````
OR
````bash 
$ composer test
````

### PHP Stan ###
The module comes with a pollyfill for all WP Functions, allowing for the testing of all core files. The current config omits the Dice file as this is not ours. To run the suite call.
````bash 
$ vendor/bin/phpstan analyse src/ -l8 
````
````bash 
$ composer analyse
````

## License ##

### MIT License ###
http://www.opensource.org/licenses/mit-license.html  

## Change Log ##
* 0.2.6 - 
* 0.2.5 - Removed object typehint for param in emit_response
* 0.2.4 - Typo on scalar (all typed as scala)
* 0.2.3 - Added in HTTP_Helper class, patched ServerRequest fromGloabls to include the raw $_POST in its body. 
* 0.2.2 - Added the helper for wrapping data as json in Stream
* 0.2.1 - Removed die() from end of Emit calls and just reutrned back void. Die to happen at other end
* 0.2.0 - Moved from Guzzle being injected in cosntructor to using cutom HTTP (pink crab). Plug move to composer format.

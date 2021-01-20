# HTTP
Wrapper around Nyholm\Psr7 library with a few helper methods and a basic emitter. For use in WordPress during ajax calls.


![alt text](https://img.shields.io/badge/Current_Version-0.1.0-yellow.svg?style=flat " ") 
[![Open Source Love](https://badges.frapsoft.com/os/mit/mit.svg?v=102)](https://github.com/ellerbrock/open-source-badge/)

![alt text](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat " ") 
![alt text](https://img.shields.io/badge/PHPUnit-PASSING-brightgreen.svg?style=flat " ") 
![alt text](https://img.shields.io/badge/PHCBF-WP_Extra-brightgreen.svg?style=flat " ") 

For more details please visit our docs.
https://app.gitbook.com/@glynn-quelch/s/pinkcrab/

## Version ##
**Release 0.2.2**

## Why? ##
Throughout a few of our modules we need to handle HTTP requests and responses. The WP_HTTP_* classes are great, but PS7 complient libraries have a lot more to offer.

So this small module acts a wrapper for the Nyholm\Psr7 and Nyholm\Psr7Server libraries and gives a few helper methods. You can easily create and emit either Responses that extend **WP_HTTP_RESPONSE** or implements **ResponseInterface**

## Examples ##

### Creates a WP_HTTP_Response

```php
<?php
use PinkCrab\HTTP\HTTP;

$http = new HTTP();

$response = $http->wp_response(
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

$http = new HTTP();

$response = $http->ps7_response(
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

$http = new HTTP();

$request = $http->psr7_request(
    'GET',
    'https://google.com'
);

```

## Testing ##

### PHP Unit ###
If you would like to run the tests for this package, please ensure you add your database details into the test/wp-config.php file before running phpunit.
````bash
$ phpunit
````
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
* 0.2.2 - Added the helper for wrapping data as json in Stream
* 0.2.1 - Removed die() from end of Emit calls and just reutrned back void. Die to happen at other end
* 0.2.0 - Moved from Guzzle being injected in cosntructor to using cutom HTTP (pink crab). Plug move to composer format.

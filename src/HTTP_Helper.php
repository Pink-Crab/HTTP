<?php

declare(strict_types=1);

/**
 * Static wrapper for the HTTP class.
 * For cleaner calls.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\HTTP
 */

namespace PinkCrab\HTTP;

use WP_HTTP_Response;
use Nyholm\Psr7\Stream;
use PinkCrab\HTTP\HTTP;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HTTP_Helper {

	/**
	 * Instance of HTTP class.
	 *
	 * @var HTTP|null
	 */
	protected static $http;

	/**
	 * Returns the current HTTP instance.
	 * Creates if doesnt exist.
	 *
	 * @return HTTP
	 */
	public static function get_http(): HTTP {
		if ( ! static::$http ) {
			static::$http = new HTTP();
		}
		return static::$http;
	}

	/**
	 * Returns a ServerRequest with current globals.
	 *
	 * @return ServerRequestInterface
	 */
	public static function global_server_request(): ServerRequestInterface {
		return static::get_http()
			->request_from_globals();
	}

	/**
	 * Wrapper for making a PS7 request.
	 *
	 * @uses Nyholm\Psr7::Request()
	 * @param string $method HTTP method
	 * @param string|UriInterface $uri URI
	 * @param array<string, string> $headers Request headers
	 * @param string|resource|StreamInterface|null $body Request body
	 * @param string $version Protocol version
	 */
	public static function request(
		string $method,
		$uri,
		array $headers = array(),
		$body = null,
		string $version = '1.1'
	): RequestInterface {
		return static::get_http()
			->psr7_request( $method, $uri, $headers, $body, $version );
	}

	/**
	 * Returns a PS7 Response object.
	 *
	 * @param int $status
	 * @param array<string, string> $headers
	 * @param array<string, string>|string|resource|StreamInterface|null $body
	 * @param string $version
	 * @param string $reason
	 * @return ResponseInterface
	 */
	public static function response(
		$body = null,
		int $status = 200,
		array $headers = array(),
		string $version = '1.1',
		string $reason = null
	): ResponseInterface {
		return static::get_http()
			->psr7_response( $body, $status, $headers, $version, $reason );
	}

	/**
	 * Returns a WP_Rest_Response
	 *
	 * @param int $status
	 * @param array<string, string> $headers
	 * @param mixed $data
	 * @return WP_HTTP_Response
	 */
	public static function wp_response(
		$data = null,
		int $status = 200,
		array $headers = array()
	): WP_HTTP_Response {
		return static::get_http()
			->wp_response( $data, $status, $headers );
	}

	/**
	 * Wraps any value which can be json encoded in a StreamInterface
	 *
	 * @param string|int|float|object|array<mixed> $value
	 * @return StreamInterface
	 */
	public static function stream_from_scalar( $value ): StreamInterface {
		return Stream::create( json_encode( $value ) ?: '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
	}
}

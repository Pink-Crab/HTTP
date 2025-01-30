<?php

declare(strict_types=1);

/**
 * Wrapper around Nyholm\Psr7 library with a few helper methods and a basic emitter.
 *
 * For use in WordPress during ajax calls.
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

use RuntimeException;
use WP_HTTP_Response;
use Nyholm\Psr7\Stream;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;

class HTTP {

	/**
	 * Returns the current request from glbals
	 *
	 * @uses Psr17Factory::class
	 * @uses ServerRequestCreator::class
	 *
	 * @return ServerRequestInterface
	 */
	public function request_from_globals(): ServerRequestInterface {

		$psr17_factory = new Psr17Factory();

		return ( new ServerRequestCreator(
			$psr17_factory,
			$psr17_factory,
			$psr17_factory,
			$psr17_factory
		) )->fromGlobals()
			->withBody( $this->stream_from_scalar( $_POST ) );  // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Wrapper for making a PS7 request.
	 *
	 * @uses Nyholm\Psr7::Request()
	 * @param string                               $method  HTTP method
	 * @param string|UriInterface                  $uri     URI
	 * @param array<string, string>                $headers Request headers
	 * @param string|resource|StreamInterface|null $body    Request body
	 * @param string                               $version Protocol version
	 *
	 * @return RequestInterface
	 */
	public function psr7_request(
		string $method,
		$uri,
		array $headers = array(),
		$body = null,
		string $version = '1.1'
	): RequestInterface {
		return new Request( $method, $uri, $headers, $body, $version );
	}

	/**
	 * Returns a PS7 Response object.
	 *
	 * @param array<string, string>|string|resource|StreamInterface|null $body    The response body.
	 * @param integer                                                    $status  The response status.
	 * @param array<string, string>                                      $headers The response headers.
	 * @param string                                                     $version The response version.
	 * @param string|null                                                $reason  The response reason.
	 *
	 * @return ResponseInterface
	 */
	public function psr7_response(
		$body = null,
		int $status = 200,
		array $headers = array(),
		string $version = '1.1',
		?string $reason = null
	): ResponseInterface {
		// Json Encode if body is array or object.
		if ( is_array( $body ) || is_object( $body ) ) {
			$body = wp_json_encode( $body );
		}

		// If body is false, pass as null. @phpstan
		return new Response( $status, $headers, $body ?: null, $version, $reason );
	}

	/**
	 * Returns a WP_Rest_Response
	 *
	 * @param array<string, string>|object|string|null $data    The response data.
	 * @param integer                                  $status  The response status.
	 * @param array<string, string>                    $headers The response headers.
	 *
	 * @return WP_HTTP_Response
	 */
	public function wp_response(
		$data = null,
		int $status = 200,
		array $headers = array()
	): WP_HTTP_Response {
		return new WP_HTTP_Response( $data, $status, $headers );
	}

	/**
	 * Emits either a PS7 or WP_HTTP Response.
	 *
	 * @param ResponseInterface|WP_HTTP_Response|object $response The response to emit.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException If response is not a valid type.
	 */
	public function emit_response( $response ): void {

		// Throw if not a valid response.
		if ( ! $response instanceof ResponseInterface
		&& ! $response instanceof WP_HTTP_Response ) {
			throw new InvalidArgumentException( 'Only ResponseInterface & WP_REST_Response responses can be emitted.' );
		}

		// Based on type, emit the response.
		if ( $response instanceof ResponseInterface ) {
			$this->emit_psr7_response( $response );
		} else {
			$this->emit_wp_response( $response );
		}
	}

	/**
	 * Emits a PSR7 response.
	 *
	 * @param ResponseInterface $response
	 * @return void
	 */
	public function emit_psr7_response( ResponseInterface $response ): void {

		// If headers sent, throw headers already sent.
		$this->headers_sent();

		// Set Set status line..
		$status_line = sprintf(
			'HTTP/%s %s %s',
			$response->getProtocolVersion(),
			$response->getStatusCode(),
			$response->getReasonPhrase()
		);
		header( $status_line, true );

		// Append headers.
		foreach ( $this->headers_with_json( $response->getHeaders() )
			as $name => $values ) {

			// If values are an array, join.
			$values = is_array( $values ) ? join( ',', $values ) : (string) $values;

			$response_header = sprintf( '%s: %s', $name, $values );
			header( $response_header, false );
		}

		// Emit body.
		echo $response->getBody(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return; // phpcs:ignore Squiz.PHP.NonExecutableCode.ReturnNotRequired
	}

	/**
	 * Emits a WP_HTTP Response.
	 *
	 * @param WP_HTTP_Response $response
	 * @return void
	 */
	public function emit_wp_response( WP_HTTP_Response $response ): void {

		// If headers sent, throw headers already sent.
		$this->headers_sent();

		// Append headers.
		foreach ( $this->headers_with_json( $response->get_headers() )
			as $name => $values ) {
			$values = is_array( $values ) ? join( ',', $values ) : (string) $values;

			$header = sprintf( '%s: %s', $name, $values );

			// Set the headers.
			header( $header, false );
		}

		// Emit body.
		$body = $response->get_data();
		print is_string( $body ) ? $body : wp_json_encode( $body ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return; // phpcs:ignore Squiz.PHP.NonExecutableCode.ReturnNotRequired
	}

	/**
	 * Adds the JSON content type header if no header set.
	 *
	 * @param array<string, mixed> $headers
	 * @return array<string, mixed>
	 */
	public function headers_with_json( array $headers = array() ): array {
		if ( ! array_key_exists( 'Content-Type', $headers ) ) {
			$headers['Content-Type'] = 'application/json; charset=' . get_option( 'blog_charset' );
		}
		return $headers;
	}

	/**
	 * Throws RunTime error if headers sent.
	 *
	 * @return void
	 * @throws RuntimeException
	 */
	protected function headers_sent(): void {
		if ( headers_sent() ) {
			throw new RuntimeException( 'Headers were already sent. The response could not be emitted!' );
		}
	}

	/**
	 * Wraps any value which can be json encoded in a StreamInterface
	 *
	 * @param string|integer|float|object|array<mixed> $data
	 * @return \Psr\Http\Message\StreamInterface
	 */
	public function stream_from_scalar( $data ): StreamInterface {
		return Stream::create( json_encode( $data ) ?: '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
	}
}

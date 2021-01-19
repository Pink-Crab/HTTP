<?php

declare(strict_types=1);
/**
 * An abstract class for resitering custom post types.
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
 * @package PinkCrab\Registerables
 */

namespace PinkCrab\HTTP;

class HTTP {

	/**
	 * Returns the current request from glbals
	 *
	 * @uses Psr17Factory::class
	 * @uses ServerRequestCreator::class
	 * @return ServerRequestInterface
	 */
	public function request_from_globals(): ServerRequestInterface {

		$psr17_factory = new Psr17Factory();

		return ( new ServerRequestCreator(
			$psr17_factory,
			$psr17_factory,
			$psr17_factory,
			$psr17_factory
		) )->fromGlobals();
	}

	/**
	 * Wrapper for making a PS7 request.
	 *
	 * @uses Nyholm\Psr7::Request()
	 * @param string $method HTTP method
	 * @param string|UriInterface $uri URI
	 * @param array $headers Request headers
	 * @param string|resource|StreamInterface|null $body Request body
	 * @param string $version Protocol version
	 */
	public function psr7_request(
		string $method,
		$uri,
		array $headers = array(),
		$body = null,
		string $version = '1.1'
	) {
		return new Request( $method, $uri, $headers, $body, $version );
	}

	/**
	 * Returns a PS7 Response object.
	 *
	 * @param int $status
	 * @param array $headers
	 * @param string|resource|StreamInterface|null $body
	 * @param string $version
	 * @param string $reason
	 * @return ResponseInterface
	 */
	public function psr7_response(
		$body = null,
		int $status = 200,
		array $headers = array(),
		string $version = '1.1',
		string $reason = null
	): ResponseInterface {
		// Json Encode if body is array or object.
		if ( is_array( $body ) || is_object( $body ) ) {
			$body = wp_json_encode( $body );
		}

		return new Response( $status, $headers, $body, $version, $reason );
	}

	/**
	 * Returns a WP_Rest_Response
	 *
	 * @param int $status
	 * @param array $headers
	 * @param mixed $data
	 * @return WP_REST_Response
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
	 * @param ResponseInterface|WP_HTTP_Response $response
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function emit_response( object $response ): void {

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
			$response_header = sprintf(
				'%s: %s',
				$name,
				$response->getHeaderLine( $name )
			);
			header( $response_header, false );
		}

		// Emit body.
		echo $response->getBody(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_die();
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
			as $name => $value ) {
			$response_header = sprintf( '%s: %s', $name, $value );
			header( $response_header, false );
		}

		// Emit body.
		$body = $response->get_data();
		print is_string( $body ) ? $body : wp_json_encode( $body ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_die();
	}

	/**
	 * Adds the JSON content type header if no header set.
	 *
	 * @param array $headers
	 * @return array
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
}

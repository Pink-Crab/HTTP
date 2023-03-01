<?php

/**
 * Sample Test
 *
 * @package PinkCrab/Tests
 */

use PinkCrab\HTTP\HTTP;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

// use WP_Ajax_UnitTestCase;
/**
 *      *
 * @preserdveGlobalState disabled

 */
class Test_HTTP extends TestCase {

	/**
	 * @var bool
	 */
	protected $preserveGlobalState = false;

	/** @testdox It should be possible to create a WP_Rest_Response */
	public function test_can_create_wp_http_response(): void {
		$http     = new HTTP();
		$response = $http->wp_response( array( 'key' => 'test_VALUE' ), 500 );

		$this->assertInstanceOf( WP_HTTP_Response::class, $response );
		$this->assertIsArray( $response->get_data() );
		$this->assertArrayHasKey( 'key', $response->get_data() );
		$this->assertEquals( 'test_VALUE', $response->get_data()['key'] );
		$this->assertEquals( 500, $response->get_status() );
	}

	/**
	 * @testdox It should be possible to emit a WP_Rest_Response
	 * @runInSeparateProcess
	 * @return void
	 */
	public function test_can_emit_wp_response(): void {
		$http     = new HTTP();
		$response = $http->wp_response( array( 'key' => 'WP_VALUE' ) );

		$this->expectOutputRegex( '/^(.*?(\bWP_VALUE\b)[^$]*)$/' );

		$http->emit_response( $response );
	}

	/** @testdox It should be possible to create a PSR7 Response */
	public function test_can_create_psr7_response(): void {
		$http     = new HTTP();
		$response = $http->psr7_response( array( 'key' => 'test_VALUE' ), 500 );

		$body = json_decode( (string) $response->getBody(), true );

		$this->assertInstanceOf( ResponseInterface::class, $response );
		$this->assertIsArray( $body );
		$this->assertArrayHasKey( 'key', $body );
		$this->assertEquals( 'test_VALUE', $body['key'] );
		$this->assertEquals( 500, $response->getStatusCode() );
	}

	/**
	 * @testdox It should be possible to emit a PSR7 Response.
	 * @runInSeparateProcess
	 * @return void
	 */
	function test_can_emit_psr7_response(): void {
		$http     = new HTTP();
		$response = $http->psr7_response( array( 'key' => 'ps7_value' ) );

		$this->expectOutputRegex( '/^(.*?(\bps7_value\b)[^$]*)$/' );

		$http->emit_response( $response );
	}

	/**
	 * Tests that JSON header is added to array if not set.
	 *
	 * @return void
	 */
	public function test_headers_with_json(): void {

		// Test with empty array, should add.
		$mock_header = array();
		$mock_header = ( new HTTP() )->headers_with_json( $mock_header );

		$this->assertArrayHasKey( 'Content-Type', $mock_header );
		$this->assertStringContainsString( 'application/json', $mock_header['Content-Type'] );

		// Ensure that content type not over written.
		$mock_header2['Content-Type'] = 'NOPE';

		$mock_header2 = ( new HTTP() )->headers_with_json( $mock_header2 );
		$this->assertStringNotContainsString( 'application/json', $mock_header2['Content-Type'] );
	}

	/**
	 * Test that request wrapper works.
	 *
	 * @return void
	 */
	public function test_psr7_request(): void {
		$http    = new HTTP();
		$request = $http->psr7_request(
			'GET',
			'https://google.com'
		);

		$this->assertInstanceOf( RequestInterface::class, $request );
		$this->assertEquals( 'GET', $request->getMethod() );
		$this->assertInstanceOf( UriInterface::class, $request->getUri() );
		$this->assertEquals( 'google.com', $request->getUri()->getHost() );
	}

	/**
	 * Test throws exception if no response passed to emit_reponse.
	 *
	 * @return void
	 */
	public function test_emit_throw_if_none_valid_response_type(): void {
		$this->expectException( InvalidArgumentException::class );
		$http = new HTTP();
		$http->emit_response( (object) array( 'not' => 'valid' ) );
	}

	/**
	 * Test can produce stream from data which can be cast to JSON.
	 *
	 * @return void
	 */
	public function test_can_create_stream_from_jsonable_data(): void {
		$http       = new HTTP();
		$withArray  = $http->stream_from_scalar( array( 'key' => 'value' ) );
		$withObject = $http->stream_from_scalar( (object) array( 'key' => 'value' ) );
		$withString = $http->stream_from_scalar( 'STRING' );
		$withInt    = $http->stream_from_scalar( 42 );
		$withFloat  = $http->stream_from_scalar( 4.2 );

		$this->assertEquals( '{"key":"value"}', (string) $withArray );
		$this->assertEquals( '{"key":"value"}', (string) $withObject );
		$this->assertEquals( '"STRING"', (string) $withString );
		$this->assertEquals( 42, (string) $withInt );
		$this->assertEquals( 4.2, (string) $withFloat );
	}

	/** @testdox Attempting to emit a response after headers have already been sent, should results in an exception being thrown */
	public function test_emit_response_throws_exception_if_headers_sent(): void {
		$this->expectException( RuntimeException::class );

		$http = new HTTP();

		// Emit a response.
		$http->emit_response( $http->psr7_response( array( 'key' => 'ps7_value' ) ) );

		// Emit another response.
		$http->emit_response( $http->psr7_response( array( 'key' => 'ps7_value' ) ) );
	}
}

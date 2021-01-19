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
// use PHPUnit\Framework\TestCase;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

// use WP_Ajax_UnitTestCase;
/**
 *      *
 * @preserdveGlobalState disabled

 */
class Test_HTTP extends TestCase {

	/**
	 * Undocumented variable
	 *
	 * @var bool
	 */
	protected $preserveGlobalState = false;

	/**
	 * Test that we can creatre a WP_HTTP_Response
	 *
	 * @return void
	 */
	public function test_can_create_wp_http_response(): void {
		$http     = new HTTP();
		$repsonse = $http->wp_response( array( 'key' => 'test_VALUE' ), 500 );

		$this->assertInstanceOf( WP_HTTP_Response::class, $repsonse );
		$this->assertIsArray( $repsonse->get_data() );
		$this->assertArrayHasKey( 'key', $repsonse->get_data() );
		$this->assertEquals( 'test_VALUE', $repsonse->get_data()['key'] );
		$this->assertEquals( 500, $repsonse->get_status() );
	}

	/**
	 * Tests that a WP_Response can be generated and emmited.
	 *
	 * @runInSeparateProcess
	 * @return void
	 */
	function test_can_emit_wp_response(): void {
		$http     = new HTTP();
		$repsonse = $http->wp_response( array( 'key' => 'test_VALUE' ) );

		$this->expectOutputRegex( '/^(.*?(\btest_VALUE\b)[^$]*)$/' );

		$http->emit_response( $repsonse );
	}

	/**
	 * Test that we can creatre a psr7 Response
	 *
	 * @return void
	 */
	public function test_can_create_psr7_respnse(): void {
		$http     = new HTTP();
		$repsonse = $http->psr7_response( array( 'key' => 'test_VALUE' ), 500 );

		$body = json_decode( (string) $repsonse->getBody(), true );

		$this->assertInstanceOf( ResponseInterface::class, $repsonse );
		$this->assertIsArray( $body );
		$this->assertArrayHasKey( 'key', $body );
		$this->assertEquals( 'test_VALUE', $body['key'] );
		$this->assertEquals( 500, $repsonse->getStatusCode() );
	}

	/**
	 * Tests that a ResponseInterface can be generated and emmited.
	 *
	 * @runInSeparateProcess
	 * @return void
	 */
	function test_can_emit_psr7_response(): void {
		$http     = new HTTP();
		$repsonse = $http->psr7_response( array( 'key' => 'ps7_value' ) );

		$this->expectOutputRegex( '/^(.*?(\bps7_value\b)[^$]*)$/' );

		$http->emit_response( $repsonse );
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

	public function test_emit_throw_if_none_valid_response_type(): void {
		$this->expectException( InvalidArgumentException::class );
		$http = new HTTP();
		$http->emit_response( (object) array( 'not' => 'valid' ) );
	}
}

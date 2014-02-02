<?php

namespace Tests;

use AutoUpdate;

class AutoUpdateTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var string this is used to cache the contents of the log when running tests and restore it after
	 */
	private static $logContents;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		//cache anything in the StableUpdate log to restore after
		if( file_exists( __DIR__ . '/../../Includes/StableUpdate.log' ) ) {
			self::$logContents = file_get_contents( __DIR__ . '/../../Includes/StableUpdate.log' );
		}
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();

		//restore the StableUpdate log to previous contents
		if( isset( self::$logContents ) ) {
			file_put_contents( __DIR__ . '/../../Includes/StableUpdate.log', self::$logContents );
		}
	}

	private function getUpdater( $http ){
		return new AutoUpdate( $http );
	}

	private function getMockHttp( $data = array(), $header = '' ) {
		$mock = $this->getMockBuilder( 'HTTP' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'get' )
			->will( $this->returnValue( json_encode( $data ) ) );
		$mock->expects( $this->any() )
			->method( 'getLastHeader' )
			->will( $this->returnValue( $header ) );
		return $mock;
	}

	public function provideCheckforupdate() {
		return array(
			array( true,
				null,
				' Status: 304 Not Modified ',
				'/Peachy is up to date/',
			),
			array( true,
				array( 'message' => 'API rate limit exceeded'),
				'', //no header
				'/Cant check for updates right now, next window in/'
			),
			array( false,
				array( array( 'sha' => 'testshahash' ) ),
				'ETag: "253647-IamAtestETag"',
				'/No update log found/',
				'253647-IamAtestETag'
			),
			array( true,
				array( array( 'sha' => 'testshahash' ) ),
				'ETag: "253647-IamAtestETag"',
				'/Peachy is up to date/',
				'253647-IamAtestETag',
				serialize( array( array( 'sha' => 'testshahash' ) ) )
			),
			array( false,
				array( array( 'sha' => 'testshahash' ) ),
				'ETag: "253647-IamAtestETag"',
				'/Update available/',
				'253647-IamAtestETag',
				serialize( array( array( 'sha' => 'differenthash!' ) ) )
			),
		);
	}

	/**
	 * @dataProvider provideCheckforupdate
	 * @covers ::iin_array
	 */
	public function testCheckforupdate( $expected, $data, $header, $outputRegex = '/.*?/', $expectEtag = false, $updatelog = null ) {
		$updater = $this->getUpdater( $this->getMockHttp( $data, $header ) );
		if( $updatelog === null ) {
			if( file_exists( __DIR__ . '/../../Includes/StableUpdate.log' ) ) {
				unlink( __DIR__ . '/../../Includes/StableUpdate.log' );
			}
		} else {
			file_put_contents( __DIR__ . '/../../Includes/StableUpdate.log', $updatelog );
		}

		$this->expectOutputRegex( $outputRegex );
		$result = $updater->Checkforupdate();
		$this->assertEquals( $expected, $result );
		if( $expectEtag !== false ) {
			$this->assertFileExists( __DIR__ . '/../../tmp/github-ETag.tmp' );
			$this->assertEquals( $expectEtag, file_get_contents( __DIR__ . '/../../tmp/github-ETag.tmp' ) );
		}
	}

} 
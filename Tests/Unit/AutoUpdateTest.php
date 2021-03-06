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

	private function getMockHttp( $data = array() ) {
		$mock = $this->getMockBuilder( 'HTTP' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'get' )
			->will( $this->returnValue( json_encode( $data ) ) );
		return $mock;
	}

	public function provideCheckforupdate() {
		return array(
			array( true,
				array( 'message' => 'API rate limit exceeded'),
				'/Cant check for updates right now, next window in/'
			),
			array( false,
				array( array( 'sha' => 'testshahash' ) ),
				'/No update log found/',
			),
			array( true,
				array( array( 'sha' => 'testshahash' ) ),
				'/Peachy is up to date/',
				serialize( array( array( 'sha' => 'testshahash' ) ) )
			),
			array( false,
				array( array( 'sha' => 'testshahash' ) ),
				'/Update available/',
				serialize( array( array( 'sha' => 'differenthash!' ) ) )
			),
		);
	}

	/**
	 * @dataProvider provideCheckforupdate
	 * @covers AutoUpdate::Checkforupdate
	 */
	public function testCheckforupdate( $expected, $data, $outputRegex = '/.*?/', $updatelog = null ) {
		$updater = $this->getUpdater( $this->getMockHttp( $data ) );
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
	}

} 
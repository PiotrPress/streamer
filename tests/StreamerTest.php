<?php

class StreamerTest extends \PHPUnit\Framework\TestCase {
    protected string $testData =
        'Lorem ipsum dolor sit amet, consectetur adipiscing elit. 
        Donec sit amet molestie metus, eget feugiat erat. 
        Vivamus tempor convallis diam, vitae finibus mauris egestas in. 
        Etiam feugiat ullamcorper condimentum. 
        In tincidunt feugiat mi, eget pellentesque tellus tristique in. 
        Nullam in purus sollicitudin purus mattis elementum at lobortis elit. 
        Mauris a venenatis augue, non euismod massa. 
        Donec rhoncus turpis eu sem pretium, quis varius leo tincidunt. 
        Duis rutrum enim rutrum, laoreet tortor vitae, malesuada neque.';

    public function testRegister() {
        \PiotrPress\Streamer::register( 'streamer' );
        $this->assertTrue( in_array( 'streamer', stream_get_wrappers() ) );
    }

    public function testAlreadyDefined() {
        $this->expectWarning();
        $this->expectWarningMessageMatches('/already defined/');
        \PiotrPress\Streamer::register( 'streamer' );
    }

    public function testUnregister() {
        \PiotrPress\Streamer::unregister( 'streamer' );
        $this->assertTrue( ! in_array( 'streamer', stream_get_wrappers() ) );
    }

    public function testUnableToUnregister() {
        $this->expectWarning();
        $this->expectWarningMessageMatches('/Unable to unregister/');
        \PiotrPress\Streamer::unregister( 'streamer' );
    }

    public function testFailedToOpen() {
        $this->expectWarning();
        $this->expectWarningMessageMatches('/Failed to open/');
        \PiotrPress\Streamer::register( 'streamer' );
        fopen( 'streamer://test', 'r' );
    }

    public function testCreateResource() {
        $this->assertIsResource( fopen( 'streamer://test', 'w' ) );
    }

    public function testOpen() {
        $this->assertIsResource( fopen( 'streamer://test', 'r' ) );
    }

    public function testClose() {
        $this->assertTrue( fclose( fopen( 'streamer://test', 'r' ) ) );
    }

    public function testOverwrite() {
        $this->assertEquals( strlen( $this->testData ), file_put_contents( 'streamer://test', $this->testData ) );
    }

    public function testReadAll() {
        $this->assertEquals( $this->testData, file_get_contents( 'streamer://test' ) );
    }

    public function testAppend() {
        $handle = fopen( 'streamer://test', 'a' );
        $this->assertEquals(  $length = strlen( $this->testData ), fwrite( $handle, $this->testData, $length ) );
        fclose( $handle );
        $this->assertEquals( $this->testData . $this->testData, file_get_contents( 'streamer://test' ) );
    }

    public function testWriteRestricted() {
        $handle = fopen( 'streamer://test', 'r' );
        $this->assertEquals( 0, fputs( $handle, $this->testData ) );
        fclose( $handle );
    }

    public function testReadRestricted() {
        foreach ( [ 'a', 'w' ] as $mode ) {
            $handle = fopen( 'streamer://test', $mode );
            $this->assertEquals( '', fgets( $handle ) );
            fclose( $handle );
        }
    }

    public function testTell() {
        $handle = fopen( 'streamer://test', 'r+' );
        $this->assertEquals( 0, ftell( $handle ) );
        fputs( $handle, $this->testData, $length = strlen( $this->testData ) );
        $this->assertEquals( $length, ftell( $handle ) );
        fclose( $handle );
    }

    public function testEof() {
        $handle = fopen( 'streamer://test', 'r' );
        $data = '';
        while ( ! feof( $handle ) ) $data .= fread( $handle, 8192 );
        $this->assertEquals( $this->testData, $data );
        fclose( $handle );
    }

    public function testRead() {
        $handle = fopen( 'streamer://test', 'r' );
        $data = '';
        while ( false !== ( $buffor = fgets( $handle, 4096 ) ) ) $data .= $buffor;
        $this->assertEquals( $this->testData, $data );
        fclose( $handle );
    }

    public function testTruncate() {
        $handle = fopen( 'streamer://test', 'r+' );
        $this->assertEquals( ( $length = strlen( $this->testData ) ) * 2, ftruncate( $handle, $length * 2 ) );
        $this->assertEquals( $length, ftruncate( $handle, $length ) );
        fclose( $handle );
        $this->assertEquals( $this->testData, file_get_contents( 'streamer://test' ) );
    }

    public function testSeek() {
        $handle = fopen( 'streamer://test', 'r' );
        $data = explode( PHP_EOL, $this->testData );
        fgets( $handle );
        fseek( $handle, strlen( $data[ 0 ] ), SEEK_SET );
        $this->assertEquals( strlen( $data[ 0 ] ), ftell( $handle ) );
        fseek( $handle, strlen( $data[ 1 ] ), SEEK_CUR );
        $this->assertEquals( strlen( $data[ 0 ] . $data[ 1 ] ), ftell( $handle ) );
        fseek( $handle, -strlen( $data[ 0 ] ), SEEK_END );
        $this->assertEquals( strlen( $this->testData ) - strlen( $data[ 0 ] ), ftell( $handle ) );
        fclose( $handle );
    }

    public function testStat() {
        $handle = fopen( 'streamer://test', 'r' );
        $length = strlen( $this->testData );
        $this->assertEquals( $length, fstat( $handle )[ 'size' ] );
        $this->assertEquals( $length, fstat( $handle )[ 7 ] );
        fclose( $handle );
        $this->assertEquals( $length, stat( 'streamer://test' )[ 'size' ] );
        $this->assertEquals( $length, stat( 'streamer://test' )[ 7 ] );
        $this->assertEquals( $length, filesize( 'streamer://test' ) );
    }

    public function testRename() {
        rename( 'streamer://test', 'streamer://rename' );
        $this->assertIsResource( $handle = fopen( 'streamer://rename', 'r' ) );
        fclose( $handle );
        $this->assertEquals( $this->testData, file_get_contents( 'streamer://rename' ) );
        $this->expectWarning();
        $this->expectWarningMessageMatches('/Failed to open/');
        fopen( 'streamer://test', 'r' );
    }

    public function testUnlink() {
        unlink( 'streamer://rename' );
        $this->expectWarning();
        $this->expectWarningMessageMatches('/Failed to open/');
        fopen( 'streamer://rename', 'r' );
    }
}
<?php declare( strict_types = 1 );

namespace PiotrPress;

class Streamer {
    public $context = null;

    protected string $path = '';
    protected string $mode = '';
    protected int $position = 0;

    protected static array $data = [];

    static public function register( string $protocol, int $flags = 0 ) : bool {
        return \stream_wrapper_register( $protocol, static::class, $flags );
    }

    static public function unregister( string $protocol ) : bool {
        return \stream_wrapper_unregister( $protocol );
    }

    public function stream_open( string $path, string $mode, int $options, ?string &$opened_path ) : bool {
        $this->path = $path;

        switch ( $this->mode = \str_replace( [ 'b', 't' ], '', $mode ) ) {
            case 'r' :
            case 'r+' :
                return isset( self::$data[ $this->path ] );
            case 'w' :
            case 'w+' :
                self::$data[ $this->path ] = '';
                return true;
            case 'a' :
            case 'a+' :
                if ( ! isset( self::$data[ $this->path ] ) ) self::$data[ $this->path ] = '';
                return true;
            default : return false;
        }
    }

    public function stream_write( string $data ) : int {
        $length = \strlen( $data );

        switch ( $this->mode ) {
            case 'r' :
                return 0;
            case 'a' :
                self::$data[ $this->path ] .= $data;
                break;
            default :
                self::$data[ $this->path ] =
                    \substr( self::$data[ $this->path ], 0, $this->position ) .
                    $data .
                    \substr( self::$data[ $this->path ], $this->position += $length );
        }

        return $length;
    }

    public function stream_read( int $count ) : string {
        if ( \in_array( $this->mode, [ 'w', 'a' ] ) ) return '';

        $data = $count ?
            \substr( self::$data[ $this->path ], $this->position, $count ) :
            \substr( self::$data[ $this->path ], $this->position );
        $this->position += \strlen( $data );

        return $data;
    }

    public function stream_truncate( int $new_size ) : bool {
        if ( 'r' === $this->mode ) return false;

        if ( $new_size > $size = \strlen( self::$data[ $this->path ] ) )
            self::$data[ $this->path ] .= \str_repeat("\0", $new_size - $size );
        else
            self::$data[ $this->path ] = \substr( self::$data[ $this->path ], 0, $new_size );

        return true;
    }

    public function stream_tell() : int {
        return $this->position;
    }

    public function stream_seek( int $offset, int $whence = SEEK_SET ) : bool {
        $length = \strlen( self::$data[ $this->path ] );

        switch ( $whence ) {
            case SEEK_SET : $position = $offset; break;
            case SEEK_CUR : $position = $this->position + $offset; break;
            case SEEK_END : $position = $length + $offset; break;
            default : return false;
        }

        $return = ( $position >=0 && $position <= $length );
        if ( $return ) $this->position = $position;
        return $return;
    }

    public function stream_eof() : bool {
        return \strlen( self::$data[ $this->path ] ) <= $this->position;
    }

    public function stream_stat() : array {
        $size = \strlen( self::$data[ $this->path ] );

        return [
            7 => $size,
            'size' => $size
        ];
    }

    public function url_stat( string $path, int $flags ) : array {
        $this->path = $path;

        return $this->stream_stat();
    }

    public function unlink( string $path ) : bool {
        if ( isset( self::$data[ $path ] ) )
            unset( self::$data[ $path ] );
        else
            return false;

        return true;
    }

    public function rename( string $path_from, string $path_to ) : bool {
        if ( ! isset( self::$data[ $path_from ] ) ) return false;

        self::$data[ $path_to ] = self::$data[ $path_from ];
        unset( self::$data[ $path_from ] );

        return true;
    }
}
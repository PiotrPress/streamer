# Streamer

This library is a custom [PHP Stream Wrapper](https://www.php.net/manual/en/class.streamwrapper.php), which provides write/read operations and stores the data in the memory.

**NOTE:** It is similar to `php:// memory`, with the difference that is reusable, i.e. after the stream has been closed you can reopen it and the written data are still there, similar to regular file. Take a look at example of usage below.

## Installation

```console
composer require piotrpress/streamer
```

## Usage

```php
require __DIR__ . '/vendor/autoload.php';

PiotrPress\Streamer::register( 'virtual' );

file_put_contents( 'virtual://test', 'Test data' );
echo file_get_contents( 'virtual://test' );
```

## Supports

* `fopen()` modes: `r`, `r+`, `w`, `w+`, `a`, `a+`
* `fwrite()`, `fputs()` etc.
* `fread()`, `fgets()` etc.
* `file_get_contents()`, `file_put_contents()`
* `feof()`, `fseek()`, `ftell()`, `rewind()`
* `ftruncate()`
* `filesize()`
* `rename()`
* `unlink()`

## Requirements

PHP >= `7.4` version.

## License

[GPL3.0](license.txt)
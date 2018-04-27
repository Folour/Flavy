<?php

namespace Folour\Flavy;

use PHPUnit\Framework\TestCase;
use DOMDocument;
use Folour\Flavy\Exceptions\FileNotFoundException;
use Folour\Flavy\Exceptions\NotWritableException;
use BadMethodCallException;

class FlavyTests extends TestCase
{
    private $flavy;

    private function getFlavy()
    {
        is_dir(__DIR__ . DIRECTORY_SEPARATOR . '../extra/log/') ?: mkdir(__DIR__ . DIRECTORY_SEPARATOR . '../extra/log/', 0777, true);
        if (!$this->flavy) {
            $this->flavy = new Flavy(include(__DIR__ . DIRECTORY_SEPARATOR . '../config/flavy.php'));

        }

        return $this->flavy;
    }

    public function testMp3()
    {
        $this->getFlavy()
            ->from(__DIR__ . DIRECTORY_SEPARATOR . '../extra/Example.ogg')
            ->to(__DIR__ . DIRECTORY_SEPARATOR . '../extra/Example.mp3')
            ->aBitrate(128)
            ->aCodec('libmp3lame')
            ->overwrite()
            ->logTo(__DIR__ . DIRECTORY_SEPARATOR . '../extra/log/log.txt')
            ->run();
        $this->assertFileExists(__DIR__ . DIRECTORY_SEPARATOR . '../extra/Example.mp3');
    }

    public function testMono()
    {
        $this->getFlavy()
            ->from(__DIR__ . DIRECTORY_SEPARATOR . '../extra/Example.mp3')
            ->to(__DIR__ . DIRECTORY_SEPARATOR . '../extra/Example_mono.mp3')
            ->aBitrate(64)
            ->channels(1)
            ->sampleRate(11025)
            ->overwrite()
            ->run();
        $this->assertFileExists(__DIR__ . DIRECTORY_SEPARATOR . '../extra/Example_mono.mp3');
    }

    public function testVideo()
    {
        $this->getFlavy()
            ->from(__DIR__ . DIRECTORY_SEPARATOR . '../extra/Example.mov')
            ->to(__DIR__ . DIRECTORY_SEPARATOR . '../extra/Example.avi')
            ->aBitrate(64)
            ->vBitrate(64)
            ->aCodec('libmp3lame')
            ->vCodec('libxvid')
            ->frameRate(64)
            ->overwrite()
            ->run();
        $this->assertFileExists(__DIR__ . DIRECTORY_SEPARATOR . '../extra/Example.avi');
    }

    public function testGetInfo()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . '../extra/Example.mp3';
        $this->assertInternalType('array', $this->getFlavy()->info($file));
        $this->assertInstanceOf('DOMDocument', new DOMDocument($this->getFlavy()->info($file, 'xml')));
        $this->assertTrue($this->getFlavy()->info($file, 'json', false) === json_decode(json_encode($this->getFlavy()->info($file, 'json', false))));
        $this->assertInternalType('array', str_getcsv($this->getFlavy()->info($file, 'csv')));
    }

    public function testImages()
    {
        $this->getFlavy()->thumbnail(__DIR__ . DIRECTORY_SEPARATOR . '../extra/Example.mov', __DIR__ . DIRECTORY_SEPARATOR . '../extra/thumb_%d.jpg', 2);
        $this->assertFileExists(__DIR__ . DIRECTORY_SEPARATOR . '../extra/thumb_1.jpg');
        $this->assertFileExists(__DIR__ . DIRECTORY_SEPARATOR . '../extra/thumb_2.jpg');
    }

    public function testInformations()
    {
        $this->assertInternalType('array', $this->getFlavy()->encoders());
        $this->assertInternalType('array', $this->getFlavy()->decoders());
        $this->assertInternalType('array', $this->getFlavy()->formats());
        $this->assertInternalType('bool', $this->getFlavy()->canEncode());
        $this->assertInternalType('bool', $this->getFlavy()->canDecode());
    }

    public function testInfoException()
    {
        $this->expectException(FileNotFoundException::class);
        $this->getFlavy()->info('');
    }

    public function testThumbnailExceptionRead()
    {
        $this->expectException(FileNotFoundException::class);
        $this->getFlavy()->thumbnail('', './');
    }

    public function testThumbnailExceptionWrite()
    {
        $this->expectException(NotWritableException::class);
        $this->getFlavy()->thumbnail(__DIR__ . DIRECTORY_SEPARATOR . '../extra/Example.mov', './test/thumb_%d.jpg');
    }

    public function testFromException()
    {
        $this->expectException(FileNotFoundException::class);
        $this->getFlavy()
            ->from(__DIR__ . DIRECTORY_SEPARATOR . '../extra/test.mp3')
            ->run();
    }

    public function testToException()
    {
        $this->expectException(NotWritableException::class);
        $this->getFlavy()
            ->from(__DIR__ . DIRECTORY_SEPARATOR . '../extra/Example.mp3')
            ->to('./test/test.mp3')
            ->run();
    }

    public function testIsPossible()
    {
        $this->expectException(BadMethodCallException::class);
        $this->getFlavy()
                ->logTo(__DIR__ . DIRECTORY_SEPARATOR . '../extra/log/log.txt')
                ->run();
    }

    public function __destruct()
    {
        @unlink(__DIR__ . DIRECTORY_SEPARATOR . '../extra/Example.mp3');
        @unlink(__DIR__ . DIRECTORY_SEPARATOR . '../extra/Example_mono.mp3');
        @unlink(__DIR__ . DIRECTORY_SEPARATOR . '../extra/thumb_1.jpg');
        @unlink(__DIR__ . DIRECTORY_SEPARATOR . '../extra/thumb_2.jpg');
        @unlink(__DIR__ . DIRECTORY_SEPARATOR . '../extra/Example.avi');
        @unlink(__DIR__ . DIRECTORY_SEPARATOR . '../extra/log/log.txt');
        @rmdir(__DIR__ . DIRECTORY_SEPARATOR . '../extra/log/');
        
    }
}

<?php namespace Folour\Flavy;

/**
 *
 * FFmpeg layer for Laravel 5.2
 * This is a fully reworked fork of http://github.com/rafasamp/sonus package.
 *
 * @author Vadim Bova <folour@gmail.com>
 * @link   http://github.com/folour | http://vk.com/folour
 *
 */

use Folour\Flavy\Extensions\Base;
use Folour\Flavy\Exceptions\CmdException;
use Folour\Flavy\Exceptions\NotWritableException;
use Folour\Flavy\Exceptions\FileNotFoundException;
use BadMethodCallException;

class Flavy extends Base
{
    /**
     * Flavy version
     */
    const VERSION = '1.0.2';

    /**
     *
     * Array of command parameters from builder
     *
     * @var array
     */
    protected $parameters = [];

    /**
     *
     * Output file path
     *
     * @var string
     */
    protected $outputPath;

    /**
     *
     * Log path
     *
     * @var string
     */
    protected $logPath;

    /**
     *
     * Array of possible methods for execute
     *
     * @var array
     */
    private $nextContext = 'input';

    /**
     *
     * Flavy constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    /**
     *
     * Returns array (for json format) or string (for other formats) with file information
     *
     * @param string $file File path
     * @param string $format output format, supported json, xml and csv
     * @param bool $decode decode json to array
     *
     *
     * @return array|string
     * @throws FileNotFoundException
     * @throws CmdException
     * @throws BadMethodCallException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    public function info($file, $format = 'json', $decode = true)
    {
        $this->isPossible('input');
        if (!file_exists($file)) {
            throw new FileNotFoundException('The input file "' . $file . '" not exists!');
        }

        $format = in_array($format, ['json', 'xml', 'csv']) ? $format : 'json';
        $data = $this->runCmd('get_file_info', [$this->config['ffprobe_path'], $format, $file]);
        if ($format === 'json' && $decode === true) {
            return json_decode($data, true);
        }

        return $data;

    }

    /**
     *
     * Create thumbnails from video
     *
     * @param string $file input file
     * @param string $outputPath output file path template
     * @param int $count thumbnails count
     * @param int|null $interval frames interval in seconds
     *
     * @return bool|string
     * @throws FileNotFoundException
     * @throws NotWritableException
     * @throws CmdException
     * @throws BadMethodCallException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    public function thumbnail($file, $outputPath, $count = 1, $interval = null)
    {
        $this->isPossible('input');
        if (!file_exists($file)) {
            throw new FileNotFoundException('The input file "' . $file . '" not exists!');
        }
        if (!is_writable(dirname($outputPath))) {
            throw new NotWritableException('The output path "' . $outputPath . '" is not writable!');
        }
        if ($interval === null) {
            $interval = 10;
            $info = $this->info($file);
            if (is_array($info)) {
                $duration = $this->timestamp(explode('.', $info['format']['duration'])[0]);
                $interval = (int)round($duration / $count);
            }
        }

        return $this->runCmd('get_thumbnails', [
            $this->config['ffmpeg_path'],
            $file,
            $interval,
            $count,
            $outputPath
        ]);
    }

    /**
     *
     * Set input file
     *
     * @param $file
     * @return $this
     * @throws FileNotFoundException
     * @throws BadMethodCallException
     */
    public function from($file)
    {
        $this->isPossible('input');
        if (!file_exists($file)) {
            throw new FileNotFoundException('The input file "' . $file . '" not exists!');
        }

        $this->parameters[] = sprintf('%s -i "%s"', $this->config['ffmpeg_path'], $file);
        $this->nextContext = 'output';

        return $this;
    }

    /**
     *
     * Set output file
     *
     * @param string $outputPath
     *
     * @return $this
     * @throws NotWritableException
     * @throws BadMethodCallException
     */
    public function to($outputPath)
    {
        $this->isPossible('output');
        if (!is_writable(dirname($outputPath))) {
            throw new NotWritableException('The output path "' . $outputPath . '" is not writable!');
        }

        $this->outputPath = $outputPath;
        $this->nextContext = 'run_or_param';

        return $this;
    }

    /**
     *
     * Set audio codec
     *
     * @param string $codec
     * @return $this
     * @throws BadMethodCallException
     */
    public function aCodec($codec)
    {
        $this->isPossible('run_or_param');
        $this->parameters[] = sprintf('-acodec %s', $codec);

        return $this;
    }

    /**
     *
     * Set video codec
     *
     * @param string $codec
     * @return $this
     * @throws BadMethodCallException
     */
    public function vCodec($codec)
    {
        $this->isPossible('run_or_param');

        $this->parameters[] = sprintf('-vcodec %s', $codec);

        return $this;
    }

    /**
     *
     * Set audio bitrate
     *
     * @param int $bitrate
     * @return $this
     * @throws BadMethodCallException
     */
    public function aBitrate($bitrate)
    {
        $this->isPossible('run_or_param');
        $this->parameters[] = sprintf('-b:a %dk', $bitrate);

        return $this;
    }

    /**
     *
     * Set video bitrate
     *
     * @param int $bitrate
     * @return $this
     * @throws BadMethodCallException
     */
    public function vBitrate($bitrate)
    {
        $this->isPossible('run_or_param');
        $this->parameters[] = sprintf('-b:v %dk', $bitrate);

        return $this;
    }

    /**
     *
     * Set audio channels
     *
     * @param int $channels
     * @return $this
     * @throws BadMethodCallException
     */
    public function channels($channels)
    {
        $this->isPossible('run_or_param');
        $this->parameters[] = sprintf('-ac %d', $channels);

        return $this;
    }

    /**
     *
     * Set audio sample rate
     *
     * @param int $rate
     * @return $this
     * @throws BadMethodCallException
     */
    public function sampleRate($rate)
    {
        $this->isPossible('run_or_param');
        $this->parameters[] = sprintf('-ar %d', $rate);

        return $this;
    }

    /**
     *
     * Set video frame rate
     *
     * @param int $rate
     * @return $this
     * @throws BadMethodCallException
     */
    public function frameRate($rate)
    {
        $this->isPossible('run_or_param');
        $this->parameters[] = sprintf('-r %d', $rate);

        return $this;
    }

    /**
     *
     * Overwrite output file
     *
     * @return $this
     * @throws BadMethodCallException
     */
    public function overwrite()
    {
        $this->isPossible('run_or_param');
        $this->parameters[] = '-y';

        return $this;
    }

    /**
     * @param string $logPath
     * @return $this
     * @throws BadMethodCallException
     */
    public function logTo($logPath)
    {
        $this->isPossible('run_or_param');
        $this->logPath = $logPath;

        return $this;
    }

    /**
     *
     * Run a conversion process
     *
     * @throws Exceptions\CmdException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    public function run()
    {
        $this->parameters[] = sprintf('"%s"', $this->outputPath);
        if ($this->logPath !== null) {
            $this->parameters[] = sprintf('>"%s" 2>&1', $this->logPath);
        }

        $command = implode(' ', $this->parameters);

        $this->parameters = [];
        $this->nextContext = 'input';
        $this->logPath = $this->outputPath = null;

        $this->runCmd($command);
    }

    /**
     * @param string $context context name
     *
     * @return bool
     * @throws BadMethodCallException
     */
    private function isPossible($context)
    {
        if ($this->nextContext !== $context) {
            throw new BadMethodCallException('This method is not possible on this context');
        }

        return true;
    }
}

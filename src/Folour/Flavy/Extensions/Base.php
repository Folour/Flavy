<?php namespace Folour\Flavy\Extensions;

use Folour\Flavy\Exceptions\CmdException;

/**
 *
 * @author Vadim Bova <folour@gmail.com>
 * @link   http://github.com/folour | http://vk.com/folour
 *
 */
class Base extends Commands
{

    /**
     *
     * Flavy config
     *
     * @var array
     */
    protected $config = [
        'ffmpeg_path' => 'ffmpeg',
        'ffprobe_path' => 'ffprobe'
    ];

    /**
     *
     * FFmpeg information
     *
     * @var array
     */
    private $_info = [
        'formats' => [],

        'encoders' => [
            'audio' => [],
            'video' => []
        ],

        'decoders' => [
            'audio' => [],
            'video' => []
        ]
    ];

    /**
     * Base constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = array_replace($this->config, $config);
    }

    /**
     *
     * Returns array of supported formats
     *
     * @throws CmdException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @return array
     */
    public function formats()
    {
        if ($this->_info['formats'] === null) {
            $data = $this->runCmd('get_formats', [$this->config['ffmpeg_path']]);
            if (is_array($data)) {
                $this->_info['formats'] = array_combine($data['format'], $data['mux']);
            }
        }

        return $this->_info['formats'];
    }

    /**
     *
     * Returns array of audio and video encoders
     * [
     *     'audio' => [],
     *     'video' => []
     * ]
     *
     * @throws CmdException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @return array
     */
    public function encoders()
    {
        return $this->_info['encoders']['audio'] === [] ? $this->infoPrepare(true) : $this->_info['encoders'];
    }

    /**
     * @param bool $encoders
     * @return array
     * @throws CmdException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    private function infoPrepare($encoders = false)
    {
        $data = $this->runCmd('get_' . ($encoders ? 'encoders' : 'decoders'), [$this->config['ffmpeg_path']]);
        return array_map(
            function ($key, $type) use ($data) {
                $result = [];
                return $result[$type === 'A' ? 'audio' : 'video'][] = $data['format'][$key];
            }, array_keys($data['type']), $data['type']
        );
    }

    /**
     *
     * Returns array of audio and video decoders
     * [
     *     'audio' => [],
     *     'video' => []
     * ]
     *
     * @throws CmdException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @return array
     */
    public function decoders()
    {
        return $this->_info['decoders']['audio'] === [] ? $this->infoPrepare(false) : $this->_info['decoders'];
    }

    /**
     * @param string $format
     * @return bool
     * @throws CmdException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    public function canEncode($format = 'encoder')
    {
        return in_array($format, array_flatten($this->encoders()), true);
    }

    /**
     * @param string $format
     * @return bool
     * @throws CmdException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    public function canDecode($format = 'decoder')
    {
        return in_array($format, array_flatten($this->decoders()), true);
    }

    //Helpers

    /**
     * @param int|string $time timestamp for conversion
     * @param bool $isDate mode flag, if true - $time converts from hh::mm:ss string to seconds, else conversely
     * @return string
     */
    protected function timestamp($time, $isDate = true)
    {
        if ($isDate) {
            $time = explode(':', $time);

            return ($time[0] * 3600) + ($time[1] * 60) + (int)$time[2];
        }

        return gmdate('H:i:s', mktime(0, 0, $time));
    }
}

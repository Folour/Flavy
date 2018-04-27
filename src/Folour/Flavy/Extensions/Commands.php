<?php namespace Folour\Flavy\Extensions;

/**
 *
 * @author Vadim Bova <folour@gmail.com>
 * @link   http://github.com/folour | http://vk.com/folour
 *
 */

use Folour\Flavy\Exceptions\CmdException;
use Symfony\Component\Process\Process;

abstract class Commands
{
    /**
     *
     * Commands for get needed information from stdout
     *
     * @var array
     */

    protected $_cmd = [
        'get_formats' => [
            'regex' => '/(?P<mux>(D\s|\sE|DE))\s(?P<format>\S{3,11})\s/',
            'cmd' => '%s -formats',
            'returns' => ['format', 'mux']
        ],

        'get_encoders' => [
            'regex' => '/(?P<type>(A|V)).....\s(?P<format>\S{3,20})\s/',
            'cmd' => '%s -encoders',
            'returns' => ['type', 'format']
        ],

        'get_decoders' => [
            'regex' => '/(?P<type>(A|V)).....\s(?P<format>\S{3,20})\s/',
            'cmd' => '%s -decoders',
            'returns' => ['type', 'format']
        ],

        'get_file_info' => [
            'cmd' => '%s -v quiet -print_format %s -show_format -show_streams -pretty -i "%s" 2>&1'
        ],

        'get_thumbnails' => [
            'cmd' => '%s -i "%s" -vf fps=1/%d -vframes %d "%s"'
        ]
    ];

    /**
     *
     * Run command and return output
     *
     * @param string $cmd shell command or key of pre-defined commands
     * @param array $args Command arguments
     *
     * @return string|bool Command output
     * @throws CmdException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    protected function runCmd($cmd, $args = [])
    {
        $prop = isset($this->_cmd[$cmd]) ? $this->_cmd[$cmd] : [];
        $cmd = isset($this->_cmd[$cmd]) ? $this->_cmd[$cmd]['cmd'] : $cmd;

        $process = new Process(vsprintf($cmd, array_values($args)));
        $process->run();

        if ($process->isSuccessful()) {
            $output = $process->getOutput();

            if (isset($prop['regex'])) {
                if (preg_match_all($prop['regex'], $output, $matches)) {
                    return array_only($matches, $prop['returns']);
                }
            }

            return $output;
        }

        throw new CmdException($process->getErrorOutput());
    }
}

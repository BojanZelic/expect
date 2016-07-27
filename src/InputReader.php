<?php
/**
 * Created by PhpStorm.
 * User: bzelic
 * Date: 7/27/16
 * Time: 12:06 PM
 */

namespace Yuloh\Expect;

class InputReader
{
    private $cwd;
    private $cmd;

    public function __construct($cmd, $cwd)
    {
        $this->cmd = $cmd;
        $this->cwd = $cwd;
    }

    /**
     * @var resource[]
     */
    private $pipes;

    /**
     * @var resource
     */
    private $process;

    /**
     * Create the process.
     *
     * @return null
     * @throws \RuntimeException If the process can not be created.
     */
    public function createProcess()
    {
        $descriptorSpec = [
            ['pipe', 'r'], // stdin
            ['pipe', 'w'], // stdout
            ['pipe', 'r']  // stderr
        ];

        $this->process = proc_open($this->cmd, $descriptorSpec, $this->pipes, $this->cwd);

        if (!is_resource($this->process)) {
            throw new \RuntimeException('Could not create the process.');
        }

        stream_set_blocking($this->pipes[1], false);
    }

    /**
     * Close the process.
     *
     * @return null
     */
    public function closeProcess()
    {
        fclose($this->pipes[0]);
        fclose($this->pipes[1]);
        fclose($this->pipes[2]);
        proc_close($this->process);
    }
    
    public function read($buffer)
    {
        $buffer .= fread($this->pipes[1], 4096);
        $response = static::trimAnswer($buffer);
        
        return $response;
    }
    
    public function getPipe()
    {
        return $this->pipes[1];
    }


    /**
     * Determine if the process is running.
     *
     * @return boolean
     */
    public function isRunning()
    {
        if (!is_resource($this->process)) {
            return false;
        }

        $status = proc_get_status($this->process);

        return $status['running'];
    }


    /**
     * Returns a string with any newlines trimmed.
     *
     * @param  string $str
     * @return string
     */
    private static function trimAnswer($str)
    {
        return preg_replace('{\r?\n$}D', '', $str);
    }
    
    public function sendInput($input)
    {
        fwrite($this->pipes[0], $input);
    }
}

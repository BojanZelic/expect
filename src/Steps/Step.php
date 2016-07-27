<?php

namespace Yuloh\Expect\Steps;

abstract class Step
{
    public function __construct($output, $timeout = 0)
    {
        $this->output = $output;
        $this->timeout = $timeout;
    }

    private $output;
    private $timeout;

    /**
     * @return mixed
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param mixed $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @return mixed
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param mixed $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }
}

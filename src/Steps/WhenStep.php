<?php

namespace Yuloh\Expect\Steps;

class WhenStep extends Step
{
    private $callback;
    
    private $send;
    
    public function __construct($output, $send, $callback)
    {
        $this->send = $send;
        $this->callback = $callback;
        parent::__construct($output, 0);
    }

    /**
     * @return mixed
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param mixed $callback
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    /**
     * @return mixed
     */
    public function getSend()
    {
        return $this->send;
    }

    /**
     * @param mixed $send
     */
    public function setSend($send)
    {
        $this->send = $send;
    }
}

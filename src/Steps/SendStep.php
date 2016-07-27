<?php

namespace Yuloh\Expect\Steps;

class SendStep extends Step
{
    private $send;
    
    public function __construct($send, $timeout = 0)
    {
        $this->send = $send;
        
        parent::__construct($send, $timeout);
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

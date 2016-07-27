<?php

namespace Yuloh\Expect;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yuloh\Expect\Exceptions\ProcessTimeoutException;
use Yuloh\Expect\Exceptions\UnexpectedEOFException;
use Yuloh\Expect\Exceptions\ProcessTerminatedException;
use Yuloh\Expect\Steps\ExpectStep;
use Yuloh\Expect\Steps\SendStep;
use Yuloh\Expect\Steps\WhenStep;

class Expect
{
    /**
     * The default timeout for expectations.
     */
    const DEFAULT_TIMEOUT = 9999999;

    /**
     * @var string
     */
    private $cmd;

    /**
     * @var string
     */
    private $cwd;

    /**
     * @var resource[]
     */
    private $pipes;

    /**
     * @var resource
     */
    private $process;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

        /**
      * @var WhenStep[]
      */
    private $optional_steps = [];
    
    private $input_reader;

    /**
     * @param string $cmd
     * @param string $cwd
     * @param LoggerInterface $logger
     */
    private function __construct($cmd, $cwd = null, LoggerInterface $logger = null)
    {
        $this->cmd    = $cmd;
        $this->cwd    = $cwd;
        $this->logger = $logger ?: new NullLogger();
        $this->input_reader = new InputReader($cmd, $cwd);
    }

    /**
     * Spawn a new instance of Expect for the given command.
     * You can optionally specify a working directory and a
     * PSR compatible logger to use.
     *
     * @param  string $cmd
     * @param  string $cwd
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public static function spawn($cmd, $cwd = null, LoggerInterface $logger = null)
    {
        return new self($cmd, $cwd, $logger);
    }

    /**
     * Register a step to expect the given text to show up on stdout.
     * Expect will block and keep checking the stdout buffer until your expectation
     * shows up or the timeout is reached, whichever comes first.
     *
     * @param  string $output
     * @param  int    $timeout
     * @return $this
     */
    public function expect($output, $timeout = self::DEFAULT_TIMEOUT)
    {
        $this->steps[] = new ExpectStep($output, $timeout);

        return $this;
    }

    /**
     * Register a step to send the given text on stdin.
     * A newline is added to each string to simulate pressing enter.
     *
     * @param  string $input
     * @return $this
     */
    public function send($input)
    {
        if (stripos(strrev($input), PHP_EOL) === false) {
            $input = $input . PHP_EOL;
        }

        $this->steps[] = new SendStep($input);

        return $this;
    }

    public function when($output, $send = '', $callback = null)
    {
        $this->optional_steps[] = new WhenStep($output, $send, $callback);

        return $this;
    }

    /**
     * Run the process and execute the registered steps.
     * The program will block until the steps are completed or a timeout occurs.
     *
     * @return null
     *
     * @throws \RuntimeException If the process can not be created.
     * @throws \Yuloh\Expect\Exceptions\ProcessTimeoutException    if the process times out.
     * @throws \Yuloh\Expect\Exceptions\UnexpectedEOFException     if an unexpected EOF is found.
     * @throws \Yuloh\Expect\Exceptions\ProcessTerminatedException if the process is terminated
     * before the expectation is met.
     */
    public function run()
    {
        $this->input_reader->createProcess();
        
        foreach ($this->optional_steps as $optional_step) {
            $this->input_reader->addListener($optional_step);
        }

        foreach ($this->steps as $step) {
            if ($step instanceof ExpectStep) {
                $expectation = $step->getOutput();
                $timeout     = $step->getTimeout();
                $this->waitForExpectedResponse($expectation, $timeout);
            } elseif ($step instanceof SendStep) {
                $input = $step->getOutput();
                $this->sendInput($input);
            }
        }

        $this->input_reader->closeProcess();
    }
    
    private function runOptionalSteps($response)
    {
        foreach ($this->optional_steps as $key => $step) {
            if (fnmatch($step->getOutput(), $response)) {
                $this->sendInput($step->getSend());
                $function = $step->getCallback();
                if (is_callable($function)) {
                    $function($response);
                }

                unset($this->optional_steps[$key]);
            }
        }
    }
    

    /**
     * Wait for the given response to show on stdout.
     *
     * @param  string $expectation The expected output.  Will be glob matched.
     * @return null
     * @throws \Yuloh\Expect\Exceptions\ProcessTimeoutException if the process times out.
     * @throws \Yuloh\Expect\Exceptions\UnexpectedEOFException if an unexpected EOF is found.
     * @throws \Yuloh\Expect\Exceptions\ProcessTerminatedException if the process is terminated
     * before the expectation is met.
     */
    private function waitForExpectedResponse($expectation, $timeout)
    {
        $response           = null;
        $lastLoggedResponse = null;
        $buffer             = '';
        $start              = time();

        while (true) {
            if (time() - $start >= $timeout) {
                throw new ProcessTimeoutException();
            }

            if (feof($this->input_reader->getPipe())) {
                throw new UnexpectedEOFException();
            }

            if (!$this->input_reader->isRunning()) {
                throw new ProcessTerminatedException();
            }

            $response = $this->input_reader->read($buffer);

            if ($response !== '' && $response !== $lastLoggedResponse) {
                $lastLoggedResponse = $response;
                $this->logger->info("Expected '{$expectation}', got '{$response}'");
            }

            if (fnmatch($expectation, $response)) {
                return;
            }

              $this->runOptionalSteps($response);
        }
    }

    /**
     * Send the given input on stdin.
     *
     * @param  string $input
     * @return null
     */
    private function sendInput($input)
    {
        $this->logger->info("Sending '{$input}'");

        $this->input_reader->sendInput($input);
    }
}

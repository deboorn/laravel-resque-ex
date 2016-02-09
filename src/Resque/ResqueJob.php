<?php namespace Resque;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job as Job;
use Illuminate\Foundation\Bus\DispatchesCommands;

/**
 * Class ResqueJob
 *
 * @package Resque
 */
class ResqueJob extends Job implements JobContract
{
    use DispatchesCommands;

    /**
     * @var \Resque_Job
     */
    public $job;

    /**
     * @var array
     */
    public $args;

    /**
     * @var string
     */
    public $queue;

    /**
     * Setup container with App
     */
    public function setUp()
    {
        $this->container = app();
        \Resque_Event::listen('onFailure', array($this, 'onFailure'));
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return $this->args['attempts'];
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int $delay
     * @return void
     */
    public function release($delay = 0)
    {
        // record delay and increment attempts
        // push on to queue for retry after delay
        $payload = $this->args;
        $payload['delay'] = $delay;
        $payload['attempts']++;
        $payload = json_encode($payload);
        (new ResqueQueue())->laterRaw($delay, $payload, $this->queue);
    }

    /**
     * Get the delay in seconds for the job
     */
    public function delay()
    {
        return isset($this->args['delay']) ? $this->args['delay'] : 0;
    }

    /**
     *
     *
     * @param \Exception $e
     * @param \Resque_Job $job
     */
    public function onFailure(\Exception $e, \Resque_Job $job)
    {
        // Add exponential delay based on the job attempts and the provided delay seconds.
        // The delay will default to 30 seconds by default or when delay is set to zero.
        // A max delay of 2 hours will be used when exponential delay exceeds 2 hours
        // Example of delay in seconds: 30, 60, 90, 180, ... 7200
        $delay = $this->attempts() > 1 ? (pow(2, $this->attempts() - 2) * $this->delay()) : 30;
        $maxDelay = 60 * 60 * 2;
        if ($delay > $maxDelay) {
            $delay = $maxDelay;
        }
        $this->release($delay);
    }

    /**
     * Alias for Fire used by Resque Worker
     *
     */
    public function perform()
    {
        $this->fire();
    }

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire()
    {
        $this->resolveAndFire($this->args);
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return json_encode($this->args);
    }


}
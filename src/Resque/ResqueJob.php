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
        try{
            $this->resolveAndFire($this->args);
        }catch (\Exception $e){

        }
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

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return array_get(json_decode($this->job, true), 'attempts');
    }


}
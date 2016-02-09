<?php namespace Resque\Console;

use Illuminate\Console\Command;
use ResqueScheduler\ResqueScheduler;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Config;
use Resque;
use ResqueScheduler\Worker as Resque_Worker;
use Resque_Job;

/**
 * Class ListenSchedulerCommand
 * @package Resque\Console
 */
class ListenSchedulerCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'resque:schedulerlisten';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a resque scheduler worker';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Sets the Redis backend from config
     */
    public function setBackend()
    {
        // Configuration
        $config = Config::get('database.redis.default');

        if (!isset($config['host'])) {
            $config['host'] = '127.0.0.1';
        }

        if (!isset($config['port'])) {
            $config['port'] = 6379;
        }

        if (!isset($config['database'])) {
            $config['database'] = 0;
        }

        if (!isset($config['prefix'])) {
            $config['prefix'] = 'resque';
        }

        // Connect to redis
        Resque::setBackend($config['host'] . ':' . $config['port'], $config['database'], $config['prefix'], (isset($config['password']) ? $config['password'] : null));
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        // Read input
        $logLevel = $this->input->getOption('verbose') ? true : false;
        $queue = $this->input->getOption('queue');
        $interval = $this->input->getOption('interval');


        // Launch worker
        $queues = explode(',', $queue);
        $worker = new Resque_Worker($queues);
        $worker->logLevel = $logLevel;

        // Set backend
        $this->setBackend();

        fwrite(STDOUT, '*** Starting worker ' . $worker . "\n");
        $worker->work($interval);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['queue', null, InputOption::VALUE_OPTIONAL, 'The queue to listen on', 'default'],
            ['interval', null, InputOption::VALUE_OPTIONAL, 'Amount of time to delay failed jobs', 5],
        ];
    }

} // End ListenCommand

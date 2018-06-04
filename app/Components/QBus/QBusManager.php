<?php
namespace App\Components\QBus;

class QBusManager
{
	const MAIN_LOOP_USLEEP = 200000; /**< @type integer Main loop sleep time in micro seconds. */

	protected $processes = 3; /**< @type integer The number of processes to start. */
	protected $pids = array(); /**< @type array Array of process PIDs. */
	protected $parentPid; /**< @type integer The parent process id. */
	protected $currentProcess; /**< @type integer Cardinal process number (0, 1, 2, ...). */
	protected $runningProcesses; /**< @type integer The number of running processes. */

    protected $cluster;
    protected $topic;
    protected $group;

	public function __construct($cluster, $topic, $group, $processNum)
	{
        
        $this->setProcesses($processNum);
		$this->parentPid = posix_getpid();
        $this->cluster = $cluster;
        $this->topic = $topic;
        $this->group = $group;

        if (empty($group)) {
            $this->group = 'default';
        }

        // XXX ugly file path...
        require_once('/home/t/php/kafka_client/lib/kafka_client.php');

        // 禁止非线上环境使用default相关的group
        if (!app()->environment('production') && stripos($group, 'default') !== false) {
            throw new RuntimeException('QBus group "'.$group.'" is forbidden on non-production env');
        }

        register_shutdown_function(array($this, 'onShutdown'));

        pcntl_signal(SIGCHLD, array($this, 'onChildExited'));
		foreach(array(SIGTERM, SIGQUIT, SIGINT) as $nSignal) {
			pcntl_signal($nSignal, array($this, 'onSignal'));
		}
	}

	/**
	 * Checks if the server is running and calls signal handlers for pending signals.
	 *
	 * Example:
	 * @code
	 * while ($Server->run()) {
	 *     // do somethings...
	 *     usleep(200000);
	 * }
	 * @endcode
	 *
	 * @return @type boolean True if the server is running.
	 */
	public function run()
	{
		pcntl_signal_dispatch();
		return $this->runningProcesses > 0;
	}

	/**
	 * Waits until a forked process has exited and decreases the current running
	 * process number.
	 */
	public function onChildExited()
	{
		while (pcntl_waitpid(-1, $nStatus, WNOHANG) > 0) {
			$this->runningProcesses--;
		}
	}

	/**
	 * When a child (not the parent) receive a signal of type TERM, QUIT or INT
	 * exits from the current process and decreases the current running process number.
	 *
	 * @param  $nSignal @type integer Signal number.
	 */
	public function onSignal($nSignal)
	{
		switch ($nSignal) {
			case SIGTERM:
			case SIGQUIT:
			case SIGINT:
				if (($nPid = posix_getpid()) != $this->parentPid) {
					$this->_log("INFO: Child $nPid received signal #{$nSignal}, shutdown...");
					$this->runningProcesses--;
					exit(0);
				}
				break;
			default:
				$this->_log("INFO: Ignored signal #{$nSignal}.");
				break;
		}
	}

	/**
	 * When the parent process exits, cleans shared memory and semaphore.
	 *
	 * This is called using 'register_shutdown_function' pattern.
	 * @see http://php.net/register_shutdown_function
	 */
	public function onShutdown()
	{
		if (posix_getpid() == $this->parentPid) {
			$this->_log('INFO: Parent shutdown, cleaning memory...');
		}
	}

	/**
	 * Set the total processes to start, default is 3.
	 *
	 * @param  $nProcesses @type integer Processes to start up.
	 */
	private function setProcesses($processes)
	{
		$processes = (int)$processes;
		if ($processes <= 0) {
			return;
		}
		$this->processes = $processes;
	}

	/**
	 * Starts the server forking all processes and return immediately.
	 *
	 * Every forked process is connected to Apple Push Notification Service on start
	 * and enter on the main loop.
	 */
	public function start($callback)
	{
		for ($i = 0; $i < $this->processes; $i++) {
			$this->currentProcess = $i;
			$this->pids[$i] = $pid = pcntl_fork();
			if ($pid == -1) {
				$this->_log('WARNING: Could not fork');
			} else if ($pid > 0) {
				// Parent process
				$this->_log("INFO: Forked process PID {$pid}");
				$this->runningProcesses++;
			} else {
				// Child process
				try {
                    $this->consumer = new \Kafka_Consumer($this->cluster, $this->topic, $callback, $this->group);
                    $this->consumer->work();
                } catch (Exception $e) {
					$this->_log('ERROR: ' . $e->getMessage() . ', exiting...');
					exit(1);
				}
				exit(0);
			}
		}
	}

    //pcntl_signal_dispatch();

    public function _log($msg) 
    {
        echo $msg."\n";
    }
}

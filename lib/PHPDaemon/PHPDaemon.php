<?php

namespace PHPDaemon;

use Exception;

/**
 * Creates a 'daemon' with a given script.
 * Serves as a wrapper to execute scripts and
 * watch their process id, thus being able to start or stop it.
 *
 * Will create a .pid file with the script's filename in the script's file_path.
 * Will create a .log file with the script's filename in the script's file_path.
 */
class PHPDaemon
{
	/**
	 * The binary to use.
	 * Examples: php, yum.
	 *
	 * @var mixed
	 */
	protected $binary = null;

	/**
	 * Status.
	 *
	 * @var boolean
	 */
	protected $status = false;

	/**
	 * Filename for pid storage.
	 *
	 * @var string
	 */
	protected $pid = null;

	/**
	 * Script name.
	 *
	 * @var string
	 */
	protected $script = null;

	/**
	 * Script path.
	 *
	 * @var string
	 */
	protected $script_path = null;

	/**
	 * Internal errors template.
	 *
	 * @var array
	 */
	protected $_errors = array(
		'code' => null,
		'message' => null
	);

	/**
	 * Class name will be used to name the log file.
	 * {$this->name}.log
	 * Defaults to /dev/null device.
	 *
	 * @var string
	 */
	protected $log = '/dev/null';

	/**
	 * Public errors.
	 *
	 * @var mixed
	 */
	public $errors = null;

	/**
	 * Class constructor.
	 *
	 * @param  string $script Script to start with.
	 * @param  string $binary The binary to use.
	 * @return void
	 */
	public function __construct($script = '', $binary = '')
	{
		if ($script)
		{
			$this->setScript($script);
		}
		if ($binary)
		{
			$this->binary = $binary;
		}

		$this->initialize();
	}

	/**
	 * Set pid file path and name from the script to exec.
	 * Set log file path and name from the script to exec.
	 *
	 * @throws Exception Could not set pid.
	 * @return void
	 */
	private function initialize()
	{
		$this->pid = "{$this->script_path}.{$this->script}.pid";
		$this->log = "{$this->script_path}.{$this->script}.log";

		if (!touch($this->pid) || !touch($this->log))
		{
			throw new Exception('Could not initialize, make sure '.$this->script.' path is writtable.');
		}
	}

	/**
	 * Checks a given pid for its status.
	 *
	 * @param  int $pid Process id to check.
	 * @throws Exception Incorrect pid.
	 * @throws Exception Daemon is not running.
	 * @return bool
	 */
	private function checkPID($pid)
	{
		$running = false;

		try
		{
			if (!($running = exec("ps -p $pid| grep $pid -c") > 0))
			{
				throw new Exception('There\'s no associated process alive for '.$this->script);
			}
		}
		catch (Exception $e)
		{
			$running = false;
			$this->errors = array(
				'code' => $e->getCode(),
				'message' => $e->getMessage()
			) + $this->_errors;
		}

		return $running;
	}

	/**
	 * Get last saved / current pid in pid file.
	 *
	 * @return bolean
	 */
	private function getCurrentPID()
	{
		if (!file_exists($this->pid))
		{
			return (int) false;
		}

		return (int) file_get_contents($this->pid);
	}

	/**
	 * Kills a process by pid.
	 *
	 * @param  int $pid PID to kill.
	 * @return boolean
	 */
	private function kill($pid)
	{
		if ($this->checkPID($pid))
		{
			exec('kill '.$pid);
			file_put_contents($this->pid, null);
		}

		return $this->checkPID($pid);
	}

	/**
	 * Sets the php binary path to run the command with.
	 *
	 * @param  string $binary Binary path.
	 * @return void
	 */
	public function setBinary($binary)
	{
		$this->binary = $binary;
	}

	/**
	 * Set the script to then start().
	 *
	 * @param  string $script Dir with script name.
	 * @throws Exception Script is not present.
	 * @throws Exception Script is not running.
	 * @return boolean
	 */
	public function setScript($script)
	{
		$set = false;
		try
		{
			$this->script      = basename($script);
			$this->script_path = str_replace($this->script, '', $script);

			$set = true;
			$this->initialize();
		}
		catch (Exception $e)
		{
			$this->errors = array(
				'code' => $e->getCode(),
				'message' => $e->getMessage()
			) + $this->_errors;
		}

		return $set;
	}

	/**
	 * Checks the status of the handled script.
	 *
	 * @return boolean
	 */
	public function isAlive()
	{
		return $this->checkPID($this->getCurrentPID());
	}

	/**
	 * Stops the handled script.
	 *
	 * @var boolean
	 */
	public function stop()
	{
		return !$this->kill($this->getCurrentPID());
	}

	/**
	 * Starts the script.
	 * Also receives a script and sets it, then it runs it.
	 *
	 * @param  string $script Optional script to start.
	 * @throws Exception Could not init.
	 * @throws Exception Could not save pid.
	 * @return boolean
	 */
	public function start($script = '')
	{
		if ($script)
		{
			$this->setScript($script);
		}

		$initialized = false;
		try
		{

			$daemon_command = "{$this->binary} {$this->script_path}{$this->script} > {$this->log} 2>&1 & echo $! &";
			$daemon_pid     = exec($daemon_command, $output);

			if (!$daemon_pid)
			{
				throw new Exception('Could not initialize script: '.$this->script);
			}
			if (!file_put_contents($this->pid, $daemon_pid))
			{
				exec('kill '.$daemon_pid);
				throw new Exception('Could not save process id "'.$daemon_pid.'"… killing it.');
			}

			usleep(5000);
			if (!($initialized = $this->checkPID($daemon_pid)))
			{
				file_put_contents($this->pid, null);
				throw new Exception('Script died unexpectedly!');
			}
		}
		catch (Exception $e)
		{
			$this->errors = array(
				'code' => $e->getCode(),
				'message' => $e->getMessage()
			) + $this->_errors;
		}

		return $initialized;
	}
}

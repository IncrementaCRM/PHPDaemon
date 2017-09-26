<?php

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
	 * Default standard dev run.
	 *
	 * @var string
	 */
	protected $pid = '/var/run';

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
	 * Script's arguments.
	 * Set to private because it might contain credentials.
	 *
	 * @var string
	 */
	private $arguments = '';

	/**
	 * Internal error template.
	 *
	 * @var array
	 */
	private $_error = array(
		'code' => null,
		'message' => null
	);

	/**
	 * Log path if any given and log is set to true.
	 * Defaults to /dev/null device.
	 *
	 * @var string
	 */
	private $log_path = '';

	/**
	 * Weather to log daemon's output or not.
	 *
	 * @var string
	 */
	protected $log = true;

	/**
	 * Public error.
	 *
	 * @var mixed
	 */
	public $error = null;

	/**
	 * Class constructor.
	 *
	 * @param  string $script  Script to daemonize.
	 * @param  string $binary  The binary to use.
	 * @param  array  $options Log and logpath options.
	 * @return void
	 */
	public function __construct($script, $binary, $options = array())
	{
		if ($script)
		{
			$this->setScript($script);
		}
		if ($binary)
		{
			$this->setBinary($binary);
		}

		$this->initialize($options);
	}

	/**
	 * Set pid file path and name from the script to exec.
	 * Set log file path and name from the script to exec.
	 *
	 * @param  array $options Log options.
	 * @throws Exception      Could not set pid.
	 * @throws Exception      Could not set log.
	 * @return void
	 */
	private function initialize($options = array())
	{
		try
		{
			$this->pid = "{$this->script_path}/.{$this->script}.pid";

			if (is_array($options) && !empty($options))
			{
				$this->log      = isset($options['log']) ? (boolean) $options['log'] : false;
				$this->log_path = isset($options['log_path']) ? (string) $options['log_path'] : '';
			}
			if ($this->log && !is_dir($this->log_path))
			{
				$this->log_path = "{$this->script_path}/{$this->script}.log";
			}
			else if ($this->log && is_dir($this->log_path))
			{
				$this->log_path = "{$this->log_path}/{$this->script}.log";
			}
			if (!$this->log)
			{
				$this->log_path = '/dev/null';
			}
			if (!@touch($this->pid) && !@chmod($this->pid, 0600))
			{
				throw new Exception('Could not initialize process.');
			}
			if ($this->log && !@touch($this->log_path) && !@chmod($this->log_path, 0644))
			{
				throw new Exception('Could not initialize log.');
			}
		}
		catch (Exception $e)
		{
			$this->error = array(
				'code' => $e->getCode(),
				'message' => $e->getMessage()
			) + $this->_error;

			return false;
		}

		return true;
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
			$this->error = array(
				'code' => $e->getCode(),
				'message' => $e->getMessage()
			) + $this->_error;
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
	private function setBinary($binary)
	{
		$this->binary = $binary;
	}

	/**
	 * Exxtracts arguments and script string.
	 *
	 * @var string $script The whole script with or without args.
	 */
	private function extract($script = '')
	{
		$extracted = array(
			'script' => '',
			'arguments' => ''
		);
		preg_match_all('/(\S+)/', $script, $matches);

		if (isset($matches[0]))
		{
			$extracted['script'] = $matches[0][0];
			unset($matches[0][0]);
			$extracted['arguments'] = implode(' ', $matches[0]);
		}

		return $extracted;
	}

	/**
	 * Set the script to then start().
	 *
	 * @param  string $script Dir with script name.
	 * @throws Exception Script is not present.
	 * @throws Exception Script is not running.
	 * @return boolean
	 */
	private function setScript($script)
	{
		$set = false;
		try
		{
			$extracted         = $this->extract($script);
			$this->script      = basename($extracted['script']);
			$this->arguments   = $extracted['arguments'];
			$this->script_path = str_replace($this->script, '', $extracted['script']);

			$set = true;
		}
		catch (Exception $e)
		{
			$this->error = array(
				'code' => $e->getCode(),
				'message' => $e->getMessage()
			) + $this->_error;
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
	 * @throws Exception      Could not init.
	 * @throws Exception      Could not save pid.
	 * @return boolean
	 */
	public function start()
	{
		$initialized = false;
		try
		{
			$daemon_command = "{$this->binary} {$this->script_path}{$this->script} {$this->arguments}";
			$daemon_command = "{$daemon_command} > {$this->log_path} 2>&1 & echo $! &";
			$daemon_pid     = exec($daemon_command, $output);

			if (!$daemon_pid)
			{
				throw new Exception('Could not initialize script: '.$this->script);
			}
			if (!@file_put_contents($this->pid, $daemon_pid))
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
			$this->error = array(
				'code' => $e->getCode(),
				'message' => $e->getMessage()
			) + $this->_error;
		}

		return $initialized;
	}
}

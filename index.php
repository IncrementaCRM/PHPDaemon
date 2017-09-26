<?php

/***************************************************
* PHPDaemon Example
***************************************************/

// Start by including the class (if 1 level down).
include_once dirname(__FILE__).'/lib/PHPDaemon.php';

/***************************************************
* Prepare arguments.
***************************************************/

/**
 * Binary file path (per se).
 *  · php
 *  · bash
 *
 * You could run '$ which php' in console
 * and get binary's filepath.
 *
 * @var string $binary Binary filepath.
 */
$binary = '/opt/local/bin/php';

/**
 * The daemonized † filepath & filename.
 *
 * @var string $script Filepath & filename.
 */
$script = dirname(__FILE__).'/daemon/example.php';

/**
 * Logging options.
 *
 * @var array $options Includes log & log_path.
 */
$options = array(
	'log' => true,
	'log_path' => '/Users/Adrian/Desktop'
);

/***************************************************
* †††††††††††††††††††††††††††††††††††††††††††††††††
***************************************************/

// Instance receives 2 arguments.
$PHPDaemon = new PHPDaemon($script, $binary, $options);

// First off we want to check if there isn't a
// script running already and cut its head.
if ($PHPDaemon->isAlive())
{
	echo mb_convert_encoding('&#x1F608;', 'UTF-8', 'HTML-ENTITIES');
	echo ' Your daemon is alive and running!'.PHP_EOL;
}
else
{
	// Start the daemon else check errors.
	if ($PHPDaemon->start())
	{
		echo PHP_EOL.'You\'ve unleashed the beast!  ';
		echo mb_convert_encoding('&#x1F608;', 'UTF-8', 'HTML-ENTITIES').PHP_EOL;
	}
	else
	{
		echo $PHPDaemon->error['message'].PHP_EOL;
	}
}

// Stoping and checking errors.
if ($PHPDaemon->stop())
{
	echo mb_convert_encoding('&#x1F608;', 'UTF-8', 'HTML-ENTITIES');
	echo ' Your daemon has been stopped from deminishing the world!'.PHP_EOL;
}
else
{
	// Print out error message.
	echo $PHPDaemon->error['message'].PHP_EOL;
}

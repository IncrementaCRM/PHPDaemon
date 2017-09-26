# Introduction

With PHPDaemon you'll be able to run simple one-time daemons in any case
you find yourself in a situation where you need to run an external script,
meaning you need a Helper in your web project or what not.

This class works by storing a pid **inside** your script's dir as `script_file.pid`.

Script output (logging) will be set in `$options` with the keys `(boolean) log` and `(string) log_path`.

If `log` is set to true and no `log_path` is provided or is invalid it will log in the script's directory.

If `log` is set to true and `log_path` is provided and valid it will log into the given log_path.

**Log's name will be your `script_file` name ending with `.log`**.


**Example:**
For the **pid** `/your/daemon/path/to/script_file.php` would be `/var/run/script_file.php.pid`
and for the **log** `/your/daemon/path/to/script_file.php` without a log_path, it would be `/your/daemon/path/to/script_file.php.log`.

### PHP Version

This class is compatible with **PHP 5.3.0 and above** due to the use of namespaces.

### Installing
Add this library to your [composer](https://packagist.org/packages/adrian0350/php-daemon) configuration.
In composer.json:
```json
  "require": {
    "adrian0350/php-daemon": "1.*"
  }
```

#### OR

If you're using bash.
```
$ composer require adrian0350/php-daemon
```

### Usage
For usage just call the methods from your PHPDaemon instance object.
```
<?php

// Start by including the class (if 1 level down).
include_once dirname(__FILE__).'/lib/PHPDaemon.php';

```
### Usage — instantiating
Prepare three arguments you'll need.
* Script's **compatible** binary file path.
* Daemon script file path.
```
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
$script = dirname(__FILE__).'/daemon/test.php';

/**
 * Logging options.
 *
 * @var array $options Includes log & log_path.
 */
$options = array(
	'log' => true,
	'log_path' => '/your/log/path'
);

// Instance receives 3 arguments.
$PHPDaemon = new PHPDaemon\PHPDaemon($script, $binary, $options);
```
### Usage — instance handling
```
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
		echo 'You\'ve unleashed the beast!'.PHP_EOL;
	}
	else
	{
		echo $PHPDaemon->errors['message'].PHP_EOL;
	}
}
```
### Usage — stoping daemonized scripts
```
// Stoping and checking herrors.
if ($PHPDaemon->stop())
{
	echo mb_convert_encoding('&#x1F608;', 'UTF-8', 'HTML-ENTITIES');
	echo ' Your daemon has been stopped from deminishing the world!'.PHP_EOL;
}
else
{
	// Print out error message.
	echo $PHPDaemon->errors['message'];
}
```

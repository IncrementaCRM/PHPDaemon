<?php

if (!function_exists('get_called_class'))
{
	throw new Exception('PHPDaemon needs to be run on PHP >= 5.3.0.');
}
require_once dirname(__FILE__).'/PHPDaemon/PHPDaemon.php';

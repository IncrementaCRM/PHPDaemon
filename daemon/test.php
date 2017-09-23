#!/usr/bin/php
<?php

/***************************************************
* Daemon Test
***************************************************/

// Just start a forever loop with an ouput.
// Of course this can be any type of worker.
while (true)
{
	echo mb_convert_encoding('&#x1F608;', 'UTF-8', 'HTML-ENTITIES');
	echo ' Ah ah ah, you didn\'t say the magic word!'.PHP_EOL;
	sleep(2);
}

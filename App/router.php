<?php 
/*
	File Name 		:	router.php
	Location  		: 	App
	Last Modified 	:	2018-12-06
	Modified By 	:	Kanagu
*/


/* Please required  */

$router =array(
	'api' => 'handler->api',
	'sample' => 'handler->sample',
	'notfound' => 'handlers->notfound',
	'ping' => 'handlers->ping',
	'users'=> 'handlers->users',
	'Tasks'=> 'handlers->tasks',
	'Meetings'=> 'handlers->meetings',
	'test'=> 'handlers->test'
);

return $router;
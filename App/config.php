<?php 
/*
* Create and export  configuration variables
*
*/

//Container for all the environment
$environments = [
		'staging' => [
			'httpPort' => 9001,
			'httpsPort' => 9002,
			'APP_ENV' => 'staging',
			'BASEDIR' => __DIR__,

		],
		'production' =>[
			'httpPort' => 6001,
			'httpsPort' => 6002,
			'APP_ENV' => 'production',
			'BASEDIR' => __DIR__,
		]
];

// Determine which environment was passed as a command-line arugument
$currentEnvironment = gettype(getenv('ENV')) == 'string' ? strtolower(getenv('ENV')) : 'staging';

//var_dump($currentEnvironment);

// check that the current environment is one of the environents above, if not, default to staging
$environmentToExport = gettype($environments[$currentEnvironment]) == 'array' ? $environments[$currentEnvironment] : $environments['staging'];

return $environmentToExport;

<?php
/* 
	Primary file for swoole http server

*/

//Dependencies
//define ("root_path",'/home/kanagu/Desktop/tasks');
	define ("root_path",'/usr/share/nginx/html/uploaded_files');

$config = require 'App/config.php';   	// Loading configuration from config.php file.
//require './lib/data.php';        	// User data logic test file for authentication todo : update session information for secure athentication.	
//require './lib/handlers.php';		// Request handler file for manage user request and response. 
require 'App/autoload.php';
require __DIR__ . '/vendor/autoload.php';
//require __DIR__ . '/lib/loader.php';

//TESTING
// @TODO delete this

//$callback = $data1->delete('test','newfile3');
//var_dump($data1->data);
define('Basedir',__DIR__);

if(!isset($table)){
	$table = new swoole_table(1024);
	$table->column('name', swoole_table::TYPE_STRING, 64); 
	$table->column('logcount', swoole_table::TYPE_INT, 4);       //1,2,4,8
	$table->create();
}


//Create Swoole Http & Https Server
$http = new swoole_http_server("0.0.0.0", $config['httpPort']);
//$https = $http->addListener("0.0.0.0", $config['httpsPort'],  SWOOLE_SOCK_TCP|SWOOLE_SSL);

// Set worker Number, Max Connection & Max request
$http->set([
			'reactor_num' => 4,
      'worker_num'=>8, 
      'max_connection' => 1024,
    	'max_request' => 1000000,
    	'enable_static_handler' => true,
    	'document_root' => __DIR__.'/Public/',
    	'upload_tmp_dir' => __DIR__.'/tmp/',
    	'Log_file' => __DIR__.'/logs/swoole.log',
    	'package_max_length' => 200000000,
    	'buffer_output_size' => 32 * 1024 *1024,
     ]);
// Set SSL Cettification & Enable Http Protocol for Listening Port
/*$https->set([
	'ssl_cert_file' => __DIR__.'/App/https/local.crt',
	'ssl_key_file' => __DIR__.'/App/https/local.key',
	'open_http_protocol' => true,
	//'open_http2_protocol' => true ,
]);  */

// Start Http & Https Server
$http->on("start", function ($server) use($config){
    echo "Swoole http server is started at http://127.0.0.1:".$config['httpPort']." in " , $config['APP_ENV']." Mode\n";
    echo "Swoole https server is started at https://127.0.0.1:".$config['httpsPort']." in " , $config['APP_ENV']." Mode\n";
});


/*
$data1 = new data();
$callback = $data1->create('test','newfile3',json_encode(array("hai"=>"how are you")));
var_dump($data1->data); */

//$data1 = new data();
//var_dump($data1);
//$callback = $data1->read('test','newfile');
//var_dump($data1->data);

// Handle request for http server
$http->on("request", function ($request,$response) {
    //var_dump($request);
	
	//Pass the request to unifiedserver function
	unifiedserver($request,$response);
});
//Hadle request for https server
/*$https->on("request", function ($request,$response) {
	//Pass the request to unifiedserver function
	unifiedserver($request,$response);
});  */

//Start http & https servers, Single request is enough for both http & https servers, no need to call for https
$http->start();

//All the server logic for both http and https server
function unifiedserver($request,$response){
	$start_time = microtime(true); 
	//var_dump($request);	
	//Get tge URL and parse it
	$parsedURL = $request->server['request_uri'];
	//var_dump($parsedURL);

	//GEt the path
	$path = $parsedURL;
	//var_dump($path);

	 $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Methods', 'OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'x-requested-with,session_id,Content-Type,token,Origin');
        $response->header('Access-Control-Max-Age', '86400');
      $response->header('Access-Control-Allow-Credentials', 'true');

      if ($request->server['request_method'] == 'OPTIONS') {
            $response->status(200);
            $response->end();
            return;
        };
	
	//Get trimmed path
	$trimmedPath = trim($path, '/');

	//var_dump($trimmedPath);
	$hpath = explode("/",$trimmedPath);
	//var_dump($hpath);
	if($hpath){
		$hpath = $hpath[0];
	}
	

	//Get the query string as an object
	$queryStringObject = json_encode($request->get);

	//Get the HTTP Method
	$method = strtolower($request->server['request_method']);

	//Get the headers as an object
	$headers = json_encode($request->header);
	//Get the payload, if any
	 //utf8_decode(string) 
	$buffer = '';
	$buffer = $request->rawContent();
	$files = '';
	$files = $request->files;
	//var_dump("RAW Contenst :" .$buffer);
	
	if(isset($request->cookie["auth"])){
		$token = $request->cookie["auth"];
	}else{
		$token = NULL;
	}
	//var_dump($token);
	require('App/router.php');
	
	//Choose the handler this request should go to, if one is not found use the not found.
	$chosenHandler = isset($router[$hpath])  ? $hpath : "notfound";
	
	//var_dump($router[$trimmedPath]);
	//construct the data object to send to the handler
	$data = ['trimmedPath'=> $trimmedPath,
			'queryStringObject'=> $queryStringObject,
			'method'=>$method,
			'headers'=>$headers,
			'payload'=>$buffer,
			'token' => $token,
			'files'=>$files
		];
			
	//Route the request to the handler specified in the router
		//$response->end("Hai");	

	$handler = new handlers;
	
	$handler->$chosenHandler($data,function($statusCode,$payload,$content_type=null) use ($response,$start_time,$request){
		//use the status code called back by the handler, or default is 200
		$statusCode = gettype($statusCode) == 'integer' ? $statusCode : 200;

		//var_dump(gettype($payload));

		//var_dump($payload);
		
		//use the payload called back by the handler, or default to empty object
		if(isset($payload["token"])&&$payload["token"]!="delete"){
			//var_dump($payload["token"]);
			//var_dump($payload["token"]);
			$params = session_get_cookie_params();
			$response->cookie(
	            "auth",
	            $payload["token"],
	            $params['lifetime'] ? time() + $params['lifetime'] : time()+60*60*24,
	            $params['path'],
	            $params['domain'],
	            $params['secure'],
	            $params['httponly']
        	); 
			
		}else{
			var_dump("I m here no token area".$payload["token"]);
			$params = session_get_cookie_params();
			$response->cookie(
	            "auth",
	            "",
	            $params['lifetime'] ? time() + $params['lifetime'] : time()+60*60*24,
	            $params['path'],
	            $params['domain'],
	            $params['secure'],
	            $params['httponly']
        	); 
		}
		/*}else if(isset($payload["token"])&& $payload["token"]=="delete"){
			//var_dump("dafdfl;asfasdflk lsdf kdsf s");
			$params = session_get_cookie_params();
			$response->cookie(
	            "auth",
	            "",
	            $params['lifetime'] ? time() + $params['lifetime'] : time()+60*60,
	            $params['path'],
	            $params['domain'],
	            $params['secure'],
	            $params['httponly']
        	); */
		//}else{
		//	var_dump("--------------------------------------------------");
			//var_dump($payload);
		//	var_dump("--------------------------------------------------");
		//}	

		if(isset($payload["output"])){
			$payload = gettype($payload["output"]) == 'array' ? json_encode($payload["output"])  : $payload["output"];
		}else{
			$payload = gettype($payload) == 'array' ? json_encode($payload)  : $payload;
			//var_dump($payload);
		} 

		//var_dump("Content Type :". $content_type);
		//var_dump($payload);
		//Convert the payload to a string
		//$payloadString = json_encode($payload);
		$payloadString = $payload;
		$fileoutput = json_decode($payload);
		//Return the response;
		//
		$response->status($statusCode);
		if($content_type=='html'){
			$response->header('Content-Type', 'text/html');
		}else if($content_type=='text'){
			$response->header('Content-Type', 'text/plain');	
		}else if($content_type=='img'){
			$response->header('Content-Type', 'image/png');
		}else if($content_type=='file'){
			
			$response->header('filename',$fileoutput->fname);
			$response->header('Content-type', $fileoutput->type);
			$response->header('Content-Disposition', 'attachment');
			//var_dump($fileoutput->path);
			$response->sendfile($fileoutput->path);
		}else if($content_type=='memberlogout'){
	    	//$response->header('Content-Type', 'text/html');
			//$response->redirect("/member", 301);
		}else if($content_type=='redirect'){
			$response->redirect($payloadString, 301);
		}else{
         	$response->header('Content-Type', 'application/json');	
		}
		
		//var_dump($payloadString);
		//Send the response
		//$response->header('jwt', '1234567890.fafajlfjaadasfasfa.wera9id9e0');
		if($content_type!='redirect'){
		if($payloadString){
			if($content_type=='file'){
				//$response->sendfile($payloadString);
			}else{
				$response->write($payloadString);
				$response->end();
			}
		
		}
		}
		$end_time = microtime(true);
		$req = $request->server["request_uri"];
		$execution_time = ($end_time - $start_time); 
		echo "Execution time of  $req  =  $execution_time sec\n"; 
		//'Session key value: '.$_SESSION['key'].'<br>Session name: '.session_name().'<br>Session ID: '.session_id()
		//var_dump('Returning this response ' . $statusCode . $payloadString);

	});
	// Log the request path
	//echo 'Request received on path : ' . $trimmedPath. ' With this method ' . $method . ' and with this query string parameters' . $queryStringObject;
	//echo 'Request received with these headers' . $headers;
}



function generateRand_uuid ( $prefix = 'CM' ) {
	// Perfect for: UNIQUE ID GENERATION
	// Create a UUID made of: PREFIX:TIMESTAMP:UUID
	$my_random_id = $prefix;
	$my_random_id .= chr ( rand ( 65, 90 ) );
	$my_random_id .= time ();
	$my_random_id .= uniqid ( $prefix );
	return $my_random_id;
}









<?php
//namespace node\lib;
require_once Basedir.'/App/Models/Model.php';
require_once Basedir.'/App/Models/CaptchaModel.php';
require_once Basedir.'/src/DeepstreamClient.php';
use \Firebase\JWT\JWT;
use Swoole\Coroutine as co;
use Swoole\Timer;
class Api
{
	private $db;
	private $server;
	private $swoole_mysql;
	private $status;
	private $ctype;
	
	Public function __construct(){
		$this->model = new Model();

		$this->server = array(
		    'host' => 'shiwin.co.in',
		    //'host' => 'localhost',
		    'user' => 'ubuntu',
		    //'user' => 'root',
		    'password' => 'caminven',
		    'database' => 'sim',
		    'charset' => 'utf8',
		    'timeout' => 60,
		    //'strict_type' => false,  /// / Open strict mode, the returned field will automatically be converted to a numeric type
    		//'fetch_mode' => true, 
    		
		);
		$this->swoole_mysql = new Swoole\Coroutine\MySQL();
		$this->swoole_mysql->connect($this->server);
		$this->status = 200;
		$this->ctype = "json";

		Timer::tick(6000000, function () {
    	self::run();
		});

	}

	private function run()
	{
		var_dump(date('h:i:s A'));
		$lastdate ="";
		$qclients = "SELECT * FROM sim.sim_clients;";
		$result = $this->swoole_mysql->query($qclients);
		foreach ($result as $clients) {
			$cshare = $clients["share"];
			$cjdate = $clients["joindate"];

			 $accbal = "select round(max(acc_bal),2) as accbal, max(growth) as growth from client_trades ct  where ct.updated =1 and ct.uid =".$clients["uid"]." order by close_time";
			 $result3 = $this->swoole_mysql->query($accbal);
			 var_dump($result3);
			 $accbal  = $result3[0]["accbal"];
			 $growth  = $result3[0]["growth"];

 		   $qmt = "SELECT * FROM master_trades mt where ticket NOT IN (select ticket from client_trades ct  where ct.updated =1 and ct.uid =".$clients["uid"].") and mt.open_time > '$cjdate' and mt.type !='balance'" ;
 		   $result2 = $this->swoole_mysql->query($qmt);
 		   foreach ($result2 as $mt) {
 		   		
 		   		//var_dump("Close Date ".$mt["close_time"]);
 		   		$ctime = date_create($mt["close_time"]);
 		   		if($mt["close_time"]=="0000-00-00 00:00:00"){
 		   			$recdate = date('Y-m-d');
 		   		}else{
 		   			$recdate = date_format($ctime, "Y-m-d") ;	
 		   		}
 		   		
 		   		var_dump($lastdate. "     ".$recdate);
 		   		if($lastdate!=$recdate){
 		   			$lastdate = $recdate;	
 		   			//var_dump("https://api.exchangeratesapi.io/$recdate?base=USD&symbols=INR");
 		   			$content =     file_get_contents("https://api.exchangeratesapi.io/$recdate?base=USD&symbols=INR");
 		   			$dec_content = json_decode($content);
 		   			$cur_rate  = round($dec_content->rates->INR,2);
 		   		}
 		   		
 		   		var_dump("Current Rate ".$cur_rate);
 		   		
 		   		$uid = $clients["uid"];
 		   		$uacc =$mt["uacc"];
 		   		$ticket = $mt["ticket"];
 		   		$status = $mt["status"];
 		   		$type = $mt["type"];
 		   		$lot_size = $mt["lot_size"];
 		   		$open_time = $mt["open_time"];
 		   		$close_time = $mt["close_time"];
 		   		$symbol = $mt["symbol"];
 		   		$magic_number = $mt["magic_number"];
 		   		$lots = $mt["lots"];
 		   		$open = $mt["open"];
 		   		$close = $mt["close"];
 		   		$stop_loss = $mt["stop_loss"];
 		   		$take_profit = $mt["take_profit"];
 		   		$profit = ($mt["profit"]*$cshare/100)*$cur_rate;
 		   		$swap = ($mt["swap"]*$cshare/100)*$cur_rate;
 		   		$commission = ($mt["commission"]*$cshare/100)*$cur_rate;
 		   		$net_profit = ($mt["net_profit"]*$cshare/100)*$cur_rate;
 		   		//$abalance = $mt["acc_bal"];
 		   		$comment = $mt["comment"];
 		   		$curr = $mt["curr"];
 		   		//$pgrowth = $mt["growth"];
 		   		$accbal = $accbal+round(($net_profit+$accbal),2);
 		   		$growth = $growth+round(($net_profit/$accbal)*100,2);
 		   		$lastupdate = $mt["lastupd"];
 		   		if($mt["status"]=="Closed"){
 		   			$updated = 1;
 		   		}else{
 		   			$updated = 0;

 		   		}


 		   		$qupdct = "INSERT INTO client_trades SET uid = '$uid', uacc = $uacc, ticket = $ticket, status = '$status', type='$type', lot_size = $lot_size, open_time = '$open_time', close_time = '$close_time', symbol = '$symbol', magic_number = $magic_number, lots = $lots, open = $open, close = $close, stop_loss = $stop_loss, take_profit = $take_profit, profit = $profit, swap = $swap, commission= $commission, net_profit = $net_profit, acc_bal = $accbal, comment = '$comment', curr = '$curr', growth = $growth,updated=$updated,cur_rate=$cur_rate ON DUPLICATE KEY UPDATE status = '$status', type='$type', lot_size = $lot_size, open_time = '$open_time', close_time = '$close_time', symbol = '$symbol', magic_number = $magic_number, lots = $lots, open = $open, close = $close, stop_loss = $stop_loss, take_profit = $take_profit, profit = $profit, swap = $swap, commission= $commission, net_profit = $net_profit, acc_bal = $accbal, comment = '$comment', curr = '$curr', lastupd = '$lastupdate', growth = $growth,updated=$updated,cur_rate=$cur_rate";

 		   		//var_dump($qupdct);
 		   		$result2 = $this->swoole_mysql->query($qupdct);
 		   		//var_dump("Ticket =>".$ticket ." symbol =>".$symbol ." status =>".$status ." lots =>".$lots ." profit =>".$profit ." status =>".$status ." type =>".$type ." Result =>".$result2);

 		   }


		}
		
		

		return $result2;

	}

	public function post($data,$callback){
		$ret = self::run();
		//var_dump($data);
		$user = json_decode($data["payload"]);
		var_dump($user);
		if($data["trimmedPath"]=="api/login"){
			// variables used for jwt
			$key = "example_key";
			$iss = "https://shiwin.co.in";
			$aud = "https://shiwin.co.in";
			$iat = 1356999524;
			$nbf = 1357000000;
			$client = "select * from sim_clients where (uphone='$user->username' or uname='$user->username') and upass='$user->password' limit 1";
			var_dump($client);
			$result = $this->swoole_mysql->query($client);
			var_dump($result);
			if($result){
				$token = array(
			       "iss" => $iss,
			       "aud" => $aud,
			       "iat" => $iat,
			       "nbf" => $nbf,
			       "data" => array(
			           "uid" => $result[0]["uid"],
			           "username" => $result[0]["uname"],
			           "uemail" => $result[0]["ueml"],
			       )
			    );
			    $jwt = JWT::encode($token, $key);
			    $ret = array(
			    		"message"=>"Login Successful.",
			    		"token" =>$jwt,
			    		"uid" => $result[0]["uid"],
			           	"username" => $result[0]["uname"],
			           	"uemail" => $result[0]["ueml"],
			    		);	
			    $this->status = 200;
			}else{
				$ret = array("message" => "Authentication Failed");
				$this->status = 202;
			}
		}else if($data["trimmedPath"]=="api/test"){
			$user = json_decode($data["payload"]);
			$qhistory = "select * from sim_clients where uid='$user->id' ";
			var_dump($qhistory);
			$result = $this->swoole_mysql->query($qhistory);
			if($result){
				$ret = $result;
			}else{
				$ret = array("error" => "History Fetch Error");
				$this->status = 401;
			}

		}
		var_dump($ret);
		$callback($this->status,$ret,$this->ctype);
	}

	public function get($data,callable $callback){
		$ret = self::run();
		//var_dump($data);
		if($data["trimmedPath"]=="api/chk"){
			$ret = self::run();
		}else if($data["trimmedPath"]=="api/getuser"){
			$user = json_decode($data["queryStringObject"]);
			$client = "select * from sim_clients where uid='$user->uid'";
			var_dump($client);
			$result = $this->swoole_mysql->query($client);
			/////var_dump($result);
			if($result){
				$ret = $result[0];
			}else{
				$ret = array("error" => "Authentication Failed");
				$this->status = 401;
			}
		}else if($data["trimmedPath"]=="api/login"){
			$user = json_decode($data["queryStringObject"]);
			$client = "select * from sim_clients where (uphone='$user->username' or uname='$user->username') or and upass='$user->password'";
			var_dump($client);
			$result = $this->swoole_mysql->query($client);
			/////var_dump($result);
			if($result){
				$ret = $result[0];
			}else{
				$ret = array("error" => "Authentication Failed");
				$this->status = 401;
			}
		}else if($data["trimmedPath"]=="api/getdbdet"){
			$user = json_decode($data["queryStringObject"]);
			var_dump($user);
			$result = $this->swoole_mysql->query("CALL getDB($user->uid)");
			if($result){
				$dbdet = "select * from client_db where cid='$user->uid' ";
				$result = $this->swoole_mysql->query($dbdet);
				if($result){
					$ret = $result[0];
				}else{
					$ret = array("error" => "Dashboard Fetch Error");
				}
			}
		}else if($data["trimmedPath"]=="api/getinvdet"){
			$user = json_decode($data["queryStringObject"]);
			var_dump($user);
			$qhistory = "SELECT date_format(inv_date,'%d-%m-%Y') as inv_date, inv_description,inv_amount FROM sim.client_inv where cid='$user->uid' ";
			$result = $this->swoole_mysql->query($qhistory);
			if($result){
				$ret = $result;
			}else{
				$ret = array("error" => "Investment Details Fetch Error");
				$this->status = 401;
			}
		}else if($data["trimmedPath"]=="api/getchart1"){
			var_dump("Hello I am here");
			$user = json_decode($data["queryStringObject"]);
			$chart1 = "select GROUP_CONCAT(growth) as growth from sim.client_trades where status = 'Closed' and uid='$user->uid' order by close_time;";
			var_dump($chart1);
			$result = $this->swoole_mysql->query($chart1);
			var_dump($result);
			$ret =  array("growth"=>$result[0]["growth"]);
		}else if($data["trimmedPath"]=="api/getchart2"){
			var_dump("Hello I am here");
			$user = json_decode($data["queryStringObject"]);
			$chart2 = "SELECT DATE_FORMAT(close_time,'%d/%m') as date, acc_bal,round(sum(net_profit),2),Round((sum(net_profit)/acc_bal)*100,1) as dailyrr FROM sim.client_trades where status='Closed' and uid='$user->uid' group by DATE_FORMAT(close_time,'%d/%m-%y') order by close_time desc  limit 5;";
			var_dump($chart2);
			$result = $this->swoole_mysql->query($chart2);
			var_dump($result);
			$ret =  $result;
		}else if($data["trimmedPath"]=="api/getchart3"){
			var_dump("Hello I am here");
			$user = json_decode($data["queryStringObject"]);
			$chart3 = "SELECT DATE_FORMAT(close_time,'%b-%y') as date, acc_bal,round(sum(net_profit),2),Round((sum(net_profit)/acc_bal)*100,1) as dailyrr FROM sim.client_trades where status='Closed' and uid='$user->uid' group by DATE_FORMAT(close_time,'%m-%y') order by close_time desc  limit 12;";
			var_dump($chart3);
			$result = $this->swoole_mysql->query($chart3);
			var_dump($result);
			$ret =  $result;
			

		}else if($data["trimmedPath"]=="api/test"){
			$user = json_decode($data["queryStringObject"]);
			var_dump($user);
			$qhistory = "select * from sim_clients where uid='$user->id' ";
			var_dump($qhistory);
			$result = $this->swoole_mysql->query($qhistory);
			$ret = $result;
			

		}else if($data["trimmedPath"]=="api/gettrades"){
			//$token = json_decode($data["token"]);
			$user = json_decode($data["queryStringObject"]);
			//var_dump($token->uid);
			$qhistory = "select ct.*, ROUND(ct.open,2) as openprice,0 as exp  from client_trades ct where ct.uid=$user->uid  and  ct.status = 'open' and  ct.type != 'balance'  order by ct.open_time desc";
			var_dump($qhistory);
			$result = $this->swoole_mysql->query($qhistory);
				$ret = $result;
			

		}else if($data["trimmedPath"]=="api/gethistory"){
			//$token = json_decode($data["token"]);
			$user = json_decode($data["queryStringObject"]);
			//var_dump($token->uid);
			$qhistory = "select ct.*, ROUND(ct.open,2) as openprice, 0 as exp from client_trades ct where ct.uid=$user->uid  and  ct.status = 'closed' and  ct.type != 'balance' order by ct.close_time desc";
			var_dump($qhistory);
			$result = $this->swoole_mysql->query($qhistory);
				$ret = $result;
			

		}
		$callback($this->status,$ret,$this->ctype);
			
	}

	public function put($data,$callback){


		//var_dump($data); */
		$servername = "localhost";
		$username = "root";
		$password = "root";
		$dbname = "calmet";

		try {
		    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
		    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		    $stmt = $conn->prepare("CALL list(".$uid.",2,@output)");
		    $stmt->execute();

		    // set the resulting array to associative
		    $result = $stmt->fetchAll(PDO::FETCH_ASSOC); //(PDO::FETCH_ASSOC);
		    //var_dump($result);
		    //$callback(200,json_encode($result));;
		    $callback(200,$result);
		    /*foreach(new TableRows(new RecursiveArrayIterator($stmt->fetchAll())) as $k=>$v) {
		        echo $v;
		    } */
		}
		catch(PDOException $e) {
		    echo "Error: " . $e->getMessage();
		}
		$conn = null;
		
	}

	public function delete($data,$callback){
		
	}


public function createtoken($data){

		$privateKey = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQC8kGa1pSjbSYZVebtTRBLxBz5H4i2p/llLCrEeQhta5kaQu/Rn
vuER4W8oDH3+3iuIYW4VQAzyqFpwuzjkDI+17t5t0tyazyZ8JXw+KgXTxldMPEL9
5+qVhgXvwtihXC1c5oGbRlEDvDF6Sa53rcFVsYJ4ehde/zUxo6UvS7UrBQIDAQAB
AoGAb/MXV46XxCFRxNuB8LyAtmLDgi/xRnTAlMHjSACddwkyKem8//8eZtw9fzxz
bWZ/1/doQOuHBGYZU8aDzzj59FZ78dyzNFoF91hbvZKkg+6wGyd/LrGVEB+Xre0J
Nil0GReM2AHDNZUYRv+HYJPIOrB0CRczLQsgFJ8K6aAD6F0CQQDzbpjYdx10qgK1
cP59UHiHjPZYC0loEsk7s+hUmT3QHerAQJMZWC11Qrn2N+ybwwNblDKv+s5qgMQ5
5tNoQ9IfAkEAxkyffU6ythpg/H0Ixe1I2rd0GbF05biIzO/i48Det3n4YsJVlDck
ZkcvY3SK2iRIL4c9yY6hlIhs+K9wXTtGWwJBAO9Dskl48mO7woPR9uD22jDpNSwe
k90OMepTjzSvlhjbfuPN1IdhqvSJTDychRwn1kIJ7LQZgQ8fVz9OCFZ/6qMCQGOb
qaGwHmUK6xzpUbbacnYrIM6nLSkXgOAwv7XXCojvY614ILTK3iXiLBOxPu5Eu13k
eUz9sHyD6vkgZzjtxXECQAkp4Xerf5TGfQXGXhxIX52yH+N2LtujCdkQZjXAsGdm
B2zNzvrlgRmgBrklMTrMYgm1NPcW+bRLGcwgW2PTvNM=
-----END RSA PRIVATE KEY-----
EOD;

	//$uuid = base64_encode($userid);
	//$uid = self::dec_enc('encrypt',$uuid);
	$sessionid = generateRand_uuid();
	$uuid = base64_encode($sessionid);
	$uid = self::dec_enc('encrypt',$uuid);

	$token = array(
		"iss" => "http://portal.calmet.com",
		"aud" => "admin",
		"uid" => $uid
	);	
	$jwt = JWT::encode($token, $privateKey, 'RS256');

	$data = [
   		"jwt" => $jwt,
   		"uid" => $sessionid,
   	];
	return $data;

	}

	public function Auth($data){
		$loginstat=false;
		parse_str($data["payload"], $values);
		if(isset($data["token"])){
			$tokendata = self::viewtoken($data);
			//var_dump($tokendata);
			$loginstat = $this->model->redis_hmget($tokendata["uid"],"loginstat");
			if($loginstat[0]==false){
				if(isset($values["username"]) && isset($values["password"])){
					$user = $this->model->checkusernamepassword($values["username"],$values["password"]);
					if($user){
						if(isset($values["captcha_code"])){
							$captcha = $this->model->redis_hmget($tokendata["uid"],"mpharse");
							//var_dump($captcha[0]."==".$values["captcha_code"]);
							if($captcha[0]==$values["captcha_code"]){
								$uid = $user["mem_id"];
								$loginstat = True;
							}else{
								$uid = $user["mem_id"];
								$loginstat = False;
							}
						}else{
							$uid = $user["mem_id"];
							$loginstat = True;	
						}
					}else{
						$uid = NULL;
						$loginstat = False;
					}
				}else{
					$uid = NULL;
					$loginstat = False;
				}
			}else{
				$udata = $this->model->redis_hmget($tokendata["uid"],"uid");
				$uid = $udata[0];
				$loginstat = True;
			}
			$rsdata = $this->model->redis_hmget($tokendata["uid"],"lcount");
			$value = [
				"uid" => $uid,
				"lcount" => $rsdata[0]+1,
				"loginstat" => $loginstat
			];
			$this->model->redis_hmset($tokendata["uid"],$value);
			
		}else{
			$tokendata = self::createtoken($data);
			if(isset($values["username"]) && isset($values["password"])){
				$user = $this->model->checkusernamepassword($values["username"],$values["password"]);
				if($user){
					$uid = $user["mem_id"];
					$loginstat = True;
				}else{
					$uid = NULL;
					$loginstat = False;
				}
			}else{
				$uid = NULL;
				$loginstat = False;
			}
				
			$value = [
				"uid" => $uid,
				"lcount" => 1,
				"loginstat" => $loginstat
			];
			$this->model->redis_hmset($tokendata["uid"],$value);
		}
		//var_dump("Login Status ===". $loginstat);
		return array("loginstat"=>$loginstat,"jwt"=>$tokendata["jwt"],"uid" => $tokendata["uid"]);
	}


	public function viewtoken($data){

$publicKey = <<<EOD
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC8kGa1pSjbSYZVebtTRBLxBz5H
4i2p/llLCrEeQhta5kaQu/RnvuER4W8oDH3+3iuIYW4VQAzyqFpwuzjkDI+17t5t
0tyazyZ8JXw+KgXTxldMPEL95+qVhgXvwtihXC1c5oGbRlEDvDF6Sa53rcFVsYJ4
ehde/zUxo6UvS7UrBQIDAQAB
-----END PUBLIC KEY-----
EOD;
		//$key = "example_key";
		////var_dump($data["token"]);
		if(isset($data["token"])){
			////var_dump($data["token"]);
			try {
			   $decoded = (array)JWT::decode($data["token"],  $publicKey, array('RS256'));
			   ////var_dump($decoded);
			   $jwt = $data["token"];
			   $uid = $decoded["uid"];
			   $uid = self::dec_enc('decrypt',$uid);
			   $uid = base64_decode($uid);
			   
			   $data = [
			   		"jwt" => $jwt,
			   		"uid" => $uid,
			   ];
			   return $data;

			} catch (Exception $e) {
			    echo 'Exception catched: ',  $e->getMessage(), "\n";  
			    return  "Error";
			}
		}
		////var_dump($jwt);
		/**
		 * IMPORTANT:
		 * You must specify supported algorithms for your application. See
		 * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
		 * for a list of spec-compliant algorithms.
		 */
		/*
		 NOTE: This will now be an object instead of an associative array. To get
		 an associative array, you will need to cast it as such:
		*/
		//$decoded_array = (array) $decoded;
		/**
		 * You can add a leeway to account for when there is a clock skew times between
		 * the signing and verifying servers. It is recommended that this leeway should
		 * not be bigger than a few minutes.
		 *
		 * Source: http://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
		 */
		//JWT::$leeway = 60; // $leeway in seconds
		//$decoded = JWT::decode($jwt, $key, array('HS256'));
		////var_dump($decoded); */
	}

	function dec_enc($action, $string) {
		////var_dump("String Received : ". $string);
	    $output = false;
	    $encrypt_method = "AES-256-CBC";
	    $secret_key = 'This is my secret key';
	    $secret_iv = 'This is my secret iv';
	    // hash
	    $key = hash('sha256', $secret_key);
	    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
	    $iv = substr(hash('sha256', $secret_iv), 0, 16);
	    if( $action == 'encrypt' ) {
	        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
	        $output = base64_encode($output);
	    }
	    else if( $action == 'decrypt' ){
	        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
	    }
	    return $output;
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

	function parse_raw_http_request($data, array &$a_data)
	{
  	// read incoming data
  	$input = $data["payload"];
  
  	$header=json_decode($data["headers"],true);

  	////var_dump($header["content-type"]);

 	 // grab multipart boundary from content type header
 	 preg_match('/boundary=(.*)$/', $header['content-type'], $matches);
 	 ////var_dump($matches);
 	 $boundary = $matches[1];

 	 ////var_dump($boundary);
  	// split content by boundary and get rid of last -- element
 	 $a_blocks = preg_split("/-+$boundary/", $input);
 	 ////var_dump($a_blocks);
  	array_pop($a_blocks);

  	// loop data blocks
  	foreach ($a_blocks as $id => $block)
  	{
    if (empty($block))
      continue;

    // you'll have to////var_dump $block to understand this and maybe replace \n or \r with a visibile char
  	////var_dump($block);
    // parse uploaded files
    if (strpos($block, 'application/') !== FALSE)
    {
      // match "name", then everything after "stream" (optional) except for prepending newlines 
      	//preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
      	//preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
    }else if (strpos($block, 'image/') !== FALSE){
      // match "name", then everything after "stream" (optional) except for prepending newlines 
      	//preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
      	//preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
    }

    else
    {
      // match "name" and optional value in between newline sequences
      preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
    }
    	
    	if(count($matches)>2){
    		$a_data[$matches[1]] = $matches[2];
    	}else{
    		$a_data[$matches[1]] = "";
    	}
    	////var_dump($a_data);

    		    	
  	}        
	}
	public function updateSQL($table_name, $field_array, $where_condition = "")
    {
        $field_array = (array) $field_array;
        $sql = "UPDATE " . $table_name . " SET ";
        foreach( $field_array as $field => $value ) 
        {
            $sql .= $field . " = '" . trim($value) . "',";
        }
        $sql = rtrim($sql, ",");
        $sql .= "" . " " . $where_condition;
        echo "<br><br>Update query = ".$sql;
		return trim($sql);
    }

    public function insertSQL($table_name, $field_array)
    {
        $field_array = (array) $field_array;
        $sql = "INSERT INTO " . $table_name . " SET ";
        foreach( $field_array as $field => $value ) 
        {
            $sql .= $field . " = '" . trim($value) . "',";
        }
        $sql = rtrim($sql, ",");
        echo " <br>query value = ".$sql;
		return trim($sql);
		
		
    }

}

<?php
//namespace node\lib;
require_once Basedir.'/App/Models/Model.php';
require_once Basedir.'/App/Models/CaptchaModel.php';
use \Firebase\JWT\JWT;
use Swoole\Coroutine as co;
$GLOBALS['site_title'] = "Tasks";
$GLOBALS['hello_world'] = "Hello World..!!!";
class Api
{
	private $db;
	private $server;
	private $swoole_mysql;
	
	Public function __construct(){
		$this->model = new Model();

		$this->server = array(
		    'host' => '192.168.5.203',
		    'user' => 'root',
		    'password' => 'caminven',
		    'database' => 'calmet',
		    'charset' => 'utf8',
		    'timeout' => 2,
		    'strict_type' => false,  /// / Open strict mode, the returned field will automatically be converted to a numeric type
    		'fetch_mode' => true, 
    		
		);
		$this->db =  new swoole_mysql;
		$this->swoole_mysql = new Swoole\Coroutine\MySQL();
		

	}

	public function post($data,$callback){
		//var_dump($data);
		//var_dump(json_decode($data["payload"]));
		//return "Hai";
			$tokendata = self::Auth($data);
			if($tokendata["loginstat"]){
				$token = $data["token"];
				$mem_id = $this->model->redis_hmget($tokendata["uid"],"uid");
				$mem_id = $mem_id[0];
			}


		$this->swoole_mysql->connect($this->server);
		$res = $this->swoole_mysql->query('CALL list(48,2,@output)');
		//$res= array(["id"=>"1234"]);
		$Sel_Com_Pros	= "select co.name from calmet_tasks t 
				inner join categorylist co on t.sel_relateid = co.id and t.relate_to = co.Type
				inner join calmet_task_assigned ta on t.id = ta.task_id
				where t.task_status != 3 and t.created_by = 48 or find_in_set(48,t.task_assigned ) or t.manager = 48 group by co.name order by 1";
		$res2 = $this->swoole_mysql->query($Sel_Com_Pros);
		$Sel_Com_Pros	= "select cm.meet_code,cm.id, cm.meet_recu_stime from calmet_meeting cm inner join calmet_task_followup_dates tf on cm.id = tf.meetid left outer join meet_det md on md.id = tf.meetid left outer join calmet_users u on u.id = tf.loginid where 
			FIND_IN_SET(48,cm.meet_tms) and meet_stat = 1
			group by cm.meet_code 
			order by cm.meet_order,cm.meet_code asc ";
		$res3 = $this->swoole_mysql->query($Sel_Com_Pros);
		$this->view = new View();
		$ret = $this->view->vuetest2($res,$res2,$res3);
		
		//var_dump("Result " .$ret);
		$callback(303,$ret,'html');
	}

	public function get($data,callable $callback){
		var_dump($data["queryStringObject"]);
		
		$hpath = explode("/",$data["trimmedPath"]);
		var_dump($hpath);
		if($hpath[0]="api"){
			$qs = json_decode($data["queryStringObject"]);
			var_dump($qs);
			$this->swoole_mysql->connect($this->server);

			if($hpath[1]=="taskslist"){
				if(isset($qs) && $qs->m=="mid"){
						$time_start = microtime(true); 
						$time_end = microtime(true);
						$execution_time = ($time_end - $time_start)/60;
						co::create(function() {
						    $db = new co\MySQL();
						    $server2= array(
						        'host' => '192.168.5.203',
								    'user' => 'root',
								    'password' => 'caminven',
								    'database' => 'calmet',
								    'charset' => 'utf8',
								    'timeout' => 2,
								    'strict_type' => false,  /// / Open strict mode, the returned field will automatically be converted to a numeric type
						    		'fetch_mode' => true,
						    );

						    $res = $db->connect($server2);
						    $ret = $db->query("CALL list2(48,'seaadv','kanagu',0, @output)");
								var_dump("ret ". count($ret));
						});

						co::create(function() {
						    $db = new co\MySQL();
						    $server2= array(
						        'host' => '192.168.5.203',
								    'user' => 'root',
								    'password' => 'caminven',
								    'database' => 'calmet',
								    'charset' => 'utf8',
								    'timeout' => 2,
								    'strict_type' => false,  /// / Open strict mode, the returned field will automatically be converted to a numeric type
						    		'fetch_mode' => true,
						    );

						    $res = $db->connect($server2);
						    $ret = $db->query("CALL list2(48,'seaadv','98422',0, @output)");
								var_dump("ret ". count($ret));
						});

						$ret = "null";

					/*

					var_dump("CALL meetlist(48,".$qs->mid.",'".$qs->dval."',2,@output)");
					$ret="";
					go(function()use($ret){
						$ret0 = $this->swoole_mysql->query("CALL list2(48,'seaadv','98422',0, @output);");		
						var_dump("Ret0 ".count($ret0));
						return $ret0;
						$this->swoole_mysql->close();
					});
					go(function() use($qs,$ret){
						$this->swoole_mysql = new Swoole\Coroutine\MySQL();
						$this->swoole_mysql->connect($this->server);
						var_dump("CALL meetlist(48,".$qs->mid.",'".$qs->dval."',2,@output)");
						$ret = $this->swoole_mysql->query("CALL meetlist(48,".$qs->mid.",'".$qs->dval."',2,@output)");
						var_dump("Ret1 ".count($ret));
						return $ret;
						$this->swoole_mysql->close();
					});
					go(function() use($ret){
						$this->swoole_mysql = new Swoole\Coroutine\MySQL();
						$this->swoole_mysql->connect($this->server);
						$ret2 = $this->swoole_mysql->query('CALL list(48,2,@output)');
						var_dump("Ret2 ".count($ret2));
						return $ret2;
					});
					go(function() use($ret){
						$this->swoole_mysql = new Swoole\Coroutine\MySQL();
						$this->swoole_mysql->connect($this->server);
						$ret3 = $this->swoole_mysql->query("CALL list2(48,'cate','agd',2, @output);");
						var_dump("Ret3 ".count($ret3));
						return $ret3;
					});
					go(function() use($ret){
						$this->swoole_mysql = new Swoole\Coroutine\MySQL();
						$this->swoole_mysql->connect($this->server);
						$ret4 = $this->swoole_mysql->query("CALL list2(48,'search','test',0, @output);");
						var_dump("Ret4 ".count($ret4));
						return $ret4;
					});*/
				}else if(isset($qs) && $qs->m=="cate"){
					$ret = $this->swoole_mysql->query("CALL list2(48,'cate','".$qs->cate."',2, @output);");		
				}else if(isset($qs) && $qs->m=="mee"){
					//CALL list2(48,'cate','System',2,@output)
					//var_dump("CALL meetlist(48,'mee','".$qs->mee."','2022-01-01',2, @output);");
					$ret = $this->swoole_mysql->query("CALL meetlist(48,'".$qs->mee."','2022-01-01',2, @output);");		
				}else if(isset($qs) && $qs->m=="txt"){
					//CALL list2(48,'cate','System',2,@output)
					var_dump("CALL list2(48,'".search."','".$qs->txt."',2, @output);");
					$ret = $this->swoole_mysql->query("CALL list2(48,'".search."','".$qs->txt."',0, @output);");		
				}else if(isset($qs) && $qs->m=="advtxt"){
					//CALL list2(48,'cate','System',2,@output)
					//var_dump(CALL list2(48,'".seaadv."','".$qs->advtxt."',0, @output));
					$ret = $this->swoole_mysql->query("CALL list2(48,'".seaadv."','".$qs->advtxt."',0, @output);");		
				}else{
					$ret = $this->swoole_mysql->query('CALL list(48,2,@output)');		
				}
			}else if($hpath[1]=="taskdetails"){
				if(isset($qs)){
					var_dump($qs->id);
					var_dump("CALL gettaskdetails(48,'".$qs->id."',@output)");
					$ret = $this->swoole_mysql->query("CALL gettaskpagedata(48,'".$qs->id."',@output)");		
					//$ret = $this->swoole_mysql->query('CALL list(77,2,@output)');		
					var_dump(count($ret));
				}
			}else if($hpath[1]=="cateserver"){
				$Sel_Com_Pros	= "select co.name from calmet_tasks t 
					inner join categorylist co on t.sel_relateid = co.id and t.relate_to = co.Type
					inner join calmet_task_assigned ta on t.id = ta.task_id
					where t.task_status != 3 and t.created_by = 48 or find_in_set(48,t.task_assigned ) or t.manager = 48 group by co.name order by 1";
				$ret = $this->swoole_mysql->query($Sel_Com_Pros);	
			
			}else if($hpath[1]=="meetingsserver"){
				$Sel_Com_Pros	= "select cm.meet_code,cm.id, cm.meet_recu_stime from calmet_meeting cm inner join calmet_task_followup_dates tf on cm.id = tf.meetid left outer join meet_det md on md.id = tf.meetid left outer join calmet_users u on u.id = tf.loginid where 
					FIND_IN_SET(48,cm.meet_tms) and meet_stat = 1
					group by cm.meet_code 
					order by cm.meet_order,cm.meet_code asc ";
				$ret = $this->swoole_mysql->query($Sel_Com_Pros);	
			


			}else if($hpath[1]="tdymeeting"){
				$whr = "";
				$name = array();
				for($i=0;$i<8;$i++){
					if($i==0){
						$seltdymet = "select m.id, m.meet_code, m.meet_desc,DATE_FORMAT(date_add(now(),interval 1 day),'%Y-%m-%d') as sdate, m.meet_recu_days, m.meet_recu_period, CONCAT_WS('-',TIME_FORMAT(meet_recu_stime, '%l:%i %p'), TIME_FORMAT(meet_recu_etime, '%l:%i %p')) AS stime, GROUP_CONCAT(u.name ORDER BY u.name SEPARATOR ', ') AS TMS,m.meet_order FROM calmet_meeting m,     calmet_users u WHERE FIND_IN_SET(u.id, meet_tms)  $whr and FIND_IN_SET(left(DAYNAME(date_add(now(),interval 0 day)),2), m.meet_recu_days) and m.meet_stat = 1 GROUP BY m.id ORDER BY  m.meet_order, m.meet_code,m.meet_recu_period,m.meet_recu_days , m.meet_recu_stime ASC, m.meet_code";
						$result = $this->swoole_mysql->query($seltdymet);	
						$name[$i] = array(
							'name'=> 'Today',
							'data'=> $result,
						);
						//$name[$i] = array($temp[$i] => $result,);
					}else if($i==1){
						$seltdymet = "select m.id, m.meet_code, m.meet_desc,DATE_FORMAT(date_add(now(),interval 2 day),'%Y-%m-%d') as sdate, m.meet_recu_days, m.meet_recu_period, CONCAT_WS('-',TIME_FORMAT(meet_recu_stime, '%l:%i %p'), TIME_FORMAT(meet_recu_etime, '%l:%i %p')) AS stime, GROUP_CONCAT(u.name ORDER BY u.name SEPARATOR ', ') AS TMS,m.meet_order FROM calmet_meeting m,     calmet_users u WHERE FIND_IN_SET(u.id, meet_tms) $whr and FIND_IN_SET(left(DAYNAME(date_add(now(),interval 1 day)),2), m.meet_recu_days) and m.meet_stat = 1 GROUP BY m.id ORDER BY  m.meet_order, m.meet_code,m.meet_recu_period,m.meet_recu_days , m.meet_recu_stime ASC, m.meet_code";
						$result = $this->swoole_mysql->query($seltdymet);	
						$name[$i] = array('name'=>'Tomorrow','data' => $result, );
					}else{
						$dayname = date("l", strtotime("+ ".$i." day"));
						$seltdymet = "select m.id, m.meet_code, m.meet_desc, DATE_FORMAT(date_add(now(),interval $i+1 day),'%Y-%m-%d') as sdate,m.meet_recu_days, m.meet_recu_period, CONCAT_WS('-',TIME_FORMAT(meet_recu_stime, '%l:%i %p'), TIME_FORMAT(meet_recu_etime, '%l:%i %p')) AS stime, GROUP_CONCAT(u.name ORDER BY u.name SEPARATOR ', ') AS TMS,m.meet_order FROM calmet_meeting m,     calmet_users u WHERE FIND_IN_SET(u.id, meet_tms) $whr and FIND_IN_SET(left(DAYNAME(date_add(now(),interval $i day)),2), m.meet_recu_days) and m.meet_stat = 1 GROUP BY m.id ORDER BY  m.meet_order, m.meet_code,m.meet_recu_period,m.meet_recu_days , m.meet_recu_stime ASC, m.meet_code";
						$result = $this->swoole_mysql->query($seltdymet);	
						$name[$i] = array('name' => $dayname, 'data' => $result, );
					}
					
				}
				$ret = $name;
			}else{
				$ret = array('Error' => "Api Action Not Found", );
			}

		}
		$callback(202,$ret,'html');
			
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
		    $stmt = $conn->prepare("CALL list(48,2,@output)");
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
		"iss" => "http://waszuppglobal.com",
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
			$loginstat = $this->model->redis_hmget($tokendata["uid"],"loginstat");
			if($loginstat[0]==false){
				if(isset($values["username"]) && isset($values["password"])){
					$user = $this->model->checkusernamepassword($values["username"],$values["password"]);
					if($user){
						if(isset($values["captcha_code"])){
							$captcha = $this->model->redis_hmget($tokendata["uid"],"mpharse");
							var_dump($captcha[0]."==".$values["captcha_code"]);
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
		var_dump("Login Status ===". $loginstat);
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
		//var_dump($data["token"]);
		if(isset($data["token"])){
			//var_dump("Hai I have token");
			//var_dump($data["token"]);
			try {
			   $decoded = (array)JWT::decode($data["token"],  $publicKey, array('RS256'));
			   //var_dump($decoded);
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
		//var_dump($jwt);
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
		//var_dump($decoded); */
	}

	function dec_enc($action, $string) {
		//var_dump("String Received : ". $string);
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

}

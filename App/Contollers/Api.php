<?php
//namespace node\lib;
require_once Basedir.'/App/Models/Model.php';
require_once Basedir.'/App/Models/CaptchaModel.php';
require_once Basedir.'/src/DeepstreamClient.php';
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
		//$this->db =  new swoole_mysql;
		$this->swoole_mysql = new Swoole\Coroutine\MySQL();
		$this->swoole_mysql->connect($this->server);
		//$this->dsclient = new DeepstreamClient( 'http://192.168.5.203:1338',[]);
		$this->dsclient = new DeepstreamClient( 'http://192.168.5.140:1338',[]);
	}

	public function post($data,$callback){

		//var_dump("_____________________________________________________________________________________________________________________________");
		//$this->swoole_mysql->connect($this->server);
		$ctype='html';
		//var_dump($data);

		var_dump($data["trimmedPath"]);
		if($data["trimmedPath"]=="api/auth/login"){
			$user = json_decode($data["payload"]);
			//var_dump($user);
			$r=$this->swoole_mysql->query("select * from calmet_users where name = '".$user->username."' and password=base64_encode('".$user->password."');");
			
			if($r){
				$key = "example_key";
				$uuid = generateRand_uuid();
				$uid = base64_encode($uuid);
				//$uid = self::dec_enc('encrypt',$uuid);
				$values = array("uid"=>$r[0]["id"]);
				$this->model->redis_hmset($uid,$values);
				//$this->model->expire($uid);
				
				$accessToken = array(
					"iss" => "http://portal.calmet.com",
					'id' => $uid,
				  'name' => $r[0]["name"],
	      );	
				$jwt = JWT::encode($accessToken,  $key);
				$ret = array(
				"output" => array("token" => $jwt,"uid" => $uid, "uname" => $r[0]["name"], ),
				);

			}else{
				$ret = array(
					"output" => "Login Failed"
				);
			}
			

			//var_dump($ret);
		}else if($data["trimmedPath"]=="api/auth/logout"){
			var_dump($data);
			$qs = json_decode($data["queryStringObject"]);
			var_dump($qs);
			$ret = $this->model->redis_hdel($qs->uid,"uid");
			
		}else if($data["trimmedPath"]=="api/save"){
		//var_dump($data["queryStringObject"]);
		$a_data = array();
		$modify =array();
		$res=array();
		$hpath = explode("/",$data["trimmedPath"]);
		self::parse_raw_http_request($data,$a_data);
		var_dump($a_data);
		$uid = $this->model->redis_hget($a_data["uid"],"uid");
		//$uid = $a_data["uid"];
		var_dump($uid);
		$tid=$a_data["tid"];
		$tname=$a_data["tname"];
		$twhy=$a_data["twhy"];
		$ftime = $a_data["ftime"];
		//$fdate=$a_data["fdate"];
		$fdate= date('Y-m-d H:i:s',strtotime($a_data["fdate"]." ".$ftime));
		//var_dump("Followup Date".$fdate);
		$loginid=$a_data["loginid"];
		$mlist = $a_data["mlist"];
		$asstms=$a_data["asstms"];
		$tpyt=$a_data["tpyt"];
		$notitms=$a_data["notitms"];
		$imtms=$a_data["imtms"];
		$notitmstemp=$a_data["notitmstemp"];
		$asstmsname = $a_data["asstmsname"];
		$tlog=$a_data["tlog"];
		$tlog =str_replace("\\","\\\\",$tlog);
		$tlog =str_replace("'","\\'",$tlog);
				
		$dtype=$a_data["dtype"];
		$tmr=$a_data["tmr"];
		$tmrname=$a_data["tmrname"];
		$rtype=$a_data["rtype"];
		$tcategory=$a_data["tcategory"];
		$doculd = $a_data["doculd"];
		$star=$a_data["star"];
		//var_dump($a_data);
		$selid=$a_data["tsrelid"];
		$rto=$a_data["trelto"];
		$ti=$a_data["ftime"];
		$modify["task_name"]="";
		$modify["why"]="";
		$modify["fdate"]="";
		$modify["cate"]="";

		$fdatetime= date('Y-m-d H:i:s',strtotime($a_data["fdate"]." ".$ti));
		$Sel_Com_Pros="select * from calmet_tasks where id=".$tid.";";
		$ret = $this->swoole_mysql->query($Sel_Com_Pros);
		
		if($dtype==0){
			$updasstms = explode(",",$ret[0]["task_assigned"]); 
			$newasstms = explode(",",$asstms); 
			$viola = array_diff($updasstms,$newasstms);
			//var_dump("Removed TMS");
			//var_dump($viola);
			if($viola){
			$rmtms = implode(",", $viola);
				$rtms = "select GROUP_CONCAT(' ',name) as name from calmet_users where id in (".$rmtms.") order by 1";
				$doc = $this->swoole_mysql->query($rtms);
				$modify["TMS_Removed"] = "TM(s) Removed <div class=\"hl2\">".$doc[0]["name"]."</div><br>";
			}
			$viola = array_diff($newasstms,$updasstms);
			if($viola){
			//var_dump("Added TMS");
			//var_dump($viola);
				$addtms = implode(",", $viola);
				$atms = "select GROUP_CONCAT(' ',name) as name from calmet_users where id in (".$addtms.") order by 1";
				$doc = $this->swoole_mysql->query($atms);
				$modify["TMS_Added"] = "TM(s) Added <div class=\"hl2\">".$doc[0]["name"]."</div><br>";
			}
			
			//var_dump("Assigned TMS".$asstms);
			if($dtype==0){
				$sql ="Update calmet_tasks SET task_assigned='".$asstms."' where id=".$tid.";";
				$doc = $this->swoole_mysql->query($sql);
			}

		}
		
		if($ret[0]["team_member"]!=$tmr){
			$r=$this->swoole_mysql->query("select name from calmet.calmet_users where id = ".$ret[0]["team_member"].";");
			$modify["tmr"]="TMR changed from <div class=\"hl2\">".$r[0]["name"]."</div> to <div class=\"hl2\">".$tmrname."</div><br>";
			$sql ="Update calmet_tasks SET team_member=".$tmr."  where id=".$tid.";";
			var_dump($sql);
			$doc = $this->swoole_mysql->query($sql);
		}


		//var_dump(json_decode($ret));
		$r=$this->swoole_mysql->query("select name from calmet.categorylist where type = ".$ret[0]["relate_to"]." and 
			id=".$ret[0]["sel_relateid"].";");

		//var_dump($r[0]["name"]."===".$tcategory);


		if($r[0]["name"]!=$tcategory){
			$modify["cate"]="Category changed from <div class=\"hl2\">".$r[0]["name"]."</div> to <div class=\"hl2\">".$tcategory."</div><br>";
			$sql ="Update calmet_tasks SET relate_to=".$rto.", sel_relateid=".$selid." where id=".$tid.";";
			var_dump($sql);
			$doc = $this->swoole_mysql->query($sql);
		}

		$deloldmlist = "DELETE FROM calmet.calmet_task_meetings where task_id=".$tid." and loginid=".$uid;
		$res = $this->swoole_mysql->query($deloldmlist);
		//$deloldmlist = "DELETE FROM calmet.calmet_task_meetings where task_id=".$tid." and loginid=".$uid;
		//$res = $this->swoole_mysql->query($deloldmlist);
		$meet = json_decode($mlist);
		for($i=0;$i<count($meet);$i++){
			if($meet[$i]->mid>0){
			$sql = "INSERT INTO calmet_task_meetings SET task_id=".$tid.", loginid=".$uid.", meetid=".$meet[$i]->mid.", meet_date='".$meet[$i]->msdate."', msnoozedt='".$meet[$i]->msdate."', alarm2=".$meet[$i]->alarm2.";";
			//var_dump($sql);
			$doc = $this->swoole_mysql->query($sql);
			}
			$sql = "Update calmet_task_followup_dates SET meetid=0,pyt=".$tpyt." where task_id=".$tid." and loginid = ".$uid;
			//var_dump($sql);
			$doc = $this->swoole_mysql->query($sql);
		}
		$start_time1 = microtime(true); 
		$getunreadlogid = "Select group_concat('',tc.id) as logid from calmet_tasks_comments tc LEFT OUTER JOIN calmet_task_log_followup lf ON (lf.tlid =tc.id  and lf.uid = $uid and lf.tid=$tid)
where tc.task_id = $tid and tc.ltype=$dtype and tc.loginid != $uid and (lf.uid = $uid or isnull(lf.uid)) and (lf.readed!=0 or isnull(lf.readed))";
		var_dump($getunreadlogid);
		$doc = $this->swoole_mysql->query($getunreadlogid);
		var_dump($doc[0]["logid"]);
		
		/*if($doc[0]["logid"]!=""||$doc[0]["logid"]!=null){

			if($rtype==1){
				$ureadlog = $doc[0]["logid"];
				co::create(function() use($doc,$uid,$tid) {
					
				var_dump("unread log id in Coroutine ". $doc[0]["logid"]);
			    $db = new co\MySQL();
			    $server = array(
			    'host' => '192.168.5.203',
			    'user' => 'root',
			    'password' => 'caminven',
			    'database' => 'calmet',
			    'charset' => 'utf8',
			    'timeout' => 2,
			    'strict_type' => false,  /// / Open strict mode, the returned field will automatically be converted to a numeric type
	    		'fetch_mode' => true, 
	    		);

			    $ret1 = $db->connect($server);
			    //$stmt = $db->query('SELECT * FROM calmet_tasks');
			    //var_dump($stmt);
			    $logidarray=explode(",",$doc[0]["logid"]); 
					//var_dump($logidarray);
					foreach($logidarray as $logid)
					{
							$sql = "insert into calmet_task_log_followup (uid,tid,tlid,w1,readed) values ($uid,$tid,$logid,now(),0) ON DUPLICATE KEY UPDATE w1=now(),readed=0";	
							var_dump($sql);
							$res = $db->query($sql);
						
					}
			    //return $stmt;
				});
			}else{
					var_dump("unread log id ". $doc[0]["logid"]);
					$logidarray=explode(",",$doc[0]["logid"]); 
					foreach($logidarray as $logid)
					{
							$sql = "insert into calmet_task_log_followup (uid,tid,tlid,w1,readed) values ($uid,$tid,$logid,now(),0) ON DUPLICATE KEY UPDATE w1=now(),readed=0";	
							var_dump($sql);
							$res = $this->swoole_mysql->query($sql);
						
					}
			}
			
			
		}*/
		
		$end_time1 = microtime(true); 
		  
		// Calculate script execution time 
		$execution_time1 = ($end_time1 - $start_time1); 
		var_dump("Exe Time :".$execution_time1);
		$sql = "INSERT INTO calmet_task_log_temp (uid,tid,notitms,imtms,grpid) VALUES (".$uid.",".$tid.",'".$notitmstemp."','".$imtms."','".$dtype."') ON DUPLICATE KEY UPDATE notitms='". $notitmstemp ."', imtms='". $imtms ."';";
		var_dump($sql);
		$doc = $this->swoole_mysql->query($sql);
				
		
			if($ret!=false){
			//var_dump("am working");
			if($a_data["tname"]!=$ret[0]["task_name"]){
				$modify["task_name"]="Task name changed from <div class=\"hl2\">".$ret[0]["task_name"]."</div>  to  <div class=\"hl2\">".$a_data["tname"]."</div> <br>";
				//var_dump($modify["task_name"]);
				/*
					Update the taskname from db if the task name changed
				*/
				$sql ="Update calmet_tasks SET task_name='".$tname."' where id=".$tid.";";
				$doc = $this->swoole_mysql->query($sql);
				//var_dump("Change Task Name".$sql);
            }
            if($a_data["twhy"]!=$ret[0]["why"]){
				/*
					if yes create a syslog
				*/
				$modify["why"]="Why changed from <div class=\"hl2\">".$ret[0]["why"]."</div> to <div class=\"hl2\">".$twhy."</div><br>";
				/*
					Update the db based on postdata
				*/
				$sql ="Update calmet_tasks SET why='".$twhy."' where id=".$tid.";";
				$doc = $this->swoole_mysql->query($sql);
				//var_dump($sql);
				//var_dump($modify["why"]);
			}
				$selusername="select * from calmet_users where id=".$uid;
				$ret = $this->swoole_mysql->query($selusername);
				$created_date = date('Y-m-d');
				
				$time_now = mktime(date('H'),date('i'),date('s'));
				$created_date = date('Y-m-d H:i:s',$time_now);
				
				$description = "<b>".$ret[0]["name"]."</b> Updated ". $tname;
				$url = "Task/?id=".$tid;

				if($tlog!="" || $doculd=="true"){

					co::create(function() use($notitms,$imtms,$asstmsname,$uid,$tid,$description,$url,$created_date,$dtype) {
						 $db = new co\MySQL();
			    $server = array(
			    'host' => '192.168.5.203',
			    'user' => 'root',
			    'password' => 'caminven',
			    'database' => 'calmet',
			    'charset' => 'utf8',
			    'timeout' => 2,
			    'strict_type' => false,  /// / Open strict mode, the returned field will automatically be converted to a numeric type
	    		'fetch_mode' => true, 
	    		
					);

			    $ret1 = $db->connect($server);
			   // $dsclient = new DeepstreamClient( 'http://192.168.5.203:1338',[]);
			    $dsclient = new DeepstreamClient( 'http://192.168.5.140:1338',[]);
						$variableAry=explode(",",$notitms); 
						foreach($variableAry as $var)
						{
							if($var!=$uid){
								$sql = "INSERT INTO calmet_notification (not_tms,tm,task_id,description,url,when1,ntype,shown,readed,dtype) VALUES (".$var.",".$uid.",".$tid.",'".$description."','".$url."','".$created_date."',0,0,0,'".$dtype."') ON DUPLICATE KEY UPDATE description='".$description."',when1='".$created_date."',ntype=0, shown=0,readed=0,dtype='".$dtype."'";	
								$doc = $db->query($sql);
							}
							var_dump("co id".$var);
						}

						$variableAry2=explode(",",$imtms);
						foreach($variableAry2 as $var2)
						{
							if($var2!=$uid){
								$sql = "INSERT INTO calmet_notification (not_tms,tm,task_id,description,url,when1,ntype,shown,readed,dtype) VALUES (".$var2.",".$uid.",".$tid.",'".$description."','".$url."','".$created_date."',1,0,0,'".$dtype."') ON DUPLICATE KEY UPDATE description='".$description."',when1='".$created_date."',ntype=1, shown=0,readed=0,dtype='".$dtype."'";
								$doc = $db->query($sql);
							}
							var_dump("co id".$var2);
						}	

						$devent = '{ "type":"wait", "some":$asstmsname}';
						$myJSON = json_decode($devent);
						var_dump($myJSON);
						$dsclient->emitEvent('test-event', $myJSON);


					}); //Co func end

				}
				
				var_dump("------------------------------");
					var_dump($notitms);
					$notsent = $this->swoole_mysql->query("select group_concat(' ',name ORDER BY name ASC) as names from calmet_users where id in ($notitms) and id<> $uid order by name");


					$instsent = $this->swoole_mysql->query("select group_concat(' ',name ORDER BY name ASC) as names from calmet_users where id in ($imtms) and id<> $uid order by name");

			if($tlog!=""){
			if($dtype==""){
				$sql="INSERT INTO calmet_tasks_comments (loginid,task_id,comments,comments1,dateof_followup,created_date,otask_id,ltype,nottms,inttms) VALUES ($uid,".$tid.",'". $tlog ."','". $tlog ."','". $fdate ."',now(),".$tid.",0,'" . $notsent[0]["names"]."','".$instsent[0]["names"]."');"	;
				//var_dump($sql);
				$doc = $this->swoole_mysql->query($sql);
				$Sel_Com_Pros = "INSERT INTO calmet_task_log_temp (uid,tid,tlog,ltime,grpid) VALUES ($uid,$tid,'',now(),0) ON DUPLICATE KEY UPDATE tlog='', ltime=now();";
				$doc = $this->swoole_mysql->query($Sel_Com_Pros);
			}else{
					$sql="INSERT INTO calmet_tasks_comments (loginid,task_id,comments,comments1,dateof_followup,created_date,otask_id,ltype,nottms,inttms) VALUES ($uid,".$tid.",'". $tlog ."','". $tlog ."','". $fdate ."',now(),".$tid.",".$dtype.",'" . $notsent[0]["names"]."','".$instsent[0]["names"]."');";
						//var_dump($sql);
						$doc = $this->swoole_mysql->query($sql);
						$Sel_Com_Pros = "INSERT INTO calmet_task_log_temp (uid,tid,tlog,ltime,grpid) VALUES ($uid,$tid,'',now(),".$dtype.") ON DUPLICATE KEY UPDATE tlog='', ltime=now();";
				$doc = $this->swoole_mysql->query($Sel_Com_Pros);
					}
						if($star==1){
						  $retid=$this->swoole_mysql->query("SELECT LAST_INSERT_ID() as id;");
							$retid = $retid[0]["id"];
							var_dump($retid);
							$Sel_Com_Pros = "INSERT INTO calmet_task_log_followup (tid,tlid,uid,star,w1) VALUES (".$tid.",".$retid.",".$uid.",".$star.",now()) ON DUPLICATE KEY UPDATE star=".$star.";";
							 //var_dump($Sel_Com_Pros);
								$doc = $this->swoole_mysql->query($Sel_Com_Pros);	

						}
			}

			/*
				Get the task date from postdata
			*/
				$sql="select t.id,t.task_name,t.task_assigned,t.why,t.team_member,t.team_member,t.task_assigned,c.name, DATE_FORMAT(fu.dateof_followup,'%a, %e-%b-%y') as fdate,DATE_FORMAT(fu.dateof_followup,'%Y-%m-%d') as chkdate  from calmet_tasks t left join categorylist c on  
						c.type= t.relate_to and c.id=t.sel_relateid left join calmet_task_followup_dates fu on t.id= fu.task_id and fu.loginid=$uid where t.id=".$tid.";";
				//var_dump($sql);
				$ret = $this->swoole_mysql->query($sql);
				//var_dump("working date");
				//var_dump($fdate);
				$chkdate= date('Y-m-d',strtotime($a_data["fdate"]));
				if($chkdate!=$ret[0]["chkdate"]){
				$modify["fdate"]="Task Date Changed from <div class=\"hl2\">".$ret[0]["fdate"]."</div> to <div class=\"hl2\">".$a_data["fdate"]."</div><br>";
				$fdatetime= date('Y-m-d H:i:s',strtotime($a_data["fdate"]." ".$ti));
				
				$sql ="INSERT INTO calmet_task_followup_dates (task_id,loginid,dateof_followup) Values ($tid,$uid,'".$fdatetime."') ON DUPLICATE KEY UPDATE dateof_followup='".$fdatetime."',snoozedt='".$fdatetime."'"; 

				var_dump($sql);
				$doc = $this->swoole_mysql->query($sql);
				}
				
				
			  // if ($tcategory!=$ret[0][name]){
				/*
				 	if category changed create a syslog
				*/
				//$modify["cate"]="Category changed from ".$rd." to ".$r[0]["name"];
				/*
					Update the values in db
				*/
				//$sql ="Update calmet_tasks SET relate_to=$rto, sel_relateid=$selid where id=$id;";
				//$doc = $this->swoole_mysqlquery("$sql");
				//var_dump($sql);


				//var_dump($modify["cate"]);
				//}
			}
			$syslog='';
			if ($modify!=false){
				 	if ($modify["task_name"]!=""){
				 		$syslog=$modify["task_name"]."";
				  	}
				 	/*if ($modify["task_assigned"]!=false){
				 		$tlog.=$modify["task_assigned"]."\n";
				 	}
				 	if ($modify["team_member"]!=false){
				 		$tlog.=$modify["team_member"]."\n";
				 	}*/
				 	if(isset($modify["tmr"]) && $modify["tmr"]!=""){
				 		$syslog.=$modify["tmr"]."";
				 	}
				 	if (isset($modify["TMS_Added"]) && $modify["TMS_Added"]!=""){
				 		//var_dump("oh i am working");
				 		$syslog.=$modify["TMS_Added"]."";
				 	}
				 	if (isset($modify["TMS_Removed"]) && $modify["TMS_Removed"]!=""){
				 		//var_dump("oh i am working");
				 		$syslog.=$modify["TMS_Removed"]."";
				 	}
				 	if ($modify["cate"]!=""){
				 		//var_dump("oh i am working");
				 		$syslog.=$modify["cate"]."";
				 	}
				 	if ($modify["why"]!=""){
				 		$syslog.=$modify["why"]."";
				 	}
				 	
				 	if ($modify["fdate"]!=""){
				 		$syslog.=$modify["fdate"]."";
				 	}
				 	/*if($modify["notifi"]!=false){
				 		$tlog.=$modify["notifi"]."\n";
				 	}
				 	if($modify["im"]!=false){
				 		$tlog.=$modify["im"]."\n";
				 	}*/
			 }
			 var_dump($modify);
			 var_dump($syslog);
			 if (strlen($syslog)>0){
		 		/*
		 		 	if tlog is not empty then the t has sys log so change ltype=1
		 		*/
		 		$ltype=1;
		 		/*
		 			Insert the tlog values in calmet_tasks_comments in db
		 		*/
			 	$syslog="INSERT INTO calmet_tasks_comments(loginid,task_id,comments,comments1,dateof_followup,created_date,otask_id,ltype) 
			 	    VALUES ($uid,".$tid.",'". $syslog ."','". $syslog ."','". $fdatetime ."',now(),".$tid.",".$ltype.");"; 
			 	//var_dump($syslog);
			 	$doc = $this->swoole_mysql->query($syslog);
			 	if($doc==1){
			 		$res = "Successs";
			 	}else{
			 		$res= "Error";
			 	}
		 	}

		 	$dsclient = new DeepstreamClient( 'http://192.168.5.203:1338',[]);
		 	$devent = '{ "type":"wait", "some":$asstmsname}';
						$myJSON = json_decode($devent);
						var_dump($myJSON);
						$dsclient->emitEvent('test-event', $myJSON);

 			//$ret = $res;
 			$ret = array('output' => 'updated', 'token' => $data["token"] );

 		/*************************************************/	
 		/************** File Upload **********************/	
 		/*************************************************/
 		}else if($data["trimmedPath"]=="api/fileupload"){

 		var_dump($data);
		/*
			Array for get post data
		*/  
		$a_data = array();
		/*  
			Array of Callback function
		*/
		$resp = array();
		/*
			Array of syslog
		*/
		$modify =array();
		/*
			Split the $a_data as a Multipart
		*/
		self::parse_raw_http_request($data,$a_data);
		var_dump($a_data);
		
		/*
			Uploaded document array
		*/
		$dfile = array();
		/*
			Move the uploaded docuemnt names into dfile array
		*/
		$dfile = $data["files"]["doc_file"];

		//var_dump($data["files"]["doc_file"]);
		//var_dump("dfile".$dfile);
		//var_dump(__DIR__);
		//$er=$dfile[0][error];
		//var_dump($er);
		/*
		Findout the Errors
		*/
		ini_set('display_errors', 1);
		error_reporting(E_ALL ^ E_NOTICE);
		/*
			Get the Post array values into variable 
		*/
		$id=$a_data["id"];
		$tlog = $a_data["tlog"];
		$tlog =str_replace("\\","\\\\",$tlog);
		$tlog =str_replace("'","\\'",$tlog);
		//$luid=$a_data["loguserid"];
		$luid = $this->model->redis_hget($a_data["loguserid"],"uid");
		var_dump("user id ". $uid);
		$dtype=$a_data["dtype"];
		//var_dump($dtype);
		//var_dump($a_data["notes"]);
		//var_dump($resp["postdata"]);
		/*
			write a queery to get db values based on postdata values
		*/
		$sql = "select t.id,t.task_name,t.task_assigned,t.why,t.team_member,t.team_member,t.task_assigned,c.name, DATE_FORMAT(fu.dateof_followup,'%a, %e-%b-%y')  from calmet_tasks t left join categorylist c on  
			c.type= t.relate_to and c.id=t.sel_relateid left join calmet_task_followup_dates fu on t.id= fu.task_id and fu.loginid=$luid where t.id=$id";

		var_dump($sql);
		/*
			db values stored in res array 
		*/
		$res = $this->swoole_mysql->query("$sql");
		var_dump($res);
		if($res !== false) {
        ////var_dump($res);
		/*
			Get the task id from db array
		*/
		
		$id=$res[0]["id"];
		$fname ="";
		$fcount=0;

		//$sql1="select * from calmet_task_file where dtype>0 and task_id=$id";
		//var_dump(root_path);
		for ($i=0;$i<3;$i++){
			/*
				make a time based on h:m:s format to save in db
			*/
			$time_now = mktime(date('H'),date('i'),date('s'));
			$date = date('Y-m-d H:i:s',$time_now);
			/*
				Get the uploaded document name based an array (we have 3 link to uld document)
			*/
			$dn=$dfile[$i]["name"];
			/*
				Check the document name and place of the document 
			*/
			
			if($data["files"]["doc_file"][$i]["name"]){
					$filename = $data["files"]["doc_file"][$i]["name"];
					if($fname!=""){
							$fcount++;
							$fname.=", ".$filename;
					}else{
							$fcount++;
							$fname.=$filename;
							
					}
					//var_dump($fname);
					/*
						Set the path for document uld in db
					*/
					$path = root_path.'/tasks_documents/'.$id;
					/*
						Check if the same path already exists
					*/
					if (!file_exists($path)) {
					/*
						if not create a new folder to uld the docuemnt
					*/
				    @mkdir(root_path.'/tasks_documents/'.$id, 0777);
				    @chown(root_path.'/tasks_documents/'.$id,'www-data');
				    @chgrp(root_path.'/tasks_documents/'.$id,'www-data');
				    //echo " dir created successfully";
					}
					//@mkdir(root_path.'/tasks_documents/'.$id, 0777);
					/*
						Check if the document uld un tmg
					*/
			if($dtype>1)
			{		
					/*
						Create a path to uld document in tmg gruop id
					*/
					$path = root_path.'/tasks_documents/'.$id.'/'.$dtype;	
					/*
						Check the path already exists
					*/
					if (!file_exists($path)) {
						/*
							if not create a dir to uld the document
						*/
				    	@mkdir(root_path.'/tasks_documents/'.$id.'/'.$dtype, 0777);
				    	@chown(root_path.'/tasks_documents/'.$id.'/'.$dtype, 'www-data');
				    	@chgrp(root_path.'/tasks_documents/'.$id.'/'.$dtype, 'www-data');
				    	//echo " dir created successfully";
					}	
						/*
							create a var w/ the path and file information
						*/
						$uploadfile = $path .'/'.$filename;
						//var_dump($uploadfile);
						/*
							Check if the file already exists
						*/
					if(is_file($uploadfile))
					{
						//echo "<br>*********check***********";
						/*
							Write a query to get previous file id
						*/
						$selfile="select id,creat_by,creat_date,modi_by,modi_date from calmet_task_file where task_id =".$id ." and dtype =".$dtype." and doc_file = '". basename($filename)."'";
						$resfile = $this->swoole_mysql->query("$selfile");
						//var_dump($resfile[0]["creat_date"]);
						/*
							pathinfo() function returns an array that contains information about a path
						*/
						$pathinfo = pathinfo($uploadfile);
						//var_dump($pathinfo["dirname"]);
						/*
							Create a task_file_data to update the array
						*/
						$task_file_data =array("doc_file"=>''.basename($filename).'',"editmode"=>"0","modi_by"=>$luid,"modi_date"=>''.$date.'',"open_by"=>"0","dtype"=>''.$dtype.'',"ud_date"=>''.$date.'');
							$where="WHERE id='".$resfile[0]["id"]."'";
							/*
								Get the extension of the Document
							*/
						$ext = pathinfo($uploadfile, PATHINFO_EXTENSION);
						/*
							Get the base name of the document
						*/
						$file = basename($uploadfile, ".".$ext);
						//var_dump($file);
						/*
							Create a newname to rev the document
						*/
						$newname=$path .'/'.$file."_rev-1."."$ext";
						//echo "New name =".$newname;
						//$newName = $pathinfo["dirname"]."/".$file."_rev-1.".$pathinfo["extension"];
						/*
							Check the revised document name already exists
						*/
						if(is_file($newname)){
						/*
							if yes , delete the rev name 
						*/
						unlink($newname);
						}
						/*
							Change the uploadfile name as new name 
						*/
						rename($uploadfile,$newname);
						/*
							Insert the rev file name 
						*/
						$sqlrev = "INSERT INTO calmet_task_file (task_id,doc_file,log_id,modi_by,modi_date,pre_file_id,ud_date,dtype) VALUES (".$id.",'".$file."_rev-1.".$ext."',".$luid.",".$resfile[0]["creat_by"].",'".$resfile[0]["creat_date"]."',".$resfile[0]["id"].",now(),".$dtype.") ON DUPLICATE KEY UPDATE editmode=0, modi_by='".$resfile[0]["modi_by"]."',modi_date='".$resfile[0]["modi_date"]."',ud_date=now();";
						//echo "<br> rev query =".$sqlrev;
				        $res = $this->swoole_mysql->query("$sqlrev");
				        /*
				        	Get the new uploaded file name 
				        */
						$tmpname = $data["files"]["doc_file"][$i]["tmp_name"];
						/*
							Move the new file into uploaded path
						*/
					  	if(move_uploaded_file($tmpname,$uploadfile))
					  	{
					  		/*
					  			write a update query to ud the new file on same id  
					  		*/
					  		@chown($uploadfile,'www-data');
					  		@chgrp($uploadfile,'www-data');
					  	 	$sql1 = self::updateSQL("calmet_task_file", $task_file_data,$where);
							//var_dump($sql1);
							$doc = $this->swoole_mysql->query("$sql1");			
							}
							//$fname .= " - Replaced";
					
						}	else{
						/*
							If the uploaded file not exists previously
						*/
						$tmpname = $data["files"]["doc_file"][$i]["tmp_name"];
						/*
							Write a query to Insert new row in a db
						*/
						$task_file_data =array("task_id"=>''.$last_ins.'', "doc_file"=>''.basename($filename).'',"task_id"=>''.$id.'',"creat_by"=>''.$luid.'',"creat_date"=>''.$date.'',"dtype"=>''.$dtype.'',"ud_date"=>''.$date.'');
						/*
							Move the new file into a correct path
						*/
						if(move_uploaded_file($tmpname,$uploadfile))
						{
							/*
								Run a query to insert the file on db
							*/
							@chown($uploadfile,'www-data');
							@chgrp($uploadfile,'www-data');
							$sql = self::insertSQL("calmet_task_file", $task_file_data);	
							echo $sql;
							$doc = $this->swoole_mysql->query("$sql");
						}
						}
			}else{		/*
							If the document uld in main log 
						*/
						$path = root_path.'/tasks_documents/'.$id;
						//var_dump($path);
						$uploadfile = $path .'/'.$filename;
						//var_dump($uploadfile);
						/*
							Check the file already exists in main log
						*/
						if(is_file($uploadfile))
						{   

							//echo "<br>*********check***********";
							/*
								Select the id of the previous file uld in db
							*/
							$selfile="select id,creat_by,creat_date,modi_by,modi_date from calmet_task_file where task_id =".$id ." and dtype=0 and doc_file = '". basename($filename)."'";
							$resfile = $this->swoole_mysql->query("$selfile");
							//var_dump($resfile[0]["creat_date"]);
							/*
								Select the path info for the uploaded file
							*/
							$pathinfo = pathinfo($uploadfile);
							//var_dump($pathinfo["dirname"]);
							
							$task_file_data =array("doc_file"=>''.basename($filename).'',"editmode"=>"0","modi_by"=>$luid,"modi_date"=>''.$date.'',"open_by"=>"0","dtype"=>''.$dtype.'',"ud_date"=>''.$date.'');
							$where="WHERE id='".$resfile[0]["id"]."'";
							$ext = pathinfo($uploadfile, PATHINFO_EXTENSION);
							$file = basename($uploadfile, ".".$ext);
							//var_dump($file);
							/*
								Set the new name with rev-1
							*/
							$newname=$path .'/'.$file."_rev-1."."$ext";
							echo "New name =".$newname;
							//$newName = $pathinfo["dirname"]."/".$file."_rev-1.".$pathinfo["extension"];
							/*
								check if the newname already exists
							*/
							if(is_file($newname)){
							/*
								If the newname already exists delete the previous rev-1 name file 
							*/
							unlink($newname);}
							/*
								rename the uploaded fiel into newname
							*/
							//var_dump("uploaded file :".$uploadfile);
							//var_dump("New File Name :". $newname);
							if(rename($uploadfile,$newname)){
								//var_dump("File Renamed");
							}else{
								//var_dump("file Rename Failed");
							}
							/*
								get the tmpname of the file 
							*/
							$tmpname = $data["files"]["doc_file"][$i]["tmp_name"];
							/*
								Move the Newfile into previous file id
							*/
						  	if(move_uploaded_file($tmpname,$uploadfile)){
						  		@chown($uploadfile,'www-data');
						  		@chgrp($uploadfile,'www-data');
						  		$sql1 = self::updateSQL("calmet_task_file", $task_file_data,$where);
						  		//echo "<br><br>query = ".$sql1;
								$doc = $this->swoole_mysql->query("$sql1");
						  	}	
						  	/*
						  		Insert new row for previous file w/ new name rev-1 with the original file id
						  	*/
							$sqlrev = "INSERT INTO calmet_task_file (task_id,doc_file,log_id,modi_by,modi_date,pre_file_id,ud_date) VALUES (".$id.",'".$file."_rev-1.".$ext."',".$luid.",".$resfile[0]["creat_by"].",'".$resfile[0]["creat_date"]."',".$resfile[0]["id"].",now()) ON DUPLICATE KEY UPDATE editmode=0, modi_by='".$resfile[0]["modi_by"]."',modi_date='".$resfile[0]["modi_date"]."',ud_date=now();";
							echo "<br> rev query =".$sqlrev;
				           $res = $this->swoole_mysql->query("$sqlrev");
				        //$fname .= " - Replaced";
								
						}else{
							/*
								If the file not exists on the main log, write a query to insert new row
							*/
							//echo "new entry";
							$task_file_data =array("task_id"=>''.$last_ins.'', "doc_file"=>''.basename($filename).'',"task_id"=>''.$id.'',"creat_by"=>''.$luid.'',"creat_date"=>''.$date.'',"ud_date"=>''.$date.''); 
							$tmpname = $data["files"]["doc_file"][$i]["tmp_name"];
							/*
								Move the file on to the correct uploaded path
							*/
							move_uploaded_file($tmpname,$uploadfile);
							@chown($uploadfile,'www-data');
							@chgrp($uploadfile,'www-data');
							$sql = self::insertSQL("calmet_task_file", $task_file_data);	
							echo $sql;
							$doc = $this->swoole_mysql->query("$sql");
						}
						
 
					}
				}
		
			}
			if($fcount>1){
				$filelog.= "Documents uploaded: ". $fname ;
			}else{
				$filelog.= "Document uploaded: ". $fname ;
			}
			/*if($tlog!=""){
			if($dtype==""){
				$sql="INSERT INTO calmet_tasks_comments (loginid,task_id,comments,comments1,dateof_followup,created_date,otask_id,ltype,nottms,inttms) VALUES ($uid,".$tid.",'". $tlog ."','". $tlog ."','". $fdate ."',now(),".$tid.",0,'" . $notsent[0]["names"]."','".$instsent[0]["names"]."');"	;
				//var_dump($sql);
				$doc = $this->swoole_mysql->query($sql);
				$Sel_Com_Pros = "INSERT INTO calmet_task_log_temp (uid,tid,tlog,ltime,grpid) VALUES ($uid,$tid,'',now(),0) ON DUPLICATE KEY UPDATE tlog='', ltime=now();";
				$doc = $this->swoole_mysql->query($Sel_Com_Pros);
			}else{
					$sql="INSERT INTO calmet_tasks_comments (loginid,task_id,comments,comments1,dateof_followup,created_date,otask_id,ltype,nottms,inttms) VALUES ($uid,".$tid.",'". $tlog ."','". $tlog ."','". $fdate ."',now(),".$tid.",".$dtype.",'" . $notsent[0]["names"]."','".$instsent[0]["names"]."');";
						//var_dump($sql);
						$doc = $this->swoole_mysql->query($sql);
						$Sel_Com_Pros = "INSERT INTO calmet_task_log_temp (uid,tid,tlog,ltime,grpid) VALUES ($uid,$tid,'',now(),".$dtype.") ON DUPLICATE KEY UPDATE tlog='', ltime=now();";
				$doc = $this->swoole_mysql->query($Sel_Com_Pros);
					}
						if($star==1){
						  $retid=$this->swoole_mysql->query("SELECT LAST_INSERT_ID() as id;");
							$retid = $retid[0]["id"];
							var_dump($retid);
							$Sel_Com_Pros = "INSERT INTO calmet_task_log_followup (tid,tlid,uid,star,w1) VALUES (".$tid.",".$retid.",".$uid.",".$star.",now()) ON DUPLICATE KEY UPDATE star=".$star.";";
							 //var_dump($Sel_Com_Pros);
								$doc = $this->swoole_mysql->query($Sel_Com_Pros);	

						}
			}*/
			
			//var_dump($filelog);
			/*if($dtype==""){
				$sql="INSERT INTO calmet_tasks_comments (loginid,task_id,comments,comments1,created_date,otask_id,ltype) VALUES (".$luid.",".$id.",'". $filelog ."','". $filelog ."',now(),".$id.",0);"	;
				//var_dump($sql);
				$doc = $this->swoole_mysql->query("$sql");
				}else{
				$sql="INSERT INTO calmet_tasks_comments (loginid,task_id,comments,comments1,created_date,otask_id,ltype) VALUES (".$luid.",".$id.",'". $filelog ."','". $filelog ."',now(),".$id.",".$dtype.");";
				//var_dump($sql);
				$doc = $this->swoole_mysql->query("$sql");
				}
	      */
			}
		$ret = array('output' => $filelog,'token' => $data["token"]);
	 	}else if($data["trimmedPath"]=="api/updloghl"){
	 			$qs = json_decode($data["payload"]);
	 			$uid = $this->model->redis_hget($qs->uid,"uid");
				//var_dump($qs);			 		
				$hlsql = "Update calmet_tasks_comments SET comments1 = '". $qs->tlog ."',modified_date=now(),hl=". $qs->hlval ."  where id = $qs->lid";
				//var_dump($hlsql);
				$ret = $this->swoole_mysql->query($hlsql);
				//var_dump($ret);
				/*$id = $qs->tid;
				$tlog = $_POST['tlog'];
				$hl = $_POST['hlval'];
				$tlog =str_replace("\\","\\\\",$tlog);
				$tlog =str_replace("'","\\'",$tlog);
				$tlog =str_replace("<br>","",$tlog);
				
				echo $sql;
				$exe_obj->exquteQuery($sql); */
				$ctype="text";
				$ret = array('output' => "Sucess",'token' => $data["token"]);
				//$ret="Sucess";
		}else if($data["trimmedPath"]=="api/mrmsglog"){

				$qs=json_decode($data["payload"]);
				$uid = $this->model->redis_hget($qs->params->uid,"uid");
				var_dump($qs->params->msglogs);
				$n=json_decode($qs->params->msglogs);
				$j=count($n);
				var_dump($j);
				//var_dump($n[1]->task_name);
				for ($k=0;$k<$j;$k++){
					$Sel_Com_Pros = "Update calmet.calmet_notification set shown=1,readed=0, dtype=".$n[$k]->dtype.",viewed=now() where task_id=".$n[$k]->task_id." and not_tms=".$uid." and dtype=".$n[$k]->dtype.";";
					var_dump($Sel_Com_Pros);
					$sql = $this->swoole_mysql->query($Sel_Com_Pros);

				}
				$ret = array('output' => "Sucess",'token' => $data["token"]);

				//$qs=json_decode($data["payload"]);
				//var_dump($qs[1]->task_name);
				//json_decode($data["payload"]) ;				
				//$when=$qs=>["params"]=>["msglogs"];
				//var_dump($when);

		}else if($data["trimmedPath"]=="api/updgroup"){
	 			$qs = json_decode($data["payload"]);
	 			$uid = $this->model->redis_hget($qs->uid,"uid");
	 			$tms = $qs->tms;
				$seltms="select GROUP_CONCAT(DISTINCT ini ORDER BY  ini ASC SEPARATOR ' ') as grpname  from calmet_users where  find_in_set(id,'".$tms."')";
				var_dump($seltms);
				$ret = $this->swoole_mysql->query($seltms);
				if($ret){
					$grpname = $ret[0]["grpname"];
					$seltmg="select * from calmet_tmgroup where name ='". $grpname ."' or tms = '". $tms."'";
					var_dump($seltmg);
					$ret = $this->swoole_mysql->query($seltmg);
					var_dump($ret);
					if($ret){
						$res = array("result"=>"Group Already Exists");
					}else{
						$sql = "INSERT INTO calmet_tmgroup (name,tms) VALUES ('".$grpname."','".$tms."');";
						$ret=$this->swoole_mysql->query($sql);
						$retid=$this->swoole_mysql->query("SELECT LAST_INSERT_ID() as id;");
						$retid = $retid[0]["id"];
						$res = array("id"=>$retid,"grpname"=>$grpname,"tms"=>$tms,"result"=>"Success");
					}
				}
		
				
				//$ret = $this->swoole_mysql->query($seltmg);
				//var_dump($ret);
				/*if($selrescount>0){
					echo "1~$grpname~TM Group already exists!~";
				}else{
					$sql = "INSERT INTO calmet_tmgroup (name,tms) VALUES ('".$grpname."',".$tms.");";
				}*/

				$ret = array('output' => $res,'token' => $data["token"]);
		}
		//var_dump($data);
		//var_dump(json_decode($data["payload"]));
		//return "Hai";
		/*	$tokendata = self::Auth($data);
			if($tokendata["loginstat"]){
				$token = $data["token"];
				$mem_id = $this->model->redis_hmget($tokendata["uid"],"uid");
				$mem_id = $mem_id[0];
			} */


		$callback(202,$ret,$ctype);
	}







	public function get($data,callable $callback){
		$ctype= "html";
		$start_time = microtime(true); 
		//var_dump($data["trimmedPath"]);
		//var_dump($data);
		/*$headers =json_decode($data["headers"]);
		$authHeader = $headers->authorization;
		list($jwt) = sscanf($authHeader, 'Bearer %s');
		var_dump("jwt   : ".$jwt);
		if($jwt=="undefined" || $jwt==""){
			$ret = array('output' => 'Authorization Failed', );
			$callback(202,$ret,$ctype);

		}else{
			$key = "example_key";
			$decoded = JWT::decode($jwt, $key, array('HS256'));	
			$uid = $decoded->id;
			$uname = $decoded->name;
		} */
		
		//var_dump("_____________________________________________________________________________________________________________________________");
		$ctype='html';
		//var_dump($data["trimmedPath"]);
		$hpath = explode("/",$data["trimmedPath"]);
		//var_dump($hpath[1]);
		//$this->swoole_mysql->connect($this->server);
		//var_dump($data["trimmedPath"]);
		$qs = json_decode($data["queryStringObject"]);
		$uid = $this->model->redis_hget($qs->uid,"uid");
		//$this->model->expire($qs->uid);
		//var_dump($uid);
		if($uid){
			
			//exit('uid not found');
		
		if($data["trimmedPath"]=="api/auth/user"){
			$users = "Select name from calmet_users  where id=$uid";
			$ret = $this->swoole_mysql->query($users);

		}else if($hpath[0]="api"){
			$qs = json_decode($data["queryStringObject"]);
			//var_dump($qs);
			
			
			//Get Task List data for different criteria
			if($hpath[1]=="taskslist"){
				//var_dump(json_encode($qs));
				if(isset($qs->showalldate) && $qs->showalldate=="true") {
					$showall = 1;
				}else{
					$showall = 0;
				}
				//Get Meeting based task details from Meeting Page
				if(isset($qs) && isset($qs->m) && $qs->m=="mid"){
						$ret = $this->swoole_mysql->query("CALL meetlist32_new(".$uid.",".$qs->mid.",'".$qs->dval."',2,'".$qs->type."',@output)");

				}else if(isset($qs) && isset($qs->m) && $qs->m=="cate"){
					$ret = $this->swoole_mysql->query("CALL list232(".$uid.",'cate','".$qs->cate."',2,$showall, @output);");		
				
				}else if(isset($qs) && isset($qs->m) && $qs->m=="mee"){
					//var_dump("CALL meetlist(".$uid.",'mee','".$qs->mee."','2022-01-01',2, @output);");
					$ret = $this->swoole_mysql->query("CALL meetlist32_new(".$uid.",'".$qs->mee."','2050-01-01',2,'".$qs->type."', @output);");		

				//Get task list based on search text entered in regular search.
				}else if(isset($qs) && isset($qs->m) && $qs->m=="txt"){
					//CALL list2(".$uid.",'cate','System',2,@output)
					$ret = $this->swoole_mysql->query("CALL list232(".$uid.",'search','".$qs->txt."',2,$showall, @output);");		
					$ret2 = $this->swoole_mysql->query("select @output");		
				
				//Get task list based on Search text entered in Advance Search
				}else if(isset($qs) && isset($qs->m) && $qs->m=="advtxt"){
					//CALL list2(".$uid.",'cate','System',2,@output)
					//var_dump("CALL list2(".$uid.",'".seaadv."','".$qs->advtxt."',0, @output)");
					$ret = $this->swoole_mysql->query("CALL list232(".$uid.",'seaadv','".$qs->advtxt."',2,$showall, @output);");		
				
				//Get task list default option.
				}else if(isset($qs) && !isset($qs->m) && isset($qs->type)){
					var_dump("Hi"."<br><br>");
					$ret = $this->swoole_mysql->query("CALL list32('".$uid."',2,'".$qs->type."',@output)");		
					//var_dump($ret);
				
				}else if(isset($qs) && isset($qs->m) && $qs->m=="pyt"){
					$Sel_Com_Pros	= "INSERT INTO calmet_task_followup_dates (task_id,loginid,pyt) VALUES (".$qs->id.",".$uid.",".$qs->pyt.") ON DUPLICATE KEY UPDATE pyt=".$qs->pyt.";";
					$ret = $this->swoole_mysql->query($Sel_Com_Pros);
				
				}else if(isset($qs) && isset($qs->m) && $qs->m=="sno"){
					$Sel_Com_Pros	= "update calmet.calmet_task_followup_dates SET msnoozedt =  now()+INTERVAL ".$qs->sno." MINUTE, snoozedt =  now()+INTERVAL ".$qs->sno." MINUTE where id =".$qs->id." and loginid=".$uid." ;";
					$ret = $this->swoole_mysql->query($Sel_Com_Pros);
				
				}else{
					$ret = $this->swoole_mysql->query("CALL list32('".$uid."',2,0,@output)");		
				}
			}else if($hpath[1]=="getattn"){
				try {

					//connection params
					$dbCon = new PDO('odbc:Driver=FreeTDS;Server=192.168.5.204; Port=1433;Database=eTimetracklite;TDS_Version=7.0; ClientCharset=UTF-8', 'sa', 'sql');

					$id= 5196;
					//test query
					$result = $dbCon->query('SELECT * FROM [9001] order by date2 desc')->fetchAll();

					$ret =  json_encode($result);

					//close the connection
					$dbCon = null;

					} catch (PDOException $e) {

					//show exception
					$ret =  "Error : " . $e->getMessage() ."\n";

				}

			}else if($hpath[1]=="getreminders"){
					//var_dump($qs);
					$uid = $uid;
					$remsql = "CALL getreminders($uid,@output)";
					$res = $this->swoole_mysql->query($remsql);
					$ret = $res;
					
					//var_dump($ret);
			}else if($hpath[1]=="snooze"){
				//var_dump($qs);
				if($qs->type=="task"){
					$Sel_Com_Pros	= "update calmet.calmet_task_followup_dates SET  snoozedt =  now()+INTERVAL ".$qs->sno." MINUTE where task_id =".$qs->tid." and loginid=".$uid." ;";
				}else{
					$Sel_Com_Pros	= "update calmet.calmet_task_meetings SET msnoozedt =  now()+INTERVAL ".$qs->sno." MINUTE where task_id =".$qs->tid." and loginid=".$uid." and meetid = ".$qs->meetid." ;";
					
					//$ret = $this->swoole_mysql->query($Sel_Com_Pros);
				}
				$ret = $this->swoole_mysql->query($Sel_Com_Pros);
				$ret = true;
			}else if($hpath[1]=="remdismiss"){
				//var_dump($qs);
				if($qs->type=="task"){
					$Sel_Com_Pros= "update calmet.calmet_task_followup_dates SET alarm =0 where task_id =".$qs->tid." and loginid=".$uid." ;";
				}else{
					$Sel_Com_Pros	= "update calmet.calmet_task_meetings SET alarm2 =0  where task_id =".$qs->tid." and loginid=".$uid." and meetid = ".$qs->meetid." ;";
				}
				var_dump($Sel_Com_Pros);
				$ret = $this->swoole_mysql->query($Sel_Com_Pros);
			}else if($hpath[1]=="noti"){
				//var_dump($qs);
					$n=$qs->shown;
				if($qs->shown==1){
				$Sel_Com_Pros = "Update calmet.calmet_notification set shown=".$qs->shown.",readed=".$qs->shown.", dtype=".$qs->dtype.",viewed=now() where task_id=".$qs->tid." and not_tms=".$uid.";";
				//var_dump($Sel_Com_Pros);
					$ret = $this->swoole_mysql->query($Sel_Com_Pros);
				}
				$Sel_Com_Pros="select ct.task_id,if(isnull(ct.dtype),0,ct.dtype) as dtype ,CONCAT(GROUP_CONCAT(cu.name order by ct.when1 asc),\" updated \" , p.task_name) as description , CONCAT(\"task6.php?id=\",CAST(ct.task_id as char),\"&tval=\",p.task_name,\"\") as url,date_format(max(ct.when1),'%a %e-%b %l:%i %p') as when1, GROUP_CONCAT(CAST(ct.id as char) order by ct.when1 asc) as id from calmet_notification ct 
						inner join calmet_users cu on ct.tm = cu.id 
						inner join calmet_tasks p on p.id = ct.task_id
						where  ct.ntype= 0 and ct.shown =0 and ct.not_tms = ".$uid." 
						group by ct.task_id order by max(ct.when1) asc;";
					$notification = $this->swoole_mysql->query($Sel_Com_Pros);
					//var_dump($Sel_Com_Pros);
					$Sel_Com_Pros= "select ct.task_id, if(isnull(ct.dtype),0,ct.dtype) as dtype from calmet_notification ct where ct.ntype = 1 and ct.not_tms = ".$uid." and ct.readed = 0 group by ct.task_id asc;";
					//$Sel_Com_Pros="select ct.task_id,if(isnull(ct.dtype),0,ct.dtype) as dtype from calmet_notification ct where  ct.ntype= 1 and ct.shown = 0 and ct.not_tms = ".$uid."  	group by ct.task_id asc;";
					//var_dump($Sel_Com_Pros);
					$instant = $this->swoole_mysql->query($Sel_Com_Pros);
					$ret = array('notification'=>$notification,'instant'=>$instant);

				
					
				//var_dump($ret);
				//var_dump($Sel_Com_Pros);
				//var_dump($ret);
			}else if($hpath[1]=="readfile1"){
				var_dump($hpath[1]);
				var_dump($qs);
				//readfile('/home/deepa/Desktop/tasks/tasks_documents/3169/Ebrc Adj 190819.xlsx');
				$fname = $qs->doc;//$hpath[2];
				$uid = $uid;
				$tid = $qs->tid;
				$mode=$qs->emode;
				$did=$qs->docid;
				if($mode==1){
					$Sel_Com_Pros="update calmet_task_file set editmode=".$mode.",open_by=".$uid." where task_id=".$tid." and id=".$did." and dtype=".$qs->dtype." ;";
					$ret = $this->swoole_mysql->query($Sel_Com_Pros);
					var_dump($Sel_Com_Pros);
				}else{
					$ret = "view";
				}

			}else if($hpath[1]=="readfile"){
				//var_dump($hpath[1]."---".$hpath[2])
				//var_dump($qs);
				//readfile('/home/deepa/Desktop/tasks/tasks_documents/3169/Ebrc Adj 190819.xlsx');
				$fname = $qs->doc;//$hpath[2];
				$uid = $uid;
				$tid = $qs->tid;
				$mode=$qs->emode;
				$did=$qs->docid;
				if(isset($qs->dtype) && $qs->dtype!=0 ){
					$dtype = $qs->dtype;
					$path = root_path .'/tasks_documents/'.$tid.'/'.$dtype.'/'.$fname;
				}else{
					$path = root_path. '/tasks_documents/'.$tid.'/'.$fname;
				}
				echo exec('whoami');
				var_dump($path);
				if($mode==1){
					$Sel_Com_Pros="update calmet_task_file set editmode=".$mode.",open_by=".$uid." where task_id=".$tid." and id=".$did." and dtype=".$qs->dtype." ;";
					$ret = $this->swoole_mysql->query($Sel_Com_Pros);
					var_dump($Sel_Com_Pros);
				}
				

				preg_match("/[^\/]+$/", $fname, $matches);
				$last_word = $matches[0]; // test
				$split_ext = explode(".",$last_word);
				$split_ext=$split_ext[1];
				//var_dump("EXtension : ".$split_ext);
				if($split_ext=="xlsx"){
					$type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
				}else if($split_ext=="xlx"){
					$type = 'application/vnd.ms-excel';
				}else if($split_ext=="docx"){
					$type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
				}else if($split_ext=="doc"){
					$type = 'application/msword';
				}else if($split_ext=="pptx"){
					$type = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
				}else if($split_ext=="ppt"){
					$type = 'application/vnd.ms-powerpoint';
				}else{ 
					$type = 'application/octet-stream';
				} 
				if(is_file($path)){
					$ctype='file';
					$ret = array('path' => $path, 'fname'=> $fname, 'type' => $type);	
				}else{
					$ret = "false";
				}
				var_dump($ret);

			}else if($hpath[1]=="chkfile"){
				//var_dump($qs);
				$loguserid=$uid;
				$fname = $qs->fname;
				$dtype = $qs->dtype;
				$id=$qs->id;
				$fname =str_replace("\\","\\\\",$fname);
				$fname =str_replace("'","\\'",$fname);
				$selfile="select id,pre_file_id from calmet_task_file where task_id =". $qs->id ." and doc_file = '". $qs->fname."' and dtype = ".$qs->dtype;
				//var_dump($selfile);
				//$resfile = $connect->query($selfile);
				$ret1 = $this->swoole_mysql->query($selfile);
				//$selrescount = $ret->rowCount(); //mysqli_num_rows($resfile);
				if(count($ret1)>0){
					
					$i=0;
					while($i < count($ret1)){ //mysqli_fetch_array($resfile)){
						 $task_id[] = $ret1[$i]["id"];
						 $task_preid[] = $ret1[$i]["pre_file_id"];
						 $i++;
					}
					
					if($task_preid[0]!=''){
						//echo "Edit@I am Sorry ! Its a Backup File of ".  $task_preid[0]. ", So you download and view it.\n But can't upload it again to Server...!";
						$selfile="select cf.modi_by,cf.doc_file from calmet_task_file cf where cf.id =". $task_preid[0] ." and  1=1";
						//echo $selfile;
						$ret2 = $this->swoole_mysql->query($selfile);
						if(count($ret2)>0){
							$j=0;
							while($j<count($ret2)){
								 $docname[] = $ret2[$j]["doc_file"];
								 $tmid[] = $ret2[$j]["modi_by"];
								 $j++;
							}
							$ret = ['mode' =>'edit',
									 'msg' => 'I am Sorry ! Its a backup file of '.  $docname[0]. ', \n So you can only download and view it. But can\'t upload it again to Server...!',
									 'did' => $task_id[0],
									];
							var_dump(json_encode($ret));
							//echo "Edit@I am Sorry ! Its a backup file of ''".  $docname[0]. "'',\nSo you can only download and view it. But can't upload it again to Server...!";
						}
					}
					else {
					//echo $task_id[0];
					$selfile="select cu.name,cf.open_by from calmet_task_file cf inner join calmet_users cu on cu.id = cf.open_by where cf.id =". $task_id[0] ." and cf.editmode=1";
					
					$ret3 = $this->swoole_mysql->query($selfile);
					//$selrescount =  $resfile->rowCount(); 
					if(count($ret3)>0){
						$j=0;
						while($j<count($ret3)){
							 $tmname[$j] = $ret3[$j]["name"];
							 $tmid[$j] = $ret3[$j]["open_by"];
							 $j++;
						}
						var_dump($loguserid."==".$tmid[0]);
						if($loguserid==$tmid[0]){
							$ret = ['mode' =>'true',
									 'msg' => 'File downloaded by you, So you can upload this file...!',
									 'did' => $task_id[0],
									];
							//echo "True@File downloaded by you, So you can upload this file...!@".$task_id[0];
						}else {
							//\n B\'cas this document already downloaded by '". $tmname[0] ."' for editing...! \nYou can upload only if you were downloaded the original file...!"'
							$ret = ['mode' =>'edit',
									 'msg' => 'I am sorry...You are unable to upload...! \n B\'cas this document already downloaded by '. $tmname[0] .' for editing...! \nYou can upload only if you were downloaded the original file...!',
									 'did' => $task_id[0],
									];
							var_dump(json_encode($ret));
						}
					}else{
						$ret = [ 'mode'=>'edit',
									 'msg' => 'I am sorry...You are unable to upload...!\n Without download a original file, you can\'t upload it in same name...!',
									 'did' => $task_id[0],
									];
							var_dump(json_encode($ret));
					}
					}
				}
				else{
					if(strpos($fname,'_rev-1')!== false){
						$ret = [ 'mode'=>'edit',
									 'msg' => 'Edit@I am sorry...It\'s a Backup Files...!',
									 'did' => $task_id[0],
									];

						//echo "Edit@I am sorry...It's a Backup Files...!@";
					}$ret = [ 'mode'=>'true',
									 'msg' => 'False@File not in Server',
									 'did' => 0,
									];
					echo "False@File not in Server";
				}
			}else if($hpath[1]=="ntask"){
				var_dump($qs);
				var_dump($qs->tid);
				$Sel_Com_Pros="insert into calmet.calmet_tasks (relate_to,sel_relateid,date_followup,team_member,task_name,date_created,why,created_by,task_status,task_assigned) values(".$qs->ctype.",".$qs->cid.",now(),'".$uid."','".$qs->ntask."',now(),'".$qs->why."','".$uid."',1,'".$uid."');";
							//var_dump($Sel_Com_Pros);
				 $ret = $this->swoole_mysql->query($Sel_Com_Pros);
		      			 // $m=last_insert_id();
				 $tid=$this->swoole_mysql->query("SELECT LAST_INSERT_ID() as id;");							
				 $taskid=$tid[0];				
				 $tlog="Task created by ".$qs->uname;
				 $sql="INSERT INTO calmet_tasks_comments (loginid,task_id,comments,comments1,created_date,otask_id,ltype) VALUES 
						          (".$uid.",".$taskid["id"].",'". $tlog ."','". $tlog ."',now(),".$taskid["id"].",0);";						
				 $sql2 = $this->swoole_mysql->query($sql);
				 $ret=$tid;						 					
			//Delete Log
			}else if($hpath[1]=="deletelog"){
				//var_dump($qs->lid);
				$Sel_Com_Pros="update calmet_tasks_comments set comments='<<<<< Deleted >>>>>',comments1='<<<<< Deleted >>>>>' where id='".$qs->lid."' and task_id='".$qs->tid."' and loginid='".$uid."';";
				//var_dump($Sel_Com_Pros);
				$ret = $this->swoole_mysql->query($Sel_Com_Pros);

				//select * from calmet_tasks_comments where comments1 = '<<<<< Deleted >>>>>' and DATE(created_date)<=DATE(date_add(now(),interval -15 day))

		      	// $m=last_insert_id();
								 					
			//Get Document list 
			}else if($hpath[1]=="doclist"){
					//var_dump($qs->dtype);

					  $Sel_Com_Pros	= "SELECT f.id,f.task_id,f.doc_file,if(f.pre_file_id>0,1,f.editmode) as edit_mode,f.open_by,if(f.open_by=".$uid.",'You..!',if(f.open_by>0,u.name,'')) as openby,f.pre_file_id,IF(ISNULL(f.modi_by),u1.name,u2.name) as luser,
						IF(f.open_by>0,DATE_FORMAT(f.ud_date,'%e-%b-%y %l:%i %p'),IF(ISNULL(f.modi_date),DATE_FORMAT(f.creat_date,'%e-%b-%y %l:%i %p'),DATE_FORMAT(f.modi_date,'%e-%b-%y %l:%i %p'))) as ldate,
						IF(f.open_by>0,f.ud_date,IF(ISNULL(f.modi_date),f.creat_date,f.modi_date)) as l2date
						from calmet_task_file f  
						left outer join calmet_users u on u.id = f.open_by 
						left outer join calmet_users u1 on u1.id = f.creat_by 
						left outer join calmet_users u2 on u2.id = f.modi_by  
						where task_id IN (".$qs->doc.") and dtype = ".$qs->dtype." order by f.pre_file_id,ud_date desc, doc_file asc;";
						//var_dump($Sel_Com_Pros);

						//$Sel_Com_Pros="i am document link";
					  // //var_dump($Sel_Com_Pros);
						$ret = $this->swoole_mysql->query($Sel_Com_Pros);
						
			//Get TM Meeting List 
			}else if($hpath[1]=="taskmeetinglist"){
					$sel_qry = "Select u.id as uid, if(u.id='".$uid."',1,0) as tmmeet, u.ini, cm.id as mid, CONCAT_WS('-',md.meet_code,md.tms)  as mdesc, CONCAT_WS(' ',DATE_FORMAT(tf.meet_date,'%a, %e-%b-%y'),TIME_FORMAT(meet_recu_stime, '%l:%i %p')) as mdatetime, DATE_FORMAT(tf.meet_date,'%Y-%m-%d') as msdate, DATE_FORMAT(tf.meet_date,'%a, %e-%b-%y') as mdate,tf.alarm2 	from calmet_meeting cm 	
				left outer join calmet_task_meetings tf on cm.id = tf.meetid 
				left outer join meet_det md on md.id = tf.meetid
				left outer join calmet_users u on u.id = tf.loginid
				where tf.task_id = '".$qs->tid."'
				and FIND_IN_SET('".$uid."',cm.meet_tms) order by concat(tf.meet_date,'',cm.meet_recu_stime) asc";
				$ret = $this->swoole_mysql->query($sel_qry);

				
			//Get TM Meeting List 
			}else if($hpath[1]=="tmmeetinglist"){
				var_dump($qs);
					$sel_qry = "SELECT m.id as mid,TIME_FORMAT(m.meet_recu_stime, '%l:%i %p') as stime, 
							CONCAT(m.meet_code,' - ',(select GROUP_CONCAT(' ',u2.name) from calmet_users u2 where find_in_set(u2.id,m.meet_tms) order by u2.name )) as mode, 
							if(tf.meetid,1,0) as selmet, CONCAT_WS('-',md.meet_code,md.tms)  as mdesc, u2.ini, '".$uid."' as uid
							FROM calmet_meeting m 
							left outer  join calmet_task_meetings tf on m.id = tf.meetid and tf.loginid = '".$uid."' and tf.task_id = '".$qs->tid."'
							left outer join meet_det md on md.id = m.id
							left outer join calmet_users u2 on u2.id = '".$uid."'
							left outer join calmet_users u on FIND_IN_SET(u.id,meet_tms) where FIND_IN_SET('".$uid."',meet_tms) and meet_stat=1 group by m.id ORDER BY meet_order,2 ASC";
					
					$ret = $this->swoole_mysql->query($sel_qry);
				
			//Get Task details for Task Page
			}else if($hpath[1]=="taskdetails"){
				if($uid==95){
					var_dump($qs);
				}
				
					$ret = $this->swoole_mysql->query("CALL gettaskpagedata(".$uid.",'".$qs->tid."',@output)");		
					//$ret = $this->swoole_mysql->query('CALL list(77,2,@output)');		
					//var_dump($ret);
				//}
			}else if($hpath[1]=="tmpgrplist"){
				//var_dump($qs);
					$select_Qry ="SELECT id,name,LENGTH(tms) as tml,(SELECT group_concat(' ',name) FROM calmet_users WHERE FIND_IN_SET(id, tms) ORDER BY 1 asc) as tms FROM calmet_tmgroup  WHERE Find_In_set(". $uid.",tms) order by tml,name";		
					//var_dump($select_Qry);
					$ret = $this->swoole_mysql->query($select_Qry);		
				
				//var_dump($ret);
			}else if($hpath[1]=="templog"){
				//var_dump("am tmg log $#$#%$#$%#^$^%$");
				$tid=$qs->tid;
				$ltype=$qs->ltype;
				$nlog=$qs->nlog;
				$tlog=$qs->tlog;
				$tlog =str_replace("\\","\\\\",$tlog);
				$tlog =str_replace("'","\\'",$tlog);
				$nlog =str_replace("\\","\\\\",$nlog);
				$nlog =str_replace("'","\\'",$nlog);
				if($ltype==""){
						$ltype=0;
				}
				$Sel_Com_Pros = "INSERT INTO calmet_task_log_temp (uid,tid,tlog,ltime,grpid) VALUES (".$uid.",".$tid.",'". $tlog ."',now(),".$ltype.") ON DUPLICATE KEY UPDATE tlog='". $tlog ."', ltime=now();";
				//var_dump($Sel_Com_Pros);
				$ret = $this->swoole_mysql->query($Sel_Com_Pros);
				$Sel_Com_Pros = "INSERT INTO calmet_task_log_temp (uid,tid,nlog,ltime,grpid) VALUES (".$uid.",".$tid.",'". $nlog ."',now(),0) ON DUPLICATE KEY UPDATE nlog='". $nlog ."', ltime=now();";
				//var_dump($Sel_Com_Pros);
				$ret = $this->swoole_mysql->query($Sel_Com_Pros);
				

				//$Sel_Com_Pros="select tlog,ltime,nlog from calmet.calmet_task_log_temp where tid=".$tid." and uid=".$uid.";";
				//$ret = $this->swoole_mysql->query($Sel_Com_Pros);
			}else if($hpath[1]=="updateread"){
				$uid = $uid;
				$tid = $qs->tid;
				$logid = $qs->logid;
				$sql = "insert into calmet_task_log_followup (uid,tid,tlid,w1,readed) values ($uid,$tid,$logid,now(),0) ON DUPLICATE KEY UPDATE w1=now(),readed=0";	
				$ret = $this->swoole_mysql->query($sql);

			//Get TMG Button list for Task Page
			}else if($hpath[1]=="tmgbtndet"){
					//$tmg_sql= "Select tc.ltype, count(if(if(isnull(lf.readed),if(tc.id>205222,1,lf.readed),lf.readed)>0,1,NULL)) as count,tg.name,tg.tms  FROM calmet.calmet_tasks_comments tc inner join calmet_tmgroup tg on tc.ltype = tg.id and find_in_set(".$uid.",tms) LEFT JOIN calmet_task_log_followup lf ON (lf.tlid = tc.id and lf.uid = ".$uid.") where tc.task_id = '".$qs->id."' group by tc.ltype";
					//$tmg_sql="Select tc.ltype,count(if(lf.star=1,1,null)) as mcount,count(if(isnull(lf.readed) and tc.id>300000,1,null)) as rcount,tg.name,tg.tms  FROM calmet.calmet_tasks_comments tc inner join calmet_tmgroup tg on (tc.ltype = tg.id) LEFT JOIN calmet_task_log_followup lf ON ((lf.tlid = tc.id and lf.uid = ".$uid." and tid='".$qs->tid."') ) where tc.task_id = '".$qs->tid."' and find_in_set('".$uid."',tg.tms) group by tc.ltype; ";
					//$tmg_sql ="Select tc.ltype,count(if(lf.star=1,1,null)) as mcount,(select count(tc1.id) from calmet_tasks_comments tc1 LEFT OUTER JOIN calmet_task_log_followup lf1 ON tc1.id = lf1.tlid where tc1.task_id = '".$qs->tid."' and tc1.ltype=tg.id and tc1.loginid != '".$uid."' and (lf1.uid = '".$uid."' or isnull(lf1.uid)) and (lf1.readed!=0 or isnull(lf1.readed))) as rcount, tg.name,tg.tms  FROM calmet.calmet_tasks_comments tc inner join calmet_tmgroup tg on (tc.ltype = tg.id) LEFT JOIN calmet_task_log_followup lf ON ((lf.tlid = tc.id and  tid='".$qs->tid."') ) where tc.task_id = '".$qs->tid."' and find_in_set('".$uid."',tg.tms) group by tc.ltype;";
					//$tmg_sql= "Select tc.ltype, count(if(if(isnull(lf.readed),1,lf.readed),1,NULL)) as count,tg.name,tg.tms  FROM calmet.calmet_tasks_comments tc inner join calmet_tmgroup tg on tc.ltype = tg.id and find_in_set(".$uid.",tms) LEFT JOIN calmet_task_log_followup lf ON (lf.tlid = tc.id and lf.uid = ".$uid.") where tc.task_id = '".$qs->id."' group by tc.ltype";

					//$tmg_sql = "Select tc.ltype,count(if(lf.star=1,1,null)) as mcount,count(if(isnull(lf.readed) and tc.loginid!='".$uid."' and tc.id>300000,1,null)) as rcount,tg.name,tg.tms  FROM calmet.calmet_tasks_comments tc inner join calmet_tmgroup tg on (tc.ltype = tg.id) LEFT JOIN calmet_task_log_followup lf ON ((lf.tlid = tc.id and lf.uid = '".$uid."' and  lf.tid='".$qs->tid."') ) where tc.task_id = '".$qs->tid."' and find_in_set('".$uid."',tg.tms) group by tc.ltype;";
					//var_dump("TMG SQL : " .$tmg_sql);
					//var_dump("------------------------");
					$tmg_sql="select tc.ltype,count(if(lf.star=1,1,null)) as mcount,count(if(isnull(lf.readed) and tc.loginid!='".$uid."' and tc.id>300000,1,null)) as rcount, if(tc.ltype>1,tg.name,'Log') as name, if(tc.ltype>1,tg.tms,p.task_assigned) as tms from calmet_tasks_comments tc
						LEFT JOIN calmet_tasks p on(tc.task_id=p.id) 
						inner join calmet_tmgroup tg on (tc.ltype = tg.id)
						LEFT JOIN calmet_task_log_followup lf on(lf.tlid=tc.id and lf.uid='".$uid."' and lf.tid='".$qs->tid."')
						where tc.task_id= '".$qs->tid."' and if(tc.ltype=0,find_in_set('".$uid."',p.task_assigned),find_in_set('".$uid."',tg.tms))
						group by tc.ltype;";
					$ret = $this->swoole_mysql->query($tmg_sql);
					//var_dump(count($ret));
					//var_dump("------------------------");

			/*}else if(isset($qs) && $qs->m=="sno"){
					//var_dump($qs->sno);
					//var_dump($qs->id);
					$s="select * from calmet";
					//var_dump($s);

					$Sel_Com_Pros	= "update calmet.calmet_task_followup_dates SET msnoozedt =  now()+INTERVAL ".$qs->sno." MINUTE, snoozedt =  now()+INTERVAL ".$qs->sno." MINUTE where id =".$qs->id." and loginid=".$uid." ;";
					//$ret="select * from calmet_users;"
					//var_dump($Sel_Com_Pros);
					$ret = $this->swoole_mysql->query($Sel_Com_Pros);

				    //$ret = $this->swoole_mysql->query('CALL list(".$uid.",2,@output)');*/
				}else if($hpath[1]=="tlog"){
					//var_dump($qs);
					$tid=$qs->tid;
					if(isset($qs->dtype)){
						$dtype=$qs->dtype;	
					}else{
						$dtype=0;
					}
					$Sel_Com_Pros="select tlog,ltime,nlog from calmet.calmet_task_log_temp where tid= $tid and grpid=$dtype  and uid=".$uid.";";
					//var_dump("Temporty Log " . $Sel_Com_Pros);
					$ret = $this->swoole_mysql->query($Sel_Com_Pros);
					//var_dump($ret);

				}else if($hpath[1]=="gettmrlist"){
					if(!isset($qs->dtype)){
						$qs->dtype = 0;
					}
					//var_dump($qs);
					//$tmg_sql= "SELECT id,name,IF(id=".$qs->tmr.",1,0) as tmr, IF(".$uid." in (20,21,31,77),1,0) as tmr2 FROM calmet_users WHERE status = 1 order by 2";
					//$tmg_sql= "SELECT id,name,IF(id=".$qs->tmr.",1,0) as tmr, IF(id=".$uid." && id in (20,21,31,77),1,0) as tmr2 FROM calmet_users WHERE status = 1 
					//order by 2";
					//$tmg_sql= "SELECT id,name,IF(id=".$qs->tmr.",1,0) as tmr, IF(id in (20,21,31),1,0) as tmr2 FROM calmet_users WHERE status = 1 order by 2;";
					//var_dump($qs->tid."-----".$uid."-----".$qs->dtype);
					$gettmr = "Select team_member, task_assigned from calmet_tasks where id = '".$qs->tid."'";
					$ret1 = $this->swoole_mysql->query($gettmr);		
					//var_dump($gettmr);
					//var_dump($ret1);
					$notitms="";
					$imtms="";
					if($ret1){
						$tmr = $ret1[0]["team_member"];
						$assigned = $ret1[0]["task_assigned"];

					}
					
					if($qs->dtype==0){
						$getnewdata = "SELECT notitms,imtms FROM calmet.calmet_task_log_temp where tid='".$qs->tid."' and uid = '".$uid."' and grpid='".$qs->dtype."'";
						//var_dump("From log temp");
						$ret3 = $this->swoole_mysql->query($getnewdata);			
						//var_dump($ret3);
						if($ret3){
							if($ret3[0]["notitms"]!="" && $ret3[0]["imtms"]!=""){
								$notitms = $ret3[0]["notitms"];
								$imtms = $ret3[0]["imtms"];	
							}else if($ret3[0]["notitms"]!=""){
								$notitms = $ret3[0]["notitms"];
							}else if($ret3[0]["imtms"]!=""){
								$notitms = $ret3[0]["imtms"];
							}else{
								$getolddata = "SELECT notitms,imtms FROM calmet.calmet_task_followup_dates where task_id = '".$qs->tid."' and loginid = '".$uid."'";	
								$ret2 = $this->swoole_mysql->query($getolddata);		
								//var_dump("From followup dates");
								//var_dump($ret2);
								if($ret2){
									$notitms = $ret2[0]["notitms"];
									$imtms = $ret2[0]["imtms"];
								}
							}
						}else{
							$notitms = "";
							$imtms = "";
						}
					}else{
						$gettmgtms = "SELECT tms from calmet_tmgroup where id='".$qs->dtype."'";
						$rettmg = $this->swoole_mysql->query($gettmgtms);			
						$assigned = $rettmg[0]["tms"];
						$getnewdata = "SELECT notitms,imtms FROM calmet.calmet_task_log_temp where tid='".$qs->tid."' and uid = '".$uid."' and grpid='".$qs->dtype."'";
						$ret3 = $this->swoole_mysql->query($getnewdata);
						//var_dump("-----------TMG Qry----------------------");
						//var_dump($getnewdata);
						//var_dump($ret3);			
						if($ret3){
							$notitms = $ret3[0]["notitms"];
							$imtms = $ret3[0]["imtms"];
						}
					}

					if(!$assigned){
						 $assigned = 'Null';}
					if(!$notitms){ 
						$notitms = 'Null';
					}
					if(!$imtms){ $imtms = 'Null';}

					//var_dump($tmr."----".$assigned."----".$notitms."----".$imtms);
					
					
					$tmg_sql= "SELECT id,name,IF(id=".$uid.",1,0) as tmr, IF(id in (20,21,31),1,0) as tmr2,IF(id in ($assigned),1,0) as assigned,IF(id in ($notitms),1,0) as noti,
IF(id in ($imtms),1,0) as im FROM calmet_users WHERE status = 1 order by 2";
						$ret = $this->swoole_mysql->query($tmg_sql);		
				//var_dump($tmg_sql);
			

				}else if($hpath[1]=="tasknamelist"){
					$tmg_sql= "SELECT id,task_name as name from calmet_tasks";
					$ret = $this->swoole_mysql->query($tmg_sql);		
				//var_dump($ret);
			
			//Get CategoryName List for Dropdown
			}else if($hpath[1]=="categorynamelist"){
					$tmg_sql= "SELECT Name as name, Category as category, id, Type as type from categorylist Limit 1000";
					//var_dump($tmg_sql);
					$ret = $this->swoole_mysql->query($tmg_sql);		
			}else if($hpath[1]=="tstatus"){
				     if ($qs->stat==1){
					 $Sel_Com_Pros="update calmet.calmet_tasks set task_status=3 where id=".$qs->tid.";";
					}else{
						$Sel_Com_Pros="update calmet.calmet_tasks set task_status=1 where id=".$qs->tid.";";
					}
					// var_dump($Sel_Com_Pros);
					 //var_dump("*****************************************************");
						$ret = $this->swoole_mysql->query($Sel_Com_Pros);
				
			}else if($hpath[1]=="updatefollowupalarm"){
					$Sel_Com_Pros = "INSERT INTO calmet_task_followup_dates (task_id,loginid,alarm) VALUES (".$qs->tid.",".$uid.",".$qs->val.") ON DUPLICATE KEY UPDATE alarm=".$qs->val.";";
					$ret = $this->swoole_mysql->query($Sel_Com_Pros);		
			}else if($hpath[1]=="updatemeetingalarm"){
					//var_dump($qs);
					$tid = $qs->tid;
					$uid = $uid;
					$mlist = $qs->mlist;
					$deloldmlist = "DELETE FROM calmet.calmet_task_meetings where task_id=".$tid." and loginid=".$uid;
					var_dump($deloldmlist);
					$res = $this->swoole_mysql->query($deloldmlist);
					$meet = json_decode($mlist);
					for($i=0;$i<count($meet);$i++){
						if($meet[$i]->mid>0){
						$sql = "INSERT INTO calmet_task_meetings SET task_id=".$tid.", loginid=".$uid.", meetid=".$meet[$i]->mid.", meet_date='".$meet[$i]->msdate."', msnoozedt='".$meet[$i]->msdate."', alarm2=".$meet[$i]->alarm2.";";
						//var_dump($sql);
						$doc = $this->swoole_mysql->query($sql);
						}
						$sql = "Update calmet_task_followup_dates SET meetid=0,pyt=".$tpyt." where task_id=".$tid." and loginid = ".$uid;
						//var_dump($sql);
						$doc = $this->swoole_mysql->query($sql);
					}
					$ret = "Success";

					
			}else if($hpath[1]=="updateminitask"){

					if(($qs->val)==0){
						$mtdate="";
						$Sel_Com_Pros = "INSERT INTO calmet_task_log_followup (tid,tlid,uid,star,w1,mtdate) VALUES (".$qs->tid.",".$qs->lid.",".$uid.",".$qs->val.",now(),'') ON DUPLICATE KEY UPDATE star=".$qs->val.";";
						$sql="UPDATE calmet_task_log_followup SET mtdate='' WHERE tlid='".$qs->lid."' and uid=".$uid." and tid=".$qs->tid.";";
						var_dump($sql);
						$res = $this->swoole_mysql->query($sql);
					
					}else{
						$Sel_Com_Pros = "INSERT INTO calmet_task_log_followup (tid,tlid,uid,star,w1) VALUES (".$qs->tid.",".$qs->lid.",".$uid.",".$qs->val.",now()) ON DUPLICATE KEY UPDATE star=".$qs->val.";";
					}
					var_dump($Sel_Com_Pros);

				// $Sel_Com_Pros = "INSERT INTO calmet_task_log_followup (tid,tlid,uid,star,w1) VALUES (".$qs->tid.",".$qs->lid.",".$uid.",".$qs->val.",now()) ON DUPLICATE KEY UPDATE star=".$qs->val.";";
					$ret = $this->swoole_mysql->query($Sel_Com_Pros);		
			}else if($hpath[1]=="updatemtdate"){
				   var_dump($qs);
				   $Sel_Com_Pros = "update calmet.calmet_task_log_followup set mtdate='".$qs->mtdate."' where tid = ".$qs->tid." and tlid=".$qs->lid." and uid=".$uid.";";
				   var_dump($Sel_Com_Pros);
 			  	 $ret = $this->swoole_mysql->query($Sel_Com_Pros);		
			}else if($hpath[1]=="twlist"){
				$Sel_Com_Pros	= "CALL list232(".$uid.",'tw',' ',2, 0,@output);";
				$ret = $this->swoole_mysql->query($Sel_Com_Pros);
			}else if($hpath[1]=="gettasklog"){
				//var_dump($qs);
					
					if(isset($qs->ltype)){
						$ltype = $qs->ltype;
					}else{
						$ltype=0;
					}
					if($ltype==0){
						$ltype = "(tc.ltype = 0 or tc.ltype = 1) and find_in_set($uid,ct.task_assigned)";
					}else {
						$ltype = "tc.ltype = ". $qs->ltype;
					}
					/*$tlog_sql= "Select tc.*,  if(isnull(lf.readed) and tc.id>300000,1,0) as readed,if(lf.star=0 || isnull(lf.star),0,1) as star,
						if(DATE_FORMAT(tc.created_date,'%d-%m-%y')=DATE_FORMAT(now(),'%d-%m-%y'),DATE_FORMAT(tc.created_date,'Today %h:%i %p'),
	if(DATE_FORMAT(tc.created_date,'%d-%m-%y')=DATE_FORMAT(date_add(now(),interval -1 day),'%d-%m-%y'),DATE_FORMAT(tc.created_date,'Yesterday %h:%i %p'),
			if(DATE_FORMAT(tc.created_date,'%d-%m-%y')< DATE_FORMAT(date_add(now(),interval -1 day),'%d-%m-%y') && DATE_FORMAT(tc.created_date,'%d-%m-%y')> DATE_FORMAT(date_add(now(),interval -6 day),'%d-%m-%y'),DATE_FORMAT(tc.created_date,'%a %h:%i %p'),
            DATE_FORMAT(tc.created_date,'%a %e-%b-%y %l:%i %p')))) as dname, DATE_FORMAT(tc.created_date,'%d-%b-%y %h:%i %p') as ldate, cu.name,
							if(cu.id=".$uid.",0,1) as luid  from calmet_tasks_comments tc  left outer join calmet_users cu on tc.loginid = cu.id left outer join calmet_task_log_followup lf ON (lf.tlid = tc.id and lf.uid = ".$uid.")  where tc.task_id = '".$qs->tid."' and ". $ltype ." order by tc.created_date desc "; 
					
					/*$select_Qry ="select tc.id,tc.task_id,tc.ltype,tc.hl,tc.loginid,DATE_FORMAT(TC.created_date,'%a %e-%b-%y %l:%i %p') as created_date,tc.comments1,u.name,if(lf.star=0 || isnull(lf.star),0,1) as star,if(lf.readed=0 || isnull(lf.readed),0,1) as readed,if(tc.loginid = ". $loguserid.",0,1) as logtm from calmet_tasks_comments tc 
						LEFT JOIN ".Users." u ON(u.id=tc.loginid)  
						LEFT JOIN calmet_task_log_followup lf ON (lf.tlid = tc.id and lf.uid = ". $loguserid." ) 
						where ". $where ." and tc.task_id = $id  order by tc.id desc"; */
					//var_dump("tasklog  " .$tlog_sql);
					//var_dump("------------------------------------------------------------------");
					//var_dump($tlog_sql);              replace(replace(tc.comments1, '<','&lt;'),'>','&gt;') 
					if(isset($qs->mode) && $qs->mode=="top"){
					$tlog_sql= "Select tc.*, CONVERT(CAST(tc.comments1  as BINARY) USING utf8) as con_comment, if(isnull(lf.readed) and tc.id>300000,1,0) as readed,if(lf.star=0 || isnull(lf.star),0,1) as star,if(DATE(tc.created_date)>=DATE(date_add(now(),interval -1 day)),'true','false') as deldate,DATE_FORMAT(lf.mtdate,'%a %e-%b-%y') as mtdate,
						DATE_FORMAT(tc.created_date,'%a %e-%b-%y %l:%i %p') as dname, DATE_FORMAT(tc.created_date,'%d-%b-%y %h:%i %p') as ldate, cu.name,
							if(cu.id=".$uid.",0,1) as luid  from calmet_tasks_comments tc  left outer join calmet_users cu on tc.loginid = cu.id left outer join calmet_task_log_followup lf ON (lf.tlid = tc.id and lf.uid = ".$uid.")  left outer join calmet_tasks ct on ct.id = tc.task_id where tc.task_id = '".$qs->tid."' and tc.ltype=0 order by tc.created_date desc limit 10"; 
					}else{
						$tlog_sql= "Select tc.*, CONVERT(CAST(tc.comments1 as BINARY) USING utf8) as con_comment, if(isnull(lf.readed) and tc.id>300000,1,0) as readed,if(lf.star=0 || isnull(lf.star),0,1) as star,if(DATE(tc.created_date)>=DATE(date_add(now(),interval -1 day)),'true','false') as deldate,DATE_FORMAT(lf.mtdate,'%a %e-%b-%y') as mtdate,
						DATE_FORMAT(tc.created_date,'%a %e-%b-%y %l:%i %p') as dname, DATE_FORMAT(tc.created_date,'%d-%b-%y %h:%i %p') as ldate, cu.name,
							if(cu.id=".$uid.",0,1) as luid  from calmet_tasks_comments tc  left outer join calmet_users cu on tc.loginid = cu.id left outer join calmet_task_log_followup lf ON (lf.tlid = tc.id and lf.uid = ".$uid.")  left outer join calmet_tasks ct on ct.id = tc.task_id where tc.task_id = '".$qs->tid."' and ". $ltype ." order by tc.created_date desc"; 
					}
					$ret = $this->swoole_mysql->query($tlog_sql);		
					var_dump($tlog_sql);
					//var_dump("Log Count ".count($ret));
				/*	$i=0;
				  while ($i < count($ret))
	        {
	           //var_dump($ret[$i]["id"]);
	            $i++;
	        } */
					
				
			//Get Category list to fill category dropdown in task list page

			}else if($hpath[1]=="messagelogs"){
				//var_dump($qs);
				$Sel_Com_Pros	= "select DATE_FORMAT(cn.when1,'%a %d-%b %h:%i %p') as when1,DATE_FORMAT(cn.when1,'%Y%m%d %H:%i') as when2,ct.id,ct.task_name,cn.dtype, if(cn.not_tms=".$uid.",'in','out') as dir,if(cn.not_tms=".$uid.",cu.name,cu1.name) as tm,if(cn.not_tms=".$uid." && cn.ntype=0,'Notification from',if(cn.not_tms=".$uid." && cn.ntype=1,'Instant from',if(cn.tm=".$uid." && cn.ntype=1,'Instant to',if(cn.tm=".$uid." && cn.ntype=0,'Notification to','')))) as type, if(viewed>when1, DATE_FORMAT(cn.viewed,'%a %d-%b %h:%i %p'), 'Not Seen') as viewstat,task_id,'0' as selrow FROM calmet.calmet_notification cn  inner join calmet_users cu on cu.id =cn.tm  inner join calmet_users cu1 on cu1.id =cn.not_tms  inner join calmet_tasks ct on ct.id = cn.task_id  where (cn.not_tms = ".$uid." or cn.tm=".$uid.")  and DATE_FORMAT(cn.when1,'%Y-%m-%d') > DATE_FORMAT(date_add(now(),interval -10 day),'%Y-%m-%d') order by cn.when1 desc limit 100;";
				//var_dump($Sel_Com_Pros);
				$ret = $this->swoole_mysql->query($Sel_Com_Pros);	
				//var_dump($ret);
			//Get Category list to fill category dropdown in task list page
			}else if($hpath[1]=="mrmsglog"){
				var_dump(json_decode($qs->msglogs));

				var_dump($qs->msglogs[0]);
				$n=json_decode($qs->msglogs);
				$j=count($n);
				var_dump($j);
				//var_dump($n[1]->task_name);
				for ($k=0;$k<$j;$k++){
					$Sel_Com_Pros = "Update calmet.calmet_notification set shown=1,readed=0, dtype=".$n[$k]->dtype.",viewed=now() where task_id=".$n[$k]->task_id." and not_tms=".$uid." and dtype=".$n[$k]->dtype.";";
					var_dump($Sel_Com_Pros);
					$ret = $this->swoole_mysql->query($Sel_Com_Pros);

				}

				//$Sel_Com_Pros = "Update calmet.calmet_notification set shown=0,readed=0, dtype=".$qs->$.",viewed=now() where task_id=".$qs->tid." and not_tms=".$uid.";";
				
				$ret = $this->swoole_mysql->query($Sel_Com_Pros);	
			//var_dump($ret);
			//Get Category list to fill category dropdown in task list page
			}else if($hpath[1]=="readall"){

				var_dump($qs);
				$getunreadlogid = "Select group_concat('',tc.id) as logid from calmet_tasks_comments tc LEFT OUTER JOIN calmet_task_log_followup lf ON (lf.tlid =tc.id  and lf.uid = $uid and lf.tid='".$qs->tid."')
				where tc.task_id = '".$qs->tid."' and tc.ltype='".$qs->dtype."'and tc.loginid != $uid and (lf.uid = $uid or isnull(lf.uid)) and (lf.readed!=0 or isnull(lf.readed))";
				var_dump($getunreadlogid);
				$Sel_Com_Pros	= "am working readall";
				var_dump($Sel_Com_Pros);
				$doc = $this->swoole_mysql->query($getunreadlogid);
				var_dump($doc[0]["logid"]);
		
		if($doc[0]["logid"]!=""||$doc[0]["logid"]!=null){

			//if($rtype==1){
				$ureadlog = $doc[0]["logid"];
				$tid=$qs->tid;
				var_dump($tid);
				co::create(function() use($doc,$uid,$tid) {
					
				var_dump("unread log id in Coroutine ". $doc[0]["logid"]);
			    $db = new co\MySQL();
			    $server = array(
			    'host' => '192.168.5.203',
			    'user' => 'root',
			    'password' => 'caminven',
			    'database' => 'calmet',
			    'charset' => 'utf8',
			    'timeout' => 2,
			    'strict_type' => false,  /// / Open strict mode, the returned field will automatically be converted to a numeric type
	    		'fetch_mode' => true, 
	    		);

			    $ret1 = $db->connect($server);
			    //$stmt = $db->query('SELECT * FROM calmet_tasks');
			    //var_dump($stmt);
			    $logidarray=explode(",",$doc[0]["logid"]); 
					//var_dump($logidarray);
					foreach($logidarray as $logid)
					{
							$sql = "insert into calmet_task_log_followup (uid,tid,tlid,w1,readed) values ($uid,$tid,$logid,now(),0) ON DUPLICATE KEY UPDATE w1=now(),readed=0";	
							var_dump($sql);
							$res = $db->query($sql);
						
					}
			    //return $stmt;
				});
			}
				$ret =1;
				//$ret = $this->swoole_mysql->query($Sel_Com_Pros);	
				//var_dump($ret);
			//Get Category list to fill category dropdown in task list page
			}else if($hpath[1]=="cateserver"){
				//var_dump($qs);
				$Sel_Com_Pros	= "select co.name from calmet_tasks t 
					inner join categorylist co on t.sel_relateid = co.id and t.relate_to = co.Type
					inner join calmet_task_assigned ta on t.id = ta.task_id
					where t.task_status != 3 and t.created_by = $uid or find_in_set($uid,t.task_assigned ) or t.manager = $uid group by co.name order by 1";
				$ret = $this->swoole_mysql->query($Sel_Com_Pros);	
			
			//Get Meeting list to fill meeting dropdown in task list page
			
			}else if($hpath[1]=="meetingsserver"){
				//var_dump($qs);
				$Sel_Com_Pros	= "select cm.meet_code,cm.id, cm.meet_recu_stime from calmet_meeting cm inner join calmet_task_meetings tf on cm.id = tf.meetid left outer join meet_det md on md.id = tf.meetid left outer join calmet_users u on u.id = tf.loginid where 
					FIND_IN_SET($uid,cm.meet_tms) and meet_stat = 1
					group by cm.meet_code 
					order by cm.meet_order,cm.meet_code asc ";
				$ret = $this->swoole_mysql->query($Sel_Com_Pros);	
			
			//Get meeting page details
			}else if($hpath[1]="tdymeeting"){
				//var_dump($qs);
				$uid = $uid;
				if($uid==48 || $uid ==77){
					$whr = "";
				}else{
					$whr = "and FIND_IN_SET($uid, meet_tms)";	
				}
				
				
				$name = array();
				for($i=0;$i<8;$i++){
					
					//Get Today Meeting list
					if($i==0){
						$seltdymet = "select m.id, m.meet_code, m.meet_desc,DATE_FORMAT(date_add(now(),interval 1 day),'%Y-%m-%d') as sdate, m.meet_recu_days, m.meet_recu_period, CONCAT_WS('-',TIME_FORMAT(meet_recu_stime, '%l:%i %p'), TIME_FORMAT(meet_recu_etime, '%l:%i %p')) AS stime, GROUP_CONCAT(u.name ORDER BY u.name SEPARATOR ', ') AS TMS,m.meet_order FROM calmet_meeting m,     calmet_users u WHERE FIND_IN_SET(u.id, meet_tms)  $whr and FIND_IN_SET(left(DAYNAME(date_add(now(),interval 0 day)),2), m.meet_recu_days) and m.meet_stat = 1 GROUP BY m.id ORDER BY  m.meet_recu_stime,m.meet_order, m.meet_code,m.meet_recu_period,m.meet_recu_days , m.meet_recu_stime ASC, m.meet_code";
						$result = $this->swoole_mysql->query($seltdymet);	
						$name[$i] = array(
							'name'=> 'Today',
							'data'=> $result,
						);
						//$name[$i] = array($temp[$i] => $result,);
					
					//Get Tomorrow Meeting list
					}else if($i==1){
						$seltdymet = "select m.id, m.meet_code, m.meet_desc,DATE_FORMAT(date_add(now(),interval 2 day),'%Y-%m-%d') as sdate, m.meet_recu_days, m.meet_recu_period, CONCAT_WS('-',TIME_FORMAT(meet_recu_stime, '%l:%i %p'), TIME_FORMAT(meet_recu_etime, '%l:%i %p')) AS stime, GROUP_CONCAT(u.name ORDER BY u.name SEPARATOR ', ') AS TMS,m.meet_order FROM calmet_meeting m,     calmet_users u WHERE FIND_IN_SET(u.id, meet_tms) $whr and FIND_IN_SET(left(DAYNAME(date_add(now(),interval 1 day)),2), m.meet_recu_days) and m.meet_stat = 1 GROUP BY m.id ORDER BY  m.meet_recu_stime,m.meet_order, m.meet_code,m.meet_recu_period,m.meet_recu_days , m.meet_recu_stime ASC, m.meet_code";
						$result = $this->swoole_mysql->query($seltdymet);	
						$name[$i] = array('name'=>'Tomorrow','data' => $result, );
					
					//Get next 6 days Meeting list with Day name.
					}else{
						$dayname = date("l", strtotime("+ ".$i." day"));
						$seltdymet = "select m.id, m.meet_code, m.meet_desc, DATE_FORMAT(date_add(now(),interval $i+1 day),'%Y-%m-%d') as sdate,m.meet_recu_days, m.meet_recu_period, CONCAT_WS('-',TIME_FORMAT(meet_recu_stime, '%l:%i %p'), TIME_FORMAT(meet_recu_etime, '%l:%i %p')) AS stime, GROUP_CONCAT(u.name ORDER BY u.name SEPARATOR ', ') AS TMS,m.meet_order FROM calmet_meeting m,     calmet_users u WHERE FIND_IN_SET(u.id, meet_tms) $whr and FIND_IN_SET(left(DAYNAME(date_add(now(),interval $i day)),2), m.meet_recu_days) and m.meet_stat = 1 GROUP BY m.id ORDER BY  m.meet_recu_stime,m.meet_order, m.meet_code,m.meet_recu_period,m.meet_recu_days , m.meet_recu_stime ASC, m.meet_code";
						$result = $this->swoole_mysql->query($seltdymet);	
						$name[$i] = array('name' => $dayname, 'data' => $result, );
					}
					
				}
				$ret = $name;
			}else{
				$ret = array('Error' => "Api Action Not Found", );
			}
			$status = 202;
		}
		}else{
			var_dump("No uid");
			$ret= "Login Error";
			$data["token"] = "delete";
			$status = 400;
		}
		// End clock time in seconds 
		$end_time = microtime(true); 
		  
		// Calculate script execution time 
		$execution_time = ($end_time - $start_time); 
		if(!$ret){
			var_dump($ret);
			echo " Execution Error ". $hpath[1]."\n";
		}
		if($hpath[1]=="taskslist"){
			var_dump(json_encode($qs));
		}
		//echo " Execution time of ". $hpath[1] ." = ".$execution_time." sec\n"; 
		//var_dump("==============================================================================================================================");
		$ret = array(
			'output' => $ret,
			'token' => $data["token"]
		);
		$callback($status,$ret,$ctype);
			
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

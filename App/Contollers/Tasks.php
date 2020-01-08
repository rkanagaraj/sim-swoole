<?php 

class Tasks 
{
	private $db;
	private $server;
	private $swoole_mysql;
	//define("Task_file","calmet_task_file");

	
	Public function __construct(){

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
		$this->db = new Swoole\Coroutine\MySQL();
		$this->db->connect($this->server);

	}

	public function post($data,$callback){
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
		$luid=$a_data["loguserid"];
		$dtype=$a_data["dtype"];
		//var_dump($dtype);
		//var_dump($a_data["notes"]);
		//var_dump($resp["postdata"]);
		/*
			write a queery to get db values based on postdata values
		*/
		$sql = "select t.id,t.task_name,t.task_assigned,t.why,t.team_member,t.team_member,t.task_assigned,c.name, DATE_FORMAT(fu.dateof_followup,'%a, %e-%b-%y')  from calmet_tasks t left join categorylist c on  
			c.type= t.relate_to and c.id=t.sel_relateid left join calmet_task_followup_dates fu on t.id= fu.task_id and fu.loginid=$luid where t.id=$id";

		//var_dump($sql);
		/*
			db values stored in res array 
		*/
		$res = $this->db->query("$sql");

		if($res !== false) {
        // var_dump($res);
		/*
			Get the task id from db array
		*/
		
		$id=$res[0]["id"];

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
					/*
						Set the path for document uld in db
					*/
					$path = root_path.'/documents/'.$id;
					/*
						Check if the same path already exists
					*/
					if (!file_exists($path)) {
					/*
						if not create a new folder to uld the docuemnt
					*/
				    @mkdir(root_path.'/documents/'.$id, 0777);
				    //echo " dir created successfully";
					}
					//@mkdir(root_path.'/documents/'.$id, 0777);
					/*
						Check if the document uld un tmg
					*/
			if($dtype>1)
			{		
					/*
						Create a path to uld document in tmg gruop id
					*/
					$path = root_path.'/documents/'.$id.'/'.$dtype;	
					/*
						Check the path already exists
					*/
					if (!file_exists($path)) {
						/*
							if not create a dir to uld the document
						*/
				    	@mkdir(root_path.'/documents/'.$id.'/'.$dtype, 0777);
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
						$selfile="select id from calmet_task_file where task_id =".$id ." and doc_file = '". basename($filename)."'";
						$resfile = $this->db->query("$selfile");
						//var_dump($resfile[0]["creat_date"]);
						/*
							pathinfo() function returns an array that contains information about a path
						*/
						$pathinfo = pathinfo($uploadfile);
						//var_dump($pathinfo["dirname"]);
						/*
							Create a task_file_data to update the array
						*/
						$task_file_data =array("doc_file"=>''.basename($filename).'',"editmode"=>"0","open_by"=>"0","dtype"=>''.$dtype.'',"ud_date"=>''.$date.'');
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
						$sqlrev = "INSERT INTO calmet_task_file (task_id,doc_file,log_id,modi_by,modi_date,pre_file_id,ud_date,dtype) VALUES (".$id.",'".$file."_rev-1.".$ext."',".$luid.",".$luid.",now(),".$resfile[0]["id"].",now(),".$dtype.") ON DUPLICATE KEY UPDATE editmode=0, modi_by='".$luid."',modi_date=now(),ud_date=now();";
						//echo "<br> rev query =".$sqlrev;
				        $res = $this->db->query("$sqlrev");
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
					  	 	$sql1 = self::updateSQL("calmet_task_file", $task_file_data,$where);
							//var_dump($sql1);
							$doc = $this->db->query("$sql1");			
						}
					
						}
						else{
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
							$sql = self::insertSQL("calmet_task_file", $task_file_data);	
							echo $sql;
							$doc = $this->db->query("$sql");
						}
						}
			}else{		/*
							If the document uld in main log 
						*/
						$path = root_path.'/documents/'.$id;
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
							$selfile="select id,creat_date from calmet_task_file where task_id =".$id ." and doc_file = '". basename($filename)."'";
							$resfile = $this->db->query("$selfile");
							//var_dump($resfile[0]["creat_date"]);
							/*
								Select the path info for the uploaded file
							*/
							$pathinfo = pathinfo($uploadfile);
							//var_dump($pathinfo["dirname"]);
							$task_file_data =array("doc_file"=>''.basename($filename).'',"editmode"=>"0","open_by"=>"0","ud_date"=>''.$date.'');
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
							var_dump("uploaded file :".$uploadfile);
							var_dump("New File Name :". $newname);
							if(rename($uploadfile,$newname)){
								var_dump("File Renamed");
							}else{
								var_dump("file Rename Failed");
							}
							/*
								get the tmpname of the file 
							*/
							$tmpname = $data["files"]["doc_file"][$i]["tmp_name"];
							/*
								Move the Newfile into previous file id
							*/
						  	if(move_uploaded_file($tmpname,$uploadfile)){
						  		$sql1 = self::updateSQL(calmet_task_file, $task_file_data,$where);
						  		//echo "<br><br>query = ".$sql1;
								$doc = $this->db->query("$sql1");
						  	}	
						  	/*
						  		Insert new row for previous file w/ new name rev-1 with the original file id
						  	*/
							$sqlrev = "INSERT INTO calmet_task_file (task_id,doc_file,log_id,modi_by,modi_date,pre_file_id,ud_date) VALUES (".$id.",'".$file."_rev-1.".$ext."',".$luid.",".$luid.",now(),".$resfile[0]["id"].",now()) ON DUPLICATE KEY UPDATE editmode=0, modi_by='".$luid."',modi_date=now(),ud_date=now();";
							echo "<br> rev query =".$sqlrev;
				           $res = $this->db->query("$sqlrev");
								
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
							$sql = self::insertSQL("calmet_task_file", $task_file_data);	
							echo $sql;
							$doc = $this->db->query("$sql");
						}
						
 
					}
				}
		
			}
	      
		}
		//var_dump($data);
		
		
		$callback(200,$resp);
		
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
       // echo "<br><br>Update query = ".$sql;
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
       // echo " <br>query value = ".$sql;
		return trim($sql);
		
		
    }
	function gettaskdata($id,$callback){
		$this->db->connect($this->server, function ($db, $result) use ($id,$callback) {
			$result2 = $this->db->query("select * from calmet_tasks where id=$id", function (Swoole\MySQL $db, $result) use ($callback){
				$callback(201,$result);
		    	//var_dump($callback);
				$this->db->close();
		    });
		});
	}
	function parse_raw_http_request($data, array &$a_data)
	{
  	// read incoming data
  	$input = $data["payload"];
  
  	$header=json_decode($data["headers"],true);

  	//var_dump($header["content-type"]);

 	 // grab multipart boundary from content type header
 	 preg_match('/boundary=(.*)$/', $header['content-type'], $matches);
 	 //var_dump($matches);
 	 $boundary = $matches[1];

 	 //var_dump($boundary);
  	// split content by boundary and get rid of last -- element
 	 $a_blocks = preg_split("/-+$boundary/", $input);
 	 //var_dump($a_blocks);
  	array_pop($a_blocks);

  	// loop data blocks
  	foreach ($a_blocks as $id => $block)
  	{
    if (empty($block))
      continue;

    // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char
  	//var_dump($block);
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
    	//var_dump($a_data);

    		    	
  	}        
	}

	public function get($data,callable $callback){
	
		/*$this->swoole_mysql->connect($this->server);
		$res = $this->swoole_mysql->query('CALL list(48,2,@output)');
		if($res === false) {
		    return;
		}else{
			$callback(201,$res);
		
		} */
		//CALL list(48,2,@output)
		$this->db->connect($this->server, function ($db, $result) use ($callback) {
			$result2 = $this->db->query("CALL list(21,2,@output);", function (Swoole\MySQL $db, $result) use ($callback){
		    	$callback(201,$result);
		    	//var_dump($callback);
				$this->db->close();
		    });
		}); 
	
		
		
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
}

/*if($_GET['mode']=='Add_Notification1')
{
	if(trim($_GET['notes']) == ""){
		exit;
		
	}
	$tms = $_GET['tms'];
	$imtms = $_GET['imtms'];
	$ntms = $_GET['ntms'];
	$task_id = $_GET['task_id'];
	$id = $_GET['id'];
	$logid = $_GET['logid'];
	$description = $_GET['notes'];
	if($_GET['ntype']){ $ntype = $_GET['ntype']; } else { $ntype = 0;}
	$url = $_GET['url'];
	if($_GET['hmdate_followup']) {
		$dateof_followup=db_format_date(''.$_GET['hmdate_followup'].''); 
	}

	$fdatetime= date('Y-m-d H:i:s',strtotime($dateof_followuP." ".$_GET['followup_time']));
	$created_date = date('Y-m-d');
	if($_SERVER['SERVER_ADDR'] == "192.168.0.98"){
		$time_now = mktime(date('H'),date('i'),date('s'));
	}else{
		//$time_now = mktime(date('H')+5,date('i')+30,date('s'));
		$time_now = mktime(date('H'),date('i'),date('s'));
	}
	$created_date = date('Y-m-d H:i:s',$time_now);
	$split = explode(', ', $tms);
	//$sel_log_qry="select task_assigned from calmet_tasks where id  = ".$task_id;
			//echo $sel_rel_qry;
	//$res_rel_qry = $connect->query($sel_log_qry);
	//list($tms)=  $res_rel_qry->fetch(PDO::FETCH_BOTH);
	//echo $tms;
	$sql = "INSERT INTO calmet_task_log_followup (tid,tlid,uid,readed,w2) VALUES (".$task_id.",".$logid.",".$loguserid.",0,now()) ON DUPLICATE KEY UPDATE readed=0";	
	$exe_obj->exquteQuery($sql);
	$variableAry=explode(",",$tms); //you have array now
	/*foreach($variableAry as $var)
	{
		if($var!=$loguserid){
		if($ntms==0){           //if there is no notification tms selected send notification to all tms.
		$sql = "INSERT INTO calmet_notification (not_tms,tm,task_id,description,url,when1,ntype,shown,readed,viewed) VALUES (".$var.",".$loguserid.",".$task_id.",'".$description."','".$url."','".$created_date."',".$ntype.",0,0,'') ON DUPLICATE KEY UPDATE description='".$description."',when1='".$created_date."',ntype=".$ntype.",shown=0,readed=0,viewed=''";	
		//$task_comments = array("not_tms"=>''.$var.'',"tm"=>''.$loguserid.'', "description"=>''.$description.'', "url"=>''.$url.'',
				//"when1"=>''.$created_date.'');
		//$sql = $build_sql->insertSQL(calmet_notification,$task_comments);
		//echo "\ntms".$sql;
		echo "<br>";
		//$exe_obj->exquteQuery($sql);
		//$last_insertid_for_log=$exe_obj->sql_insert_id(); 
		}
		$sql = "INSERT INTO calmet_task_log_followup (tid,tlid,uid,readed,w2) VALUES (".$task_id.",".$logid.",".$var.",1,now()) ON DUPLICATE KEY UPDATE readed=1";	
		//$exe_obj->exquteQuery($sql);
		
		}
	}*/
	
/* 	$variableAry2=explode(",",$ntms);
	$variableAry3=explode(",",$imtms);
	$variableAry4=array_diff($variableAry2,$variableAry3);
	echo "ntype".$ntype;
	if($ntype==1){
	
	echo 	$variableAry2;
	foreach($variableAry3 as $var3)
	{
		if($var3!=$loguserid){
		$sql = "INSERT INTO calmet_notification (not_tms,tm,task_id,description,url,when1,ntype,shown,readed,viewed) VALUES (".$var3.",".$loguserid.",".$task_id.",'".$description."','".$url."','".$created_date."',1,0,0,'') ON DUPLICATE KEY UPDATE description='".$description."',when1='".$created_date."',ntype=1,shown=0,readed=0,viewed=''";	
		//$task_comments = array("not_tms"=>''.$var.'',"tm"=>''.$loguserid.'', "description"=>''.$description.'', "url"=>''.$url.'',
				//"when1"=>''.$created_date.'');
		//$sql = $build_sql->insertSQL(calmet_notification,$task_comments);
		echo "\error\n".$sql;
		//echo "<br>";
		$exe_obj->exquteQuery($sql);
		$last_insertid_for_log=$exe_obj->sql_insert_id(); 
		//$sql = "INSERT INTO calmet_task_log_followup (tid,tlid,uid,readed,w2) VALUES (".$task_id.",".$logid.",".$var.",1,now()) ON DUPLICATE KEY //UPDATE readed=1";	
		//$exe_obj->exquteQuery($sql);
		}
	}
	}
	
	if($ntms){
	//if($loguserid==48||$loguserid==20){
		
	//if($loguserid==48||$loguserid==20||$loguserid==43||$loguserid==57||$loguserid==17||$loguserid==54||$loguserid==34||$loguserid==55||$loguserid==70){
	//$variableAry2=explode(",",$ntms);
	foreach($variableAry4 as $var2)
	{
		if($var2!=$loguserid){
		$sql = "INSERT INTO calmet_notification (not_tms,tm,task_id,description,url,when1,ntype,shown,readed,viewed) VALUES (".$var2.",".$loguserid.",".$task_id.",'".$description."','".$url."','".$created_date."',0,0,0,'') ON DUPLICATE KEY UPDATE description='".$description."',when1='".$created_date."',ntype=0,shown=0,readed=0,viewed=''";	
		//$task_comments = array("not_tms"=>''.$var.'',"tm"=>''.$loguserid.'', "description"=>''.$description.'', "url"=>''.$url.'',
				//"when1"=>''.$created_date.'');
		//$sql = $build_sql->insertSQL(calmet_notification,$task_comments);
		echo "\nnttms..".$var2."\n".$sql;
		echo "<br>";
		$exe_obj->exquteQuery($sql);
		$last_insertid_for_log=$exe_obj->sql_insert_id(); 
		//$sql = "INSERT INTO calmet_task_log_followup (tid,tlid,uid,readed,w2) VALUES (".$task_id.",".$logid.",".$var.",1,now()) ON DUPLICATE KEY //UPDATE readed=1";	
		//$exe_obj->exquteQuery($sql);
		}
	}
	
	/*}else{
	$variableAry2=explode(",",$ntms);
	foreach($variableAry2 as $var2)
	{
		if($var2!=$loguserid){
		$sql = "INSERT INTO calmet_notification (not_tms,tm,task_id,description,url,when1,ntype,shown,readed,viewed) VALUES (".$var2.",".$loguserid.",".$task_id.",'".$description."','".$url."','".$created_date."',1,0,0,'') ON DUPLICATE KEY UPDATE description='".$description."',when1='".$created_date."',ntype=1,shown=0,readed=0,viewed=''";	
		//$task_comments = array("not_tms"=>''.$var.'',"tm"=>''.$loguserid.'', "description"=>''.$description.'', "url"=>''.$url.'',
				//"when1"=>''.$created_date.'');
		//$sql = $build_sql->insertSQL(calmet_notification,$task_comments);
		//echo $sql;
		//echo "<br>";
		$exe_obj->exquteQuery($sql);
		$last_insertid_for_log=$exe_obj->sql_insert_id(); 
		//$sql = "INSERT INTO calmet_task_log_followup (tid,tlid,uid,readed,w2) VALUES (".$task_id.",".$logid.",".$var.",1,now()) ON DUPLICATE KEY //UPDATE readed=1";	
		//$exe_obj->exquteQuery($sql);
		}
	}	
	
		
	}*/
	//}
	/*echo "ntype".$ntype;
	/*if($ntype==1){
	$variableAry3=explode(",",$imtms);
	foreach($variableAry3 as $var3)
	{
		if($var3!=$loguserid){
		$sql = "INSERT INTO calmet_notification (not_tms,tm,task_id,description,url,when1,ntype,shown,readed,viewed) VALUES (".$var3.",".$loguserid.",".$task_id.",'".$description."','".$url."','".$created_date."',1,0,0,'') ON DUPLICATE KEY UPDATE description='".$description."',when1='".$created_date."',ntype=1,shown=0,readed=0,viewed=''";	
		//$task_comments = array("not_tms"=>''.$var.'',"tm"=>''.$loguserid.'', "description"=>''.$description.'', "url"=>''.$url.'',
				//"when1"=>''.$created_date.'');
		//$sql = $build_sql->insertSQL(calmet_notification,$task_comments);
		echo "\error\n".$sql;
		//echo "<br>";
		$exe_obj->exquteQuery($sql);
		$last_insertid_for_log=$exe_obj->sql_insert_id(); 
		//$sql = "INSERT INTO calmet_task_log_followup (tid,tlid,uid,readed,w2) VALUES (".$task_id.",".$logid.",".$var.",1,now()) ON DUPLICATE KEY //UPDATE readed=1";	
		//$exe_obj->exquteQuery($sql);
		}
	}
	} */
	
	//$task_comments = array("not_tms"=>''.$tms.'',"tm"=>''.$loguserid.'', "description"=>''.$description.'', "url"=>''.$url.'',
				//"when1"=>''.$created_date.'');

	//$sql = $build_sql->insertSQL(calmet_notification,$task_comments);
	//$_SESSION['flash_msg'] = $sql;
	//$exe_obj->exquteQuery($sql);
	//$last_insertid_for_log=$exe_obj->sql_insert_id(); 
	
	//echo $sql;*/

//	exit;	
//}
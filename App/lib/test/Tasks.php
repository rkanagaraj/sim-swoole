<?php
//namespace node\lib\test;

class Tasks
{
	private $db;
	private $server;
	private $swoole_mysql;
	
	Public function __construct(){

		$this->server = array(
		    'host' => '127.0.0.1',
		    'user' => 'root',
		    'password' => 'root',
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
		//var_dump(json_decode($data["payload"]));
		return "Hai";
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
			$result2 = $this->db->query("CALL list(48,2,@output);", function (Swoole\MySQL $db, $result) use ($callback){
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

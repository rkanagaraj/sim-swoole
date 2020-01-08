<?php
include_once(Basedir.'/App/Models/UsersModel.php');
require_once Basedir.'/App/Views/view.php';

class temp 
{
	private $db;
	private $server;
	private $swoole_mysql;
	public $model;
	
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
		$this->db =  new swoole_mysql;
		$this->swoole_mysql = new Swoole\Coroutine\MySQL();

	}

	

	public function post($data,$callback){
		//var_dump(json_decode($data["payload"]));
		return "Hai";
	}

	public static function render($view, $args = [])
    {
    	//return "Hello";
        extract($args, EXTR_SKIP);
        $file = dirname(__DIR__) . "/Views/$view";  // relative to Core directory
        if (is_readable($file)) {
            require $file;
        } else {
            throw new \Exception("$file not found");
        }
    }

	public function get($data,callable $callback){
		/*$classfile = Basedir.'/App/Views/view.php';
		var_dump("res =".$classfile);
		include_once $classfile;
		$view = new View();
		$result = $view->render('list2.php');
		var_dump($result);	
		
		$callback(200); //array('name'=>'sample handler')); */
		
		//include_once(Basedir.'/App/Models/Model.php');
		//$this->model = new Model();
		//$books = $this->model->getBookList();
		//$result = require Basedir.'/App/Views/booklist.php';
		
		$this->model =  new UsersModel();
		$books = $this->model->get();
		var_dump($books);
		

		$view = new view();
		$GLOBALS['site_title'] = "Testing site";
		$GLOBALS['hello_world'] = "Hello World..!!!";
		//var_dump($GLOBALS);
		$res["names"] =array(
			"items" => array("item 1","item 2","item 3")
			);
		//$this->swoole_mysql->connect($this->server);

		//$res["items"] = $this->swoole_mysql->query('CALL list(48,2,@output)');
		//$this->swoole_mysql->close();
		$ret = $view->load("list2",$res);
		var_dump("Result :" .$ret);

		//$result = self::render('booklist.php');
		$callback(202,$ret,'html');
		//var_dump($result);
		/*$this->swoole_mysql->connect($this->server);
		$res = $this->swoole_mysql->query('CALL list(48,2,@output)');
		if($res === false) {
		    return;
		}else{
			$callback(201,"<html><title>Hello</title><body><h1>hello world</h1></body></html>",'html');
		
		} 
				//CALL list(48,2,@output)
		/*$this->db->connect($this->server, function ($db, $result) use ($callback) {
			$result2 = $this->db->query("CALL list(48,2,@output);", function (Swoole\MySQL $db, $result) use ($callback){
		    	$callback(201,$result);
		    	//var_dump($callback);
				$this->db->close();
		    });
		}); */
	
		
		
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

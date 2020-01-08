<?php
include_once(Basedir.'/App/Models/UsersModel.php');
require_once Basedir.'/App/Views/view.php';
require 'RenderService.php';
$GLOBALS['site_title'] = "Testing site";
$GLOBALS['hello_world'] = "Hello World..!!!";

class Users 
{
	private $db;
	private $server;
	private $swoole_mysql;
	public $model;
	public $view;


	
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
		$this->view = new View();
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
		/*$v8 = new V8Js();
		$JS = <<< EOT
		len = print('Hello'+'   '+ 'World!');
        len;
EOT;
        $ret = $v8->executeString($JS);
        */

		$qs = json_decode($data["queryStringObject"]);
	//	var_dump($qs);
	//	$id = $qs->id;
		//var_dump($id);
	//	$ret ="";
	//	$this->model =  new UsersModel();
	//	$books["items"] = $this->model->get($id);
		//$callback(200,$books,'html');
		//$this->view = new View();
		//$ret = $this->view->load("template",$books);
		//var_dump("Result :" .$ret);
		//$ret = $this->view->vue();
		//$this->view = new RenderService(Basedir);
		//$ret = $this->view->render(Basedir);
		//$callback(202,$ret,'text');
		$this->view = new View();
		$ret = $this->view->vuetest();
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
}

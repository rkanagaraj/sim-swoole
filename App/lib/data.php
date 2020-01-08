<?php
/*
*  lib for storing and editing data
*
*/

//Dependices

use Swoole\Async;


class data
{
	public $dir;
	public $file;
	public $data;	
	public $basedir;
	private $conn;

	public $database = array(
    'host'=>'localhost',
    'user'=>'root',
    'password'=>'caminven',
    'port'=>3306,
    'database'=>'calmet',
    'charset'=>'utf8'
	);
	
	public function __construct(){
		/*swoole_async_set(array(
				'thread_num' => 4,
    			'aio_mode' => SWOOLE_AIO_LINUX,
    			'socket_buffer_size' => 128 * 1024 *1024,
    			'socket_dontwait'=>true,
			)); */
        $this->conn = mysqli_connect('localhost','root','caminven','calmet') or die ('Connect mysql failed ~~'.mysqli_connect_error());

		$config = require 'config.php';
		$this->basedir = $config['BASEDIR']. '/.data/';
		$data = "";
	}

	public function create($dir,$file,$data) {
		//Check file already exists
		$result = swoole_async_read($this->basedir .$dir.'/'.$file.'.json', function($filename, $content) : bool{
			return $filename;
		});
		if(!$result){   //if file not already 
			swoole_async_writefile($this->basedir .$dir.'/'.$file.'.json', $data, function($write_file) {
				echo "file: $write_file\n";
				   //swoole_event_exit();
			}); 
		}else{
			var_dump('File Already exists');
		}
	}

	public function read($dir,$file) {

		
				
        
		/*$file = $this->basedir .$dir.'/'.$file.'.json';
		if (file_exists($file)) {
			$this->data = file_get_contents($file, true);
		}else{
			var_dump('File Not Found');

		} */
		/*$data = '';
		$handle = new \Swoole\MySQL;
		$handle->connect([
		    'host' => '127.0.0.1',
		    'user' => 'root',
		    'port' => '3306',
		    'password' => 'caminven',
		    'database' => 'calmet',
		], function ($db, $r) use ($data) {
		    if ($r === false) {
		        var_dump($db->connect_errno, $db->connect_error);
		        die;
		    }
		    $sql = 'select * from calmet_tasks limit 100';
		    $db->query($sql, function (\Swoole\MySQL $_db, $_rows) use ($sql,$data) {
		        
		           $data="Hai"; // file_put_contents('debug.log', json_encode($_rows), FILE_APPEND);
		        
		    });
		});
		var_dump($data); */
		/*$db = new Swoole\MySQL;
		$server = array(
		    'host' => '127.0.0.1',
		    'user' => 'root',
		    'password' => 'caminven',
		    'database' => 'calmet',
		);

		$db->connect($server, function ($db, $result) {

		    static $data2;
		    $db->query("select * from calmet_tasks limit 10", function (Swoole\MySQL $db, $result){
		    	//global $data2;
		    	$data2 = $result;

				//$this->data = $result;
				//$callback=$result;
				$db->close();
		    });
			var_dump($data2);    

		});  */
		
		
		/*//global $data;
		$data ="Hello";

		$result = swoole_async_readfile($this->basedir .$dir.'/'.$file.'.json', function($filename, $content) : bool{
			return  $filename ? true : flase;
		});
		//var_dump($result);
		if($result){
			swoole_async_readfile($this->basedir .$dir.'/'.$file.'.json', function($filename,$content)
			{
				global $data;
				$data = "file: $filename\ncontent-length: ".strlen($content)."\nContent:\n".$content;
				//return "Hello how are you";
				//var_dump($this->data);
				$data = $content;
				//return  $content;
			}); 
		}else{
			//var_dump('File doesnot exists');
			return  "File does not exists";


		}
		//var_dump($data);
		var_dump($data); */
	}

	public function update($dir,$file,$data){
		$data = json_encode($data);
		$file = $this->basedir .$dir.'/'.$file.'.json';
		$myfile = fopen($file, "w") or die("Unable to open file!");
		fwrite($myfile, $data);
		fclose($myfile);
	}

	public function delete($dir,$file){
		$file = $this->basedir .$dir.'/'.$file.'.json';
		if(!unlink($file)){
			echo "error delete";
		}else{
			echo "delete sucessed";
		}
	}

}
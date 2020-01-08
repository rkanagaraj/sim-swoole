<?php
class UsersModel extends DB {

  public function get($id){
    //$result = $this->select("SELECT * FROM  calmet_users");
    //var_dump($result);
    $servername = "localhost";
		$username = "root";
		$password = "root";
		$dbname = "calmet";

		try {
		    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
		    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		    $stmt = $conn->prepare("CALL list($id,2,@output)");
		    $stmt->execute();

		    // set the resulting array to associative
		    $result = $stmt->fetchAll(PDO::FETCH_ASSOC); //(PDO::FETCH_ASSOC);
		    //var_dump($result);
		    //$callback(200,json_encode($result));;
		    return $result;
		    /*foreach(new TableRows(new RecursiveArrayIterator($stmt->fetchAll())) as $k=>$v) {
		        echo $v;
		    } */
		}
		catch(PDOException $e) {
		    echo "Error: " . $e->getMessage();
		}
		$conn = null;
  }

  

}


?>

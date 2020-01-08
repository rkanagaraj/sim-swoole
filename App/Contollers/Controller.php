<?php
include_once(Basedir.'/App/Models/Model.php');

class Controller {
	public $model;
	
	public function __construct()  
    {  
        $this->model = new Model();

    } 
	
	public function invoke()
	{
		if (!isset($_GET['book']))
		{
			// no special book is requested, we'll show a list of all available books
			$books = $this->model->getBookList();
			$result = require Basedir.'/App/Views/booklist.php';
			var_dump($result);
		}
		else
		{
			// show the requested book
			$book = $this->model->getBook($_GET['book']);
			include Basedir.'/App/Views/viewbook.php';
		}
	}
}

?>
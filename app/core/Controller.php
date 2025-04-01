<?php
	// Create a class
	class Controller{
		// PHP Constructor
		public function __construct(){
			echo "Object is created.";
		}

		/*
		// PHP Destructor
		public function __destruct(){
			echo "Script is stopped or exited.";
		}
		*/
		public function display($view, $data=[]){
			require_once "../app/view/".$view.".php";
		}
		// core logic model meethod
		public function logic($model){
			require_once "../app/model/".$model.".php";
			$obj_model = new $model;
			return $obj_model;
		}
	}
?>
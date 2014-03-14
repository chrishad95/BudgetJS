<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

session_start();

require_once './idiorm.php';
require_once '../config/database.php';

ORM::configure("mysql:host=" . $db_host . ";dbname=" . $db_name . ";");
ORM::configure('username', $db_username);
ORM::configure('password', $db_password);

$categories = array();
$username = "";

if (isset($_SESSION['username'])) {
	$username = $_SESSION['username'];

	if  ( $_SERVER['REQUEST_METHOD']  == "GET" ) {
		error_log("Got a GET for categories", 0);
	
		if(isset($_GET['id']) && !empty($_GET['id']) ) {
			// specific category
			$categories = ORM::for_table('categories')->where('owner', $username)->where('id', $_GET['id'])->find_array();
	
		} elseif (isset($_GET['id']) && !empty($_GET['id']) && isset($_GET['action']) && !empty($_GET['action']) && $_GET['action'] == "delete" ) {
			$category = ORM::for_table('categories')->where('owner', $username)->find_one($_GET['id']);
			$category->delete();
	
		} else {
			//$categories = ORM::for_table('categories')->find_array(); 

			$count_categories = ORM::for_table('categories')->where('owner', $username)->where('name', 'Unassigned')->count();
			if ($count_categories == 0) {
				// create the Unassigned category
				$category = ORM::for_table('categories')->create();
				$category->name = "Unassigned";
				$category->owner = $username;
				$category->created_at = date("Y-m-d H:i:s");
				$category->updated_at = $category->created_at;
				$category->updated_by = 'System';
				$category->description = 'System created category for unassigned transactions.';
				$category->save();
			}
			$categories = ORM::for_table('categories')->where('owner', $username)->find_array(); 
			
		}
	
	} else  {
	
		error_log("Got a PUT for categories", 0);
		$requestData = json_decode(file_get_contents('php://input'));
	
		if (property_exists($requestData->data, "id")) {
			// got an id, this is an update
			// to an existing item
			error_log("Update a category", 0);
	
			$item = ORM::for_table('categories')->find_one($requestData->data->id);
	
			$item->name = $requestData->data->name;
	
			// need to write code to update updated_at, updated_by
			$item->updated_at = date("Y-m-d H:i:s");
			$item->updated_by = $username;
	
			$item->save();
			// return the new category in an array
			$categories = ORM::for_table('categories')->where('id', $item->id)->find_array();
	
		} else {
			error_log("CREATE a category", 0);
			// check to see if there is an account id
			// validate the account_id
			if (property_exists($requestData->data, "name")) {
				$category_count = ORM::for_table('categories')->where('name' , $requestData->data->name)->count();
				if ($category_count == 0) {
	
					// create the category
					$category = ORM::for_table('categories')->create();
	
					$category->name = $requestData->data->name;
	
					// need to write code to update updated_at, updated_by
					$category->updated_at = date("Y-m-d H:i:s");
					$category->created_at = $category->updated_at;
					$category->updated_by = $username;
					$category->owner = $username;
	
					$category->save();
	
					// return the new category in an array
					$categories = ORM::for_table('categories')->where('id', $category->id())->find_array();
					
				}
			} else {
	
			}
		}
	}
} else {
	error_log("Not logged in", 0);

}
echo json_encode($categories);



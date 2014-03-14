<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

session_start();

require_once './idiorm.php';
require_once '../config/database.php';

ORM::configure("mysql:host=" . $db_host . ";dbname=" . $db_name . ";");
ORM::configure('username', $db_username);
ORM::configure('password', $db_password);

$accounts = array();
$username = "";

if (isset($_SESSION['username'])) {
	$username = $_SESSION['username'];


	if ( $_SERVER['REQUEST_METHOD']  == "PUT" ) {
	
		$requestData = json_decode(file_get_contents('php://input'));
	
		if (property_exists($requestData->data, "id")) {
			$account_count = ORM::for_table('accounts')->where('id' , $requestData->data->id)->count();
			if ($account_count > 0) {
				// this is an update to an existing item
				$item = ORM::for_table('shopping_accounts')->find_one($requestData->data->id);
				$item->name = $requestData->data->name;
				$item->updated_at = date("Y-m-d H:i:s");
				$item->save();
	
			}
		} else {
			// create the account
			$account = ORM::for_table('accounts')->create();
			$account->name = $requestData->data->name;
			$account->owner = $username;
			$account->created_at = date("Y-m-d H:i:s");
			$account->updated_at = $account->created_at;
			$account->save();
		}

		$accounts = ORM::for_table('accounts')->where('owner', $username)->find_array(); 
	}else{  
	
		if(isset($_GET['id']) && !empty($_GET['id']) ) {
			$accounts = ORM::for_table('accounts')->where('owner', $username)->where('id', $_GET['id'])->find_array();
	
		} elseif (isset($_GET['id']) && !empty($_GET['id']) && isset($_GET['action']) && !empty($_GET['action']) && $_GET['action'] == "delete" ) {
			$account = ORM::for_table('accounts')->where('owner', $username)->find_one($_GET['id']);
			$account->delete();
		} else {
			$accounts = ORM::for_table('accounts')->where('owner', $username)->find_array(); 
		}
	}
}
echo json_encode($accounts);


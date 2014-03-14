<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

session_start();

require_once './idiorm.php';
require_once '../config/database.php';

ORM::configure("mysql:host=" . $db_host . ";dbname=" . $db_name . ";");
ORM::configure('username', $db_username);
ORM::configure('password', $db_password);

$auth = array();

if ( $_SERVER['REQUEST_METHOD']  == "GET" ) {
	// a get request if there is a
	// username in the session then return that
	
	if(isset($_SESSION['username'])) {
		$auth = array(
			'username' => $_SESSION['username'],
			'desciption' => 'Tall dark and handsome.',
			'last_seen' => date("Y-m-d H:i:s")
		);
	}
} else {


	$requestData = json_decode(file_get_contents('php://input'));
	error_log('requestData->data: ' . json_encode($requestData->data), 0);

	if (property_exists($requestData->data, "username") && property_exists($requestData->data, "password")) {
		if (property_exists($requestData->data, "register") && 
			property_exists($requestData->data, "email_address") ) {

				$count_users = ORM::for_table('users')
					->where_raw('(username = ? or email_address = ?)', array($requestData->data->username, $requestData->data->email_address))->count();
				if ($count_users == 0) {
					$user = ORM::for_table('users')->create();
					$user->username = $requestData->data->username;
					$user->email_address = $requestData->data->email_address;
					$user->password = md5($requestData->data->password);
					$user->created_at = date("Y-m-d H:i:s");
					$user->updated_at = $user->created_at;
					$user->last_login = $user->created_at;
					$user->save();

					$_SESSION['username'] = $requestData->data->username;
					$auth = array(
						'username' => $_SESSION['username'],
						'desciption' => 'Tall dark and handsome.',
						'last_login' => $user->last_login
					);

				}

			
		} else {

			$count_users = ORM::for_table('users')->where('username', $requestData->data->username)->where('password', md5($requestData->data->password))->count();
			if ($count_users > 0) {
				$user = ORM::for_table('users')->where('username', $requestData->data->username)->where('password', md5($requestData->data->password))->find_one();
				$user->last_login = date("Y-m-d H:i:s");
				$user->save();
				
				$_SESSION['username'] = $requestData->data->username;
				$auth = array(
					'username' => $_SESSION['username'],
					'desciption' => 'Tall dark and handsome.',
					'last_login' => $user->last_login
				);
			}
		}
	}
}

echo json_encode($auth);



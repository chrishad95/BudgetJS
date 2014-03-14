<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

session_start();

require_once './idiorm.php';
require_once '../config/database.php';

ORM::configure("mysql:host=" . $db_host . ";dbname=" . $db_name . ";");
ORM::configure('username', $db_username);
ORM::configure('password', $db_password);

$transactions = array();
$username = "";

if (isset($_SESSION['username'])) {
	$username = $_SESSION['username'];

	if  ( $_SERVER['REQUEST_METHOD']  == "GET" ) {
	
		if(isset($_GET['id']) && !empty($_GET['id']) ) {
			// specific transaction
			$transactions = ORM::for_table('transactions')->where('id', $_GET['id'])->find_array();
	
		} elseif (isset($_GET['account_id']) && !empty($_GET['account_id']) ) {
			// transactions for an account
			$transactions = ORM::for_table('transactions')
				->where_raw('account_id = ? and account_id in (select distinct id from accounts where owner =?)', array( $_GET['account_id'], $username))
				->order_by_desc('t_date')
				->find_array();
			
			$transactions[count($transactions) -1]['balance'] = 0;
	
		} elseif (isset($_GET['id']) && !empty($_GET['id']) && isset($_GET['action']) && !empty($_GET['action']) && $_GET['action'] == "delete" ) {
			$transaction = ORM::for_table('transactions')->find_one($_GET['id']);
			$transaction->delete();
	
		} else {
			$transactions = ORM::for_table('transactions')->find_array(); 
		}
	
	} else  {
	
		error_log("Got a PUT for transactions", 0);
		$requestData = json_decode(file_get_contents('php://input'));
	
		if (property_exists($requestData->data, "id")) {
			// got an id, this is an update
			// to an existing item
			error_log("Update a transaction", 0);
	
			$item = ORM::for_table('transactions')->find_one($requestData->data->id);
	
			// validate account
			$item->account_id = $requestData->data->account_id;
	
			$item->t_date = $requestData->data->t_date;
			$item->payee = $requestData->data->payee;
			$item->memo = $requestData->data->memo;
			$item->amount = $requestData->data->amount;
			$item->category_id = $requestData->data->category_id; // validate
	
			// need to write code to update updated_at, updated_by
			$item->updated_at = date("Y-m-d H:i:s");
			$item->updated_by = $username;
	
			$item->save();
			// return the new transaction in an array
			$transactions = ORM::for_table('transactions')->where('id', $item->id)->find_array();
	
		} else {
			error_log("CREATE a transaction", 0);
			// check to see if there is an account id
			// validate the account_id
			if (property_exists($requestData->data, "account_id")) {
				error_log("found an account_id for a transaction: " + $requestData->data->account_id, 0);
				$account_count = ORM::for_table('accounts')->where('id' , $requestData->data->account_id)->count();
				if ($account_count > 0) {
					error_log("found an account for an account_id: " + $requestData->data->account_id, 0);
	
					// create the transaction
					$transaction = ORM::for_table('transactions')->create();
					$transaction->account_id = $requestData->data->account_id;
	
					$transaction->t_date = $requestData->data->t_date;
					$transaction->payee = $requestData->data->payee;
					$transaction->memo = $requestData->data->memo;
					$transaction->amount = $requestData->data->amount;
					$transaction->category_id = $requestData->data->category_id; // validate
	
					// need to write code to update updated_at, updated_by
					$transaction->updated_at = date("Y-m-d H:i:s");
					$transaction->created_at = $transaction->updated_at;
					$transaction->updated_by = $username;
	
					$transaction->save();
	
					// return the new transaction in an array
					$transactions = ORM::for_table('transactions')->where('id', $transaction->id())->find_array();
					
				}
			} else {
				error_log("no account_id for a transaction: " , 0);
				error_log(json_encode($requestData) , 0);
	
			}
		}
	}
}
echo json_encode($transactions);



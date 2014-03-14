<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

session_start();

require_once './idiorm.php';
require_once '../config/database.php';

ORM::configure("mysql:host=" . $db_host . ";dbname=" . $db_name . ";");
ORM::configure('username', $db_username);
ORM::configure('password', $db_password);

$budgets = array();
$username = "";

if (isset($_SESSION['username'])) {
	$username = $_SESSION['username'];

	if ( $_SERVER['REQUEST_METHOD']  == "GET" ) {
	
		if(isset($_GET['id']) && !empty($_GET['id']) ) {
			$budgets = ORM::for_table('budgets')->where('owner', $username)->where('id', $_GET['id'])->find_array();
		} else {
			error_log("calculate budget totals");
			$budgets = ORM::for_table('budgets')->raw_query(
				'select c.name as category_name, sum(a.amount) as total from transactions a left join categories c on a.category_id=c.id where a.account_id in (select id from accounts where owner = ?) group by a.category_id'	, 
				array($username)
			
			)->find_array(); 


			// collect the source categories
			$budget_transfers = ORM::for_table('budget_transfers')->raw_query(
				'select c.name as source_category_name, c2.name as destination_category_name, a.amount as total from budget_transfers a left join categories c on a.source_category_id=c.id left join categories c2 on a.destination_category_id=c2.id where a.source_category_id in (select distinct id from categories where owner = ?) '	, 
				array($username)
			
			)->find_array(); 
			error_log(json_encode($budget_transfers), 0);

//			$budget_transfers = ORM::for_table('budget_transfers')
//				->table_alias('a')
//				->select('a.amount')
//				->select('c1.name', 'source_category_name')
//				->select('c2.name', 'destination_category_name')
//				->join('categories, array('budget_transfers.source_category_id', '=', 'categories.id'), 'c1')
//				->join('categories, array('budget_transfers.destination_category_id', '=', 'categories.id'), 'c2')


//			error_log("Found " . count($budget_transfers));
			foreach ($budget_transfers as $transfer) {
				error_log("Transfer " . $transfer['total'] . " from " . $transfer['source_category_name'] . " to " . $transfer['destination_category_name']);
				foreach ($budgets as &$b) {
					if ($b['category_name'] == $transfer['source_category_name']) {
						//error_log("subtract " . $transfer->total . " from " . $b['total']);
						$b['total'] = $b['total'] - $transfer['total'];
					}
					if ($b['category_name'] == $transfer['destination_category_name']) {
						//error_log("add " . $transfer->total . " to " . $b['total']);
						$b['total'] = $b['total'] + $transfer['total'];
					}
				}
			}
		}
	} else {
	
		$requestData = json_decode(file_get_contents('php://input'));
	
		if (property_exists($requestData->data, "id")) {
			$budget_count = ORM::for_table('budgets')->where('id' , $requestData->data->id)->count();
			if ($budget_count > 0) {
				// this is an update to an existing item
				$item = ORM::for_table('shopping_budgets')->find_one($requestData->data->id);
				$item->name = $requestData->data->name;
				$item->updated_at = date("Y-m-d H:i:s");
				$item->save();
	
			}
		} else {
			// create the budget transfer
			$budget = ORM::for_table('budget_transfers')->create();
			$budget->t_date = $requestData->data->t_date;
			$budget->source_category_id = $requestData->data->source_category_id;
			$budget->destination_category_id = $requestData->data->destination_category_id;
			$budget->amount = $requestData->data->amount;
			$budget->memo = $requestData->data->memo;

			$budget->created_at = date("Y-m-d H:i:s");
			$budget->updated_at = $budget->created_at;
			$budget->updated_by = $username;
			$budget->save();

		}
	}
}
echo json_encode($budgets);




<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

session_start();

require_once 'model/idiorm.php';
require_once 'config/database.php';

ORM::configure("mysql:host=" . $db_host . ";dbname=" . $db_name . ";");
ORM::configure('username', $db_username);
ORM::configure('password', $db_password);
if (isset($_SESSION['username'])) {
	$username = $_SESSION['username'];

	if ($_FILES["uploadedFile"]["error"] > 0) {
	  echo "Error: " . $_FILES["uploadedFile"]["error"] . "<br>";
	} else {
	  $file_text = file_get_contents($_FILES['uploadedFile']['tmp_name']);
	  $lines = explode("\n", $file_text);
	
		$imported_transaction = array();
		$imported_transaction['t_date'] = "";
		$imported_transaction['memo'] = "";
		$imported_transaction['payee'] = "";
		$imported_transaction['check_number'] = "";
		$imported_transaction['amount'] = 0;

		$category_split = array();
	
		$transaction_counter = 0;
	
	  foreach ($lines as $line) {
	  	$key = substr($line,0,1);
		$value = trim(substr($line, 1));
		switch($key) {
			case "D":
				$value = str_replace("'","/", $value);
				list($m,$d,$y) = split("/", $value);
				$value = $y . "-" . $m . "-" . $d;
				$imported_transaction['t_date'] = $value;
				break;
			case "M":
				$imported_transaction['memo'] = $value;
				break;
			case "N":
				$imported_transaction['check_number'] = $value;
				break;
			case "P":
				$imported_transaction['payee'] = $value;
				break;
			case "L":
				$imported_transaction['category_name'] = $value;
				break;
			case "T":
				$value = str_replace(",","", $value);
				$imported_transaction['amount'] = $value;
				break;
			case "S":
				$category_split['category_name'] = $value;
				break;
			case "$":
				$category_split['amount'] = $value;
				// do something
				break;
			case "^":
				// end of record
	
				// create the transaction
				$transaction = ORM::for_table('imported_transactions')->create();
	
				$transaction->account_id = 1;
		
				$transaction->t_date = $imported_transaction['t_date'];
				$transaction->payee = $imported_transaction['payee'];
				$transaction->memo = $imported_transaction['memo'];
				$transaction->amount = $imported_transaction['amount'];
				$transaction->check_number = $imported_transaction['check_number'];
				
				// do something about categories
				//$transaction->category_id = $requestData->data->category_id; // validate
		
				// need to write code to update updated_at, updated_by
				$transaction->updated_at = date("Y-m-d H:i:s");
				$transaction->created_at = $transaction->updated_at;
				$transaction->updated_by = $username;
		
				$transaction->save();
	
				$imported_transaction = array();
				$imported_transaction['t_date'] = "";
				$imported_transaction['memo'] = "";
				$imported_transaction['payee'] = "";
				$imported_transaction['check_number'] = "";
				$imported_transaction['amount'] = 0;
				$category_split = array();
				$transaction_counter++;
				break;
		}
		
	  }
	
		error_log("Transactions: " . $transaction_counter, 0);
	
	  echo "File uploaded successfully.";
	
	//	error_log(json_encode($_FILES), 0);
	
	//  echo "Upload: " . $_FILES["uploadedFile"]["name"] . "<br>";
	//  echo "Type: " . $_FILES["uploadedFile"]["type"] . "<br>";
	//  echo "Size: " . ($_FILES["uploadedFile"]["size"] / 1024) . " kB<br>";
	//  echo "Stored in: " . $_FILES["uploadedFile"]["tmp_name"];
	  
	//    if (file_exists("upload/" . $_FILES["uploadedFile"]["name"]))
	//      {
	//      echo $_FILES["uploadedFile"]["name"] . " already exists. ";
	//      }
	//    else
	//      {
	//      move_uploaded_file($_FILES["uploadedFile"]["tmp_name"],
	//      "upload/" . $_FILES["uploadedFile"]["name"]);
	//      echo "Stored in: " . "upload/" . $_FILES["uploadedFile"]["name"];
	//      }
	}
} else {
	  echo "You are not logged in.";
}

<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

session_start();
error_log("Doing a file upload.", 0);

echo "File Upload...";

if ($_FILES["uploadedFile"]["error"] > 0)
  {
  echo "Error: " . $_FILES["uploadedFile"]["error"] . "<br>";
  }
else
  {
	error_log(json_encode($_FILES), 0);

  echo "Upload: " . $_FILES["uploadedFile"]["name"] . "<br>";
  echo "Type: " . $_FILES["uploadedFile"]["type"] . "<br>";
  echo "Size: " . ($_FILES["uploadedFile"]["size"] / 1024) . " kB<br>";
  echo "Stored in: " . $_FILES["uploadedFile"]["tmp_name"];
    if (file_exists("upload/" . $_FILES["uploadedFile"]["name"]))
      {
      echo $_FILES["uploadedFile"]["name"] . " already exists. ";
      }
    else
      {
      move_uploaded_file($_FILES["uploadedFile"]["tmp_name"],
      "upload/" . $_FILES["uploadedFile"]["name"]);
      echo "Stored in: " . "upload/" . $_FILES["uploadedFile"]["name"];
      }
  }

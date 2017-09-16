<?php

/** NOTE: The session values must match DB values of users table */

define("DB_HOST", "localhost");
define("DB_NAME", "XXXX");
define("DB_USER", "XXXX");
define("DB_PASS", "XXXX");
define("MESSAGE_DATABASE_ERROR", "Failed to connect to database.");

    
try {
	$db_connection = new PDO('mysql:host='. DB_HOST.';dbname='. DB_NAME. ';charset=utf8', DB_USER, DB_PASS);
	return true;
} catch (PDOException $e) {
	echo "Connection failed: " . $e->getMessage();
}
<?php

$db_name = "camembert";
$db_user = "camembert";
$db_pass = "CamembertDB@Pacat";

function ConnectDB() {
	global $db, $db_name, $db_user, $db_pass;

	if(!$db) {
		$db = pg_connect("dbname=${db_name} user=${db_user} password=${db_pass}");
	}
	return $db;
}


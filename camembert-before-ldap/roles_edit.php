<?php
$page_name = "Roles";
include "inc/roles.php";

if(!$roles['roles']) {
	include "denied.php";
	exit();
}

include "inc/inc.header.php";

do {
	if(isset($_GET['add']) && $_GET['add']) {
		$r = pg_query("SELECT idrole FROM role WHERE name = '".$_GET['name']."'");
		if(pg_fetch_array($r)) {
			echo "<p class=\"error\">Il y a deja un role avec ce nom</p>\n";
			break;
		}
		$r = pg_query("SELECT MAX(idrole) FROM role");
		$a = pg_fetch_array($r);
		if($a !== false)
			$newid = $a[0]+1;
		else
			$newid = 0;

		$r = pg_query("INSERT INTO role(idrole, name) VALUES($newid, '".$_GET['name']."')");
	}
} while(false);

$r = pg_query("SELECT idrole, name FROM role");
if($a = pg_fetch_array($r)) {
?>
	<table class="list" cellpadding="1" cellspacing="1"><tr><th width="20">id</th><th width="50">nom</th></tr>
<?php
	$bline = false;
	while($a) {
		echo "<tr class=\"normal"; echo "2";
		echo "\"><td>".$a['idrole']."</td><td>".$a['name']."</td></tr>\n";
		$a = pg_fetch_array($r);
	}
	echo "</table>\n";
}
?>
	<p><form method="GET" action="">
		<input type="text" name="name" />
		<input type="submit" value="Ajouter" />
		<input type="hidden" name="add" value="1" />
	</form></p>
<?php
include "inc/inc.footer.php";
?>

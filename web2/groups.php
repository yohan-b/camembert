<?php
$page_name = "Groupes";
include "inc/roles.php";

if(!$roles['roles']) {
	include "denied.php";
	exit();
}

include "inc/inc.header.php";

if(isset($_POST['act']) && $_POST['act'] == 1) {
	$r = pg_query("SELECT idgroupe FROM groupe WHERE name = '".$_POST['name']."'");
	if($a = pg_fetch_array($r))
		echo "<p class=\"error\">Erreur: ce groupe existe d&eacute;j&agrave;</p>";
	else {
		$r = pg_query("SELECT MAX(idgroupe) FROM groupe");
		if($a = pg_fetch_array($r))
			$newid = $a[0]+1;
		else
			$newid = 1;
		pg_query("INSERT INTO groupe(idgroupe, name, roles) VALUES($newid, '".$_POST['name']."', 0)");
	}
}
else if(isset($_POST['act']) && $_POST['act'] == 2) {
	$r = pg_query("SELECT name FROM groupe WHERE idgroupe = ".$_POST['id']);
	if(($a = pg_fetch_array($r)) === false) {
		echo "<p class=\"error\">Mauvais id</p>\n";
	}
	else {
		$uroles = 0;
		foreach($_POST as $k => $v)
			if(is_int($k) && ($v == 'on' || $v == 1 || $v = 'checked'))
				$uroles += 1 << $k;
		pg_query("UPDATE groupe SET roles = $uroles WHERE idgroupe = ".$_POST['id']);
	}
}

$r = pg_query("SELECT idgroupe, name FROM groupe");
if($a = pg_fetch_array($r)) {
?>
	<table class="list" cellpadding="1" cellspacing="1"><tr><th width="20">id</th><th width="50">nom</th></tr>
<?php
	$bline = false;
	while($a) {
		echo "<tr class=\"normal"; if($bline) echo "2";
		echo "\"><td>".$a['idgroupe']."</td><td><a href=\"groups.php?id=".$a['idgroupe']."\">".$a['name']."</a></td></tr>\n";
		$a = pg_fetch_array($r);
		$bline = !$bline;
	}
	echo "</table>\n";
}
?>

<p><form method="post" action="">
	<input type="hidden" name="act" value="1" />
	<input type="text" name="name" />
	<input type="submit" value="Ajouter" />
</form></p>

<?php
if(isset($_GET['id'])) {
	$r = pg_query("SELECT name, roles FROM groupe WHERE idgroupe = ".pg_escape_string($_GET['id']));
	if(($a = pg_fetch_array($r)) === false) {
		echo "<p class=\"error\">Mauvais id</p>\n";
		include "inc/inc.footer.php";
		exit();
	}
	$uroles = $a['roles'];
	echo "<h3>".$a['name']."</h3>\n";
?>
	<form method="post" action="?id=<?php echo $_GET['id']; ?>">
		<input type="hidden" name="id" value="<?php echo $_GET['id']; ?>" />
		<input type="hidden" name="act" value="2" />
	<table class="list" cellpadding="1" cellspacing="1"><tr><th>&nbsp;</th><th width="50">role</th></tr>
<?php
	$r = pg_query("SELECT idrole, name FROM role ORDER BY idrole");
	$bline = false;
	while($a = pg_fetch_array($r)) {
		echo "<tr class=\"normal"; echo "2";
		echo "\"><td><input type=\"checkbox\" name=\"".$a['idrole']."\" ";
		if($uroles & (1 << $a['idrole']))
			echo "checked=\"checked\" ";
		echo "/></td><td>".$a['name']."</td></tr>\n";
	}
?>
	</table><p><input type="submit" /></form>
<?php
}

include "inc/inc.footer.php";
?>

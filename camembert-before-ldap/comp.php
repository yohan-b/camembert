<?php
$page_name = "Machine";
include "inc/roles.php";

function FormNew($user) {
	$r = pg_query("SELECT nom, prenom FROM user_pac WHERE iduser = $user");
	$a = pg_fetch_array($r);
	if(!$a) {
		echo "<p class=\"error\">Erreur : cet utilisateur n'existe pas</p>\n";
		return;
	}
	$name = "pc-de-".str_replace(" ", "-", strtolower($a['prenom']));
        $r = pg_query("SELECT name FROM computer");
	$a = pg_fetch_all_columns($r);
        $i = 2;
        while(in_array($name, $a)) {
                $name .= $i;
                $i++;
        }
?>
	<h3>Nouvelle machine pour <?php echo $a['nom']." ".$a['prenom']; ?></h3>
	<form action="" method="post"><input type="hidden" name="user" value="<?php echo $user; ?>" /><table>
	<tr><td>Nom de machine</td><td><input type="text" name="name" value="<?php echo $name; ?>"/></td></tr>
	<tr><td>Adresse MAC</td><td><input type="text" name="mac0" maxlength="2" size="1" /> : <input type="text" name="mac1" maxlength="2" size="1" /> :
		<input type="text" name="mac2" maxlength="2" size="1" /> : <input type="text" name="mac3" maxlength="2" size="1" /> :
		<input type="text" name="mac4" maxlength="2" size="1" /> : <input type="text" name="mac5" maxlength="2" size="1" /></td></tr>
	<tr><td><input type="submit" /></td><td></td></tr></table></form>
<?php
}

function FormEdit($comp) {
	$r = pg_query("SELECT idcomp, name, mac FROM computer WHERE idcomp = $comp");
	$a = pg_fetch_array($r);
	if(!$a) {
		echo "<p class=\"error\">Erreur : cette machine n'existe pas</p>\n";
		return;
	}
	$mac = explode(":", strtoupper($a['mac']));
?>
	<h3>Modification de machine</h3>
	<form action="" method="post"><input type="hidden" name="comp" value="<?php echo $comp; ?>" /><table>
	<tr><td>Nom de machine</td><td><input type="text" name="name" value="<?php echo $a['name']; ?>" /></td></tr>
	<tr><td>Adresse MAC</td><td><input type="text" name="mac0" maxlength="2" size="1" value="<?php echo $mac[0]; ?>" /> :
		<input type="text" name="mac1" maxlength="2" size="1" value="<?php echo $mac[1]; ?>" /> :
		<input type="text" name="mac2" maxlength="2" size="1" value="<?php echo $mac[2]; ?>" /> :
		<input type="text" name="mac3" maxlength="2" size="1" value="<?php echo $mac[3]; ?>" /> :
		<input type="text" name="mac4" maxlength="2" size="1" value="<?php echo $mac[4]; ?>" /> :
		<input type="text" name="mac5" maxlength="2" size="1" value="<?php echo $mac[5]; ?>" /></td></tr>
	<tr><td><input type="submit" /></td><td></td></tr></table></form>
<?php
}

function ValidateNew($user) {
	$r = pg_query("SELECT nom, prenom FROM user_pac WHERE iduser = $user");
	$a = pg_fetch_array($r);
	if(!$a)
		return "Erreur : cet utilisateur n'existe pas";

	if(!isset($_POST['name']) || !isset($_POST['mac0']) || !isset($_POST['mac1']) || !isset($_POST['mac2']) || !isset($_POST['mac3']) || !isset($_POST['mac4'])
	|| !isset($_POST['mac5']) || $_POST['name'] == "" || $_POST['mac0'] == "" || $_POST['mac1'] == "" || $_POST['mac2'] == "" || $_POST['mac3'] == ""
	|| $_POST['mac4'] == "" || $_POST['mac5'] == "")
		return "Erreur : formulaire incomplet";

	if(preg_match("/^[a-z0-9-]+$/", $_POST['name']) == 0)
		return "Erreur : nom de machine incorrect. Sont autoris&eacute;s : les lettres minuscules, les chiffres et le trait d'union";

	$mac = $_POST['mac0'].":".$_POST['mac1'].":".$_POST['mac2'].":".$_POST['mac3'].":".$_POST['mac4'].":".$_POST['mac5'];
	if(preg_match("/^([a-fA-F0-9]{2}:){5}[a-fA-F0-9]{2}$/", $mac) == 0)
		return "Erreur : MAC incorrecte. Sont autoris&eacute;s : les chiffres et les lettres de A &agrave; F. Deux caractères par champ.";

	$r = pg_query("SELECT idcomp FROM computer WHERE name = '".$_POST['name']."' OR mac = '$mac'");
	$a = pg_fetch_array($r);
	if($a)
		return "Erreur : il y a deja une machine avec ce nom ou cette adresse MAC";

	$r = pg_query("SELECT MAX(idcomp) FROM computer");
	if($a = pg_fetch_array($r))
		$newid = $a[0]+1;
	else
		$newid = 1;
	$r = pg_query("SELECT ip FROM ip_user WHERE free = '1' ORDER BY ip LIMIT 1");
	$a = pg_fetch_array($r);
	$ip = $a['ip'];
	pg_query("INSERT INTO computer(idcomp, name, mac, iduser, ip) VALUES($newid, '".$_POST['name']."', '$mac', $user, '$ip')");
	pg_query("UPDATE ip_user SET free = '0' WHERE ip = '$ip'");

	UpdateInterface($user);
	return true;
}

function ValidateEdit($comp) {
	$r = pg_query("SELECT iduser FROM computer WHERE idcomp = $comp");
	$a = pg_fetch_array($r);
	if(!$a)
		return "Erreur : cette machine n'existe pas";
	$user = $a['iduser'];

	if(!isset($_POST['name']) || !isset($_POST['mac0']) || !isset($_POST['mac1']) || !isset($_POST['mac2']) || !isset($_POST['mac3']) || !isset($_POST['mac4'])
	|| !isset($_POST['mac5']) || $_POST['name'] == "" || $_POST['mac0'] == "" || $_POST['mac1'] == "" || $_POST['mac2'] == "" || $_POST['mac3'] == ""
	|| $_POST['mac4'] == "" || $_POST['mac5'] == "")
		return "Erreur : formulaire incomplet";

	if(preg_match("/^[a-z0-9-]+$/", $_POST['name']) == 0)
		return "Erreur : nom de machine incorrect. Sont autoris&eacute;s : les lettres minuscules, les chiffres et le trait d'union";

	$mac = $_POST['mac0'].":".$_POST['mac1'].":".$_POST['mac2'].":".$_POST['mac3'].":".$_POST['mac4'].":".$_POST['mac5'];
	if(preg_match("/^([a-fA-F0-9]{2}:){5}[a-fA-F0-9]{2}$/", $mac) == 0)
		return "Erreur : MAC incorrecte. Sont autoris&eacute;s : les chiffres et les lettres de A &agrave; F. Deux caractères par champ.";

	$r = pg_query("SELECT idcomp FROM computer WHERE (name = '".$_POST['name']."' OR mac = '$mac') AND idcomp <> $comp");
	$a = pg_fetch_array($r);
	if($a)
		return "Erreur : il y a deja une machine avec ce nom ou cette adresse MAC";

	pg_query("UPDATE computer SET name = '".$_POST['name']."', mac = '$mac' WHERE idcomp = $comp");

	UpdateInterface($user);
	return true;
}

function DeleteComp($comp) {
	$r = pg_query("SELECT iduser FROM computer WHERE idcomp = $comp");
	$a = pg_fetch_array($r);
	if(!$a)
		return false;

	pg_query("UPDATE ip_user SET free = '1' WHERE ip IN(SELECT ip FROM computer WHERE idcomp = $comp)");
	pg_query("DELETE FROM computer WHERE idcomp = $comp");
	UpdateInterface($a['iduser']);
	return $a['iduser'];
}

function UpdateInterface($user) {
	$r = pg_query("SELECT idinterface, datedeco, certif FROM room r, user_pac u WHERE u.idroom = r.idroom AND iduser = $user");
	$a = pg_fetch_array($r);
	$idif = $a['idinterface'];
	$ddeco = explode("-", $a['datedeco']);
	$adeco = $ddeco[0];
	$mdeco = $ddeco[1];
	$certif = ($a['certif'] == 't' || (date("n") >= 9 && date("n") < 11));

	$r = pg_query("SELECT MAX(idaction) FROM action");
	$a = pg_fetch_array($r);
	if($a)
		$newid = $a[0]+1;
	else
		$newid = 1;

	$r = pg_query("SELECT COUNT(*) FROM computer WHERE iduser = $user");
	$a = pg_fetch_array($r);
	$nb = max(1, $a[0]);

	pg_query("INSERT INTO action(idaction, idinterface, numaction) VALUES($newid, $idif, 0)"); $newid++;
	pg_query("INSERT INTO action(idaction, idinterface, numaction, option) VALUES($newid, $idif, 3, '$nb')"); $newid++;
	$r = pg_query("SELECT mac FROM fdb WHERE idinterface = $idif AND type = 2 AND datelast = (SELECT MAX(datelast) FROM fdb)");
	while($a = pg_fetch_array($r)) {
		pg_query("INSERT INTO action(idaction, idinterface, numaction, option) VALUES($newid, $idif, 4, '".$a['mac']."')");
		$newid++;
	}
	if(($adeco > date("Y") || $mdeco >= date("m")) && $certif)
		pg_query("INSERT INTO action(idaction, idinterface, numaction) VALUES($newid, $idif, 1)");
}

if(isset($_POST['user']) && $_POST['user'] != "") {
	if($roles['inscription']) {
		$val = ValidateNew($_POST['user']);
		if($val === true)
			header("Location: user.php?id=".$_POST['user']);
		else {
			include "inc/inc.header.php";
			echo "<p class=\"error\">$val</p>\n";
		}
	}
	else {
		include "denied.php";
		return;
	}
}
else if(isset($_POST['comp']) && $_POST['comp'] != "") {
	if($roles['edit_comp']) {
		$val = ValidateEdit($_POST['comp']);
		if($val === true) {
			$r = pg_query("SELECT iduser FROM computer WHERE idcomp = ".$_POST['comp']);
			$a = pg_fetch_array($r);
			header("Location: user.php?id=".$a['iduser']);
		}
		else {
			include "inc/inc.header.php";
			echo "<p class=\"error\">$val</p>\n";
		}
	}
	else {
		include "denied.php";
		return;
	}
}
else if(isset($_GET['user']) && $_GET['user'] != "") {
	if($roles['inscription']) {
		include "inc/inc.header.php";
		FormNew($_GET['user']);
	}
	else {
		include "denied.php";
		return;
	}
}
else if(isset($_GET['id']) && $_GET['id'] != "") {
	if($roles['edit_comp']) {
		include "inc/inc.header.php";
		FormEdit($_GET['id']);
	}
	else {
		include "denied.php";
		return;
	}
}
else if(isset($_GET['del']) && $_GET['del'] != "") {
	if($roles['inscription']) {
		$user = DeleteComp($_GET['del']);
		if($user === false) {
			include "inc/inc.header.php";
			echo "<p class=\"error\">Erreur : cette machine n'existe pas</p>\n";
		}
		else
			header("Location: user.php?id=$user");
	}
	else {
		include "denied.php";
		return;
	}
}

include "inc/inc.footer.php";
?>

<?php
$page_name = "Mat&eacute;riel";

include "inc/roles.php";
include "inc/inc.header.php";

$id = $_GET['id'];
$r = pg_query("SELECT hostname, type, ostype, manageable, datecreation, datelast FROM materiel WHERE idmateriel = $id");
if(!$r || !($a = pg_fetch_array($r))) {
	echo "<p class=\"error\">Aucun &eacute;quipement ne correspond &agrave; cet identifiant</p>\n";
	include "inc/inc.footer.php";
	exit();
}

$action = $_GET['action'];
$confirm = $_GET['confirm'];
$last = ($_GET['last']!=""?$_GET['last']:3);

$r = pg_query("SELECT MAX(datelast) FROM link");
$lastlink = pg_fetch_array($r);
$lastlink = $lastlink[0];

$r = pg_query("SELECT MAX(datelast) FROM materiel");
$datelast = pg_fetch_array($r);
$datelast = $datelast[0];

if($action == "") {
	DisplayInfos($a);
	DisplayIPList();
	DisplayLinkedFrom();

	if($mana > 0) {
		if($mana == 2)
			echo "<p class=\"error\">Ce mat&eacute;riel n'est plus manageable. Les informations sont suceptibles de ne pas etre &agrave; jour.</p>";
		DisplayInterfaces();
		DisplayVLANList();
	}
	else
		echo "<p class=\"error\">Ce mat&eacute;riel n'est pas manageable.</p>";
}
else if($action == "del") {
	if($confirm == 1)
		DeleteMateriel();
	else
		DisplayFormDelete($a);
}
else if($action == "mac") {
	DisplayInfos($a);
	if($mana > 0) {
		if($mana == 2)
			echo "<p class=\"error\">Ce mat&eacute;riel n'est plus manageable. Les informations sont suceptibles de ne pas etre &agrave; jour.</p>";
		DisplayForwardingDatabase();
	}
	else
		echo "<p class=\"error\">Ce mat&eacute;riel n'est pas manageable.</p>";
}

// ---------------------------------
// Blabla fonctions
// ---------------------------------

function DisplayInfos($a) {
	global $id, $mana, $datelast, $roles;
?>
	<h3><?php echo $a[0]; ?></h3>
	<blockquote class="list">
	<!-- De la mise en page avec des tableaux, comme c'est pas beau :p -->
	<table><tr><td>
	<table>
	<tr><td><b>Type :</b></td><td><?php echo $a[1]; ?></td></tr>
	<tr><td valign="top"><b>OS :</b></td><td class="os"><?php echo str_replace("\n", "<br>", $a[2]); ?></td></tr>
	<tr><td><b>Statut :</b></td><td>
<?php
	$mana = $a[3];
	if($a[5] < $datelast) echo "<font color=\"red\"><b>DOWN</b></font>\n";
	else {
		echo "<font color=\"green\">UP</font> ";
		if($a[3] == 2) echo "anciennement manageable";
		else if($a[3] == 0) echo "non manageable";
	}

	if(substr($a[0], 0, 3)=='SEP')
		$link = "phones.php?filter=${a[0]}";
	else {
		if($mana == 0)
			$link = "materiel_nomana.php?filter=${a[0]}";
		else
			$link = "materiel_mana.php?filter=${a[0]}";
	}
?>
	</td></tr><tr><td><b>Trouv&eacute; le :</b></td><td><?php echo date("D d/m/Y H:i", $a[4]); ?></td></tr>
	<tr><td><b>Mis &agrave; jour :</b></td><td><?php echo date("D d/m/Y H:i", $a[5]); ?></td></tr>
	</table></td>

	<td><ul>
		<li><a href="materiel.php?id=<?php echo $id; ?>">Liste des interfaces</a></li>
<?php
	if($mana > 0) {
?>
		<li><a href="materiel.php?id=<?php echo $id; ?>&action=mac">Table des adresses MAC</a></li>
<?php
	}
	if($roles['root']) {
?>
		<li><a href="materiel.php?id=<?php echo $id; ?>&action=del">Supprimer cet &eacute;quipement</a></li>
<?php } ?>
	</ul></td></tr></table>

	</blockquote>
<?php
}

function DisplayIPList() {
	global $id;
?>
	<h4>Liste des IPs</h4>
	<ul>
<?php
	$r = pg_query("SELECT MAX(datelast) FROM ip");
	$a = pg_fetch_array($r);
	$datelast = $a[0];
	$r = pg_query("SELECT ip, main, datelast FROM ip WHERE idmateriel = $id ORDER BY main DESC, datelast DESC, ip");
	while($a = pg_fetch_array($r)) {
		$tmp = $a[0];
		if($a[2] < $datelast) $tmp = "<font color=\"red\">$tmp</font>";
		else $tmp = "<font color=\"green\">$tmp</font>";
		if($a[1] == 't') $tmp = "<b>$tmp</b>";
		echo "<li>$tmp</li>\n";
	}
	echo "</ul>\n";
}

function DisplayLinkedFrom() {
	global $id, $lastlink;
?>
	<h4>Trouv&eacute; sur</h4>
	<blockquote class="list">
	<table class="list" cellspacing="1" cellpadding="1">
	<tr><th width="140">Hostname</th><th width="140">Interface</th><th width="40">VLAN</th><th width="40">Voice</th><th width="140">Vu la derni&egrave;re fois</th></tr>
<?php
	$r = pg_query("SELECT m.idmateriel, hostname, ifname, l.datelast, ifvlan, ifvoicevlan FROM link l, interface i, materiel m
		WHERE l.idinterface=i.idinterface AND i.idmateriel=m.idmateriel AND iddstmateriel=$id
		ORDER BY datelast DESC, hostname, ifname");
	$bline = false;
	while($a = pg_fetch_array($r)) {
		switch($a[4]) {
			case 0: $vlan = "Ind&eacute;termin&eacute;"; $voice = ""; break;
			case -1: $vlan = "Trunk"; $voice = ""; break;
			default:
				$vlan = $a[4];
				if($a[5] != 0 && $a[5] != 4096) $voice = $a[5];
				else $voice = "";
				break;
		}

		$class = (($a[3]<$lastlink)?"red":"normal");
		if($bline) $class .= "2";
		$bline = !$bline;

		echo "<tr class=\"$class\"><td><a href=\"materiel.php?id=${a[0]}\">${a[1]}</a></td>";
		echo "<td>${a[2]}</td><td>$vlan</td><td>$voice</td><td>".date("D d/m/Y H:i", $a[3])."</td></tr>\n";
	}
?>
	</table>
	</blockquote>
<?php
}

function DisplayInterfaces() {
	global $id, $lastlink;
?>
	<h4>Liste des interfaces physiques</h4>
	<blockquote class="list">
	<table class="list" cellpadding="1" cellspacing="1">
	<tr><th width="140">Nom</th><th width="240">Description</th><th width="40">Etat</th><th width="40">VLAN</th>
	<th width="40">Voice</th><th width="240">Connect&eacute; &agrave;</th></tr>
<?php
	$r = pg_query("SELECT idinterface, ifname, ifdescription, ifadminstatus, ifoperstatus, ifvlan, ifvoicevlan, portsecenable, portsecstatus FROM interface
		WHERE idmateriel = $id AND iftype IN(6, 22, 71, 117) ORDER BY ifnumber");
	$bline = false;
	while($a = pg_fetch_array($r)) {
		$errdisable = (($a[7] == 't') && ($a[8] == 3));
		$class = ($a[3]==0?"normal":($errdisable?"red":($a[4]==1?"green":"blue")));
		if($bline) $class .= "2";
		$bline = !$bline;

		switch($a[5]) {
			case 0: $vlan = "Ind&eacute;termin&eacute;"; $voice = ""; break;
			case -1: $vlan = "Trunk"; $voice = ""; break;
			default:
				$vlan = $a[5];
				if($a[6] != 0 && $a[6] != 4096) $voice = $a[6];
				else $voice = "";
				break;
		}
		echo "<tr class=\"$class\"><td><a href=\"interface.php?id=${a[0]}\">${a[1]}</a></td><td>${a[2]}</td>";
		echo "<td>".($a[3]==0?"SHUT":($errdisable?"ERR-DIS":($a[4]==1?"UP":"DOWN")))."</td><td>$vlan</td><td>$voice</td>";
		echo "<td>";
		$r2 = pg_query("SELECT idmateriel, hostname, dstifname FROM materiel, link WHERE idmateriel=iddstmateriel AND idinterface=${a[0]} AND link.datelast=$lastlink");
		$bfirst = true;
		while($arr = pg_fetch_array($r2)) {
			echo "".($bfirst?"":"<br>")."<a href=\"materiel.php?id=".$arr[0]."\">".$arr[1]."</a> / ".$arr[2];
			$bfirst = false;
		}
		echo "</td></tr>\n";
	}
?>
	</table>
	</blockquote>
<?php
}

function DisplayVLANList() {
	global $id;

	$r = pg_query("SELECT idinterface, ifname, ifdescription, ifadminstatus, ifoperstatus FROM interface
		WHERE idmateriel = $id AND iftype IN(53, 135) ORDER BY ifname, ifnumber");
	if(pg_num_rows($r) <= 0)
		return;
?>
	<h4>Liste des interfaces virtuelles</h4>
	<blockquote class="list">
	<table class="list" cellpadding="1" cellspacing="1">
	<tr><th width="140">Nom</th><th width="240">Description</th><th width="40">Etat</th></tr>
<?php
	$bline = false;
	while($a = pg_fetch_array($r)) {
		$class = ($a[3]==2?"blue":($a[4]==1?"green":"red"));
		if($bline) $class .= "2";
		$bline = !$bline;

		echo "<tr class=\"$class\"><td><a href=\"interface.php?id=${a[0]}\">${a[1]}</a></td><td>${a[2]}</td>";
		echo "<td>".($a[3]==2?"SHUT":($a[4]==1?"UP":"DOWN"))."</td></tr>\n";
	}
?>
	</table>
	</blockquote>
<?php
}

function DisplayForwardingDatabase() {
	global $id, $last;
?>
	<h4>Table de forwarding MAC</h4>
	<blockquote class="list">
	<form action="materiel.php">
		<p>Afficher les MACs enregistr&eacute;es depuis <input type="text" name="last" value="<?php echo $last; ?>" size="2"> jours.
		<input type="hidden" name="id" value="<?php echo $id; ?>"><input type="hidden" name="action" value="mac">
		<input type="submit" value="Filtrer"></p>
	</form>
	<table class="list" cellpadding="1" cellspacing="1">
	<tr><th width="140">Interface</th><th width="40">VLAN</th><th width="120">MAC</th><th width="50">Type</th><th width="100">IP</th>
	<th width="120">Date enr</th><th width="120">Date M&agrave;J</th></tr>
<?php
	$datelast = 86400 * $last;
	$r = pg_query("SELECT i.idinterface, ifname, vlan, mac, datefirst, datelast, type
		FROM interface i, fdb WHERE i.idinterface = fdb.idinterface AND idmateriel = $id
		AND datelast > ".(time()-$datelast)."
		ORDER BY ifnumber, datelast DESC, vlan, mac");

	$lastid = 0;
	$bline = false;
	while($a = pg_fetch_array($r)) {
		$class = "normal".($bline?"2":"");
		$bline = !$bline;

		if($lastid != $a[0]) {
			$lastid = $a[0];
			$ifname = $a[1];
		}
		else
			$ifname = "";
		if($a[2] != 0)
			$vlan = $a[2];
		else
			$vlan = "";
		switch($a[6]) {
			case 0: $type = "FDB"; break;
			case 1: $type = "static"; break;
			case 2: $type = "sticky"; break;
			default: $type = "";
		}

		echo "<tr class=\"$class\"><td><b>$ifname</b></td><td>$vlan</td><td>${a[3]}</td><td>$type</td><td>";
		$r2 = pg_query("SELECT ip FROM arpcache WHERE mac = '${a[3]}' AND datelast = ${a[5]}");
		if($a2 = pg_fetch_array($r2))
			echo $a2[0];
		echo "</td><td>".date("d/m/Y H:i", $a[4])."</td><td>".date("d/m/Y H:i", $a[5])."</td></tr>\n";
	}
?>
	</table>
	</blockquote>
<?php
}

function DisplayFormDelete($a) {
	global $id
?>
	<blockquote class="list">
	<p><b><font color="red">Etes vous sur de vouloir supprimer <?php echo $a[0]; ?> ?</font></b><br>
	[<a href="materiel.php?id=<?php echo $id; ?>&action=del&confirm=1">Oui</a>]
	[<a href="materiel.php?id=<?php echo $id; ?>">Non</a>]</p>
	</blockquote>
<?php
}

function DeleteMateriel() {
	global $id;

	pg_query("DELETE FROM link WHERE iddstmateriel = $id");
	pg_query("DELETE FROM link WHERE idinterface IN (SELECT idinterface FROM interface WHERE idmateriel = $id)");
	pg_query("DELETE FROM fdb WHERE idinterface IN (SELECT idinterface FROM interface WHERE idmateriel = $id)");
	pg_query("DELETE FROM action WHERE idinterface IN (SELECT idinterface FROM interface WHERE idmateriel = $id)");
	pg_query("DELETE FROM action_log WHERE idinterface IN (SELECT idinterface FROM interface WHERE idmateriel = $id)");
	pg_query("DELETE FROM interface WHERE idmateriel = $id");
	pg_query("DELETE FROM ip WHERE idmateriel = $id");
	pg_query("DELETE FROM arpcache WHERE foundon = $id");
	pg_query("DELETE FROM materiel WHERE idmateriel = $id");
?>
	<blockquote class="list">
	<p><b>Equipement supprim&eacute;.</b><br>
	<a href="list.php">Retourner &agrave; la liste du mat&eacute;riel</a></p>
	</blockquote>
<?php
}

include "inc/inc.footer.php";
?>

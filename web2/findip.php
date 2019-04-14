<?php
$page_name = "Recherche d'IP";

$ip = $_GET['ip'];
if($ip == '')
	header("Location: index.php");

include "inc/roles.php";
include "inc/inc.header.php";

?>

<form action="findip.php">Chercher une adresse IP
	<input type="text" name="ip" value="<?php echo $ip; ?>">
	<input type="submit" value="Rechercher">
</form>
<blockquote>

<?php

$r = pg_query("SELECT MAX(datelast) FROM ip");
$datelast = pg_fetch_array($r);
$datelast = $datelast[0];

// IPs associées à un équipement
$r = pg_query("SELECT m.idmateriel, hostname, ip.datecreation, ip.datelast
	FROM materiel m, ip WHERE m.idmateriel = ip.idmateriel AND ip = '$ip' ORDER BY ip.datelast DESC");
$a = pg_fetch_array($r);
if($a) {
?>
	<p>L'adresse IP <b><?php echo $ip; ?></b> a &eacute;t&eacute; associ&eacute;e au mat&eacute;riel suivant</p>
	<table class="list" cellspacing="1" cellpadding="1">
	<tr><th width="160">Mat&eacute;riel</th><th width="140">Associ&eacute;e le</th><th width="140">Jusqu'au</th></tr>
<?php
	$bline = false;
	while($a) {
		if($a[3] == $datelast)
			$mat = "<b>${a[1]}</b>";
		else
			$mat = $a[1];
		echo "<tr class=\"normal".($bline?2:"")."\"><td><a href=\"materiel.php?id=${a[0]}\">$mat</a></td>";
		echo "<td>".date("D d/m/Y H:i", $a[2])."</td><td>".date("D d/m/Y H:i", $a[3])."</td></tr>\n";
		$bline = !$bline;

		$a = pg_fetch_array($r);
	}
	echo "</table>\n";
}

// IP dans le cache ARP
$r = pg_query("SELECT mac, foundon, hostname, a.datefirst, a.datelast
	FROM arpcache a, materiel m WHERE foundon = idmateriel AND ip = '$ip' ORDER BY a.datelast DESC");
$a = pg_fetch_array($r);
if($a) {
?>
	<p>L'adresse IP <b><?php echo $ip; ?></b> a &eacute;t&eacute; associ&eacute;e aux adresses MAC suivantes</p>
	<table class="list" cellspacing="1" cellpadding="1">
	<tr><th width="120">MAC</th><th width="160">Associ&eacute; par</th><th width="140">Associ&eacute;e le</th><th width="140">Jusqu'au</th></tr>
<?php
	$bline = false;
	while($a) {
		if($a[4] == $datelast)
			$mac = "<b>${a[0]}</b>";
		else
			$mac = $a[0];
		echo "<tr class=\"normal".($bline?2:"")."\"><td><a href=\"findmac.php?mac=${a[0]}\">$mac</a></td>";
		echo "<td><a href=\"materiel.php?id=${a[1]}\">${a[2]}</a></td>";
		echo "<td>".date("D d/m/Y H:i", $a[3])."</td><td>".date("D d/m/Y H:i", $a[4])."</td></tr>\n";
		$bline = !$bline;

		$a = pg_fetch_array($r);
	}
	echo "</table>\n";
}

// IP dans le DHCP
$r = pg_query("SELECT u.iduser, nom, prenom, c.name, r.idroom, r.name FROM computer c, user_pac u, room r
	WHERE c.iduser = u.iduser AND u.idroom = r.idroom AND ip = '$ip'");
$a = pg_fetch_array($r);
if($a) {
?>
	<p>L'adresse IP <b><?php echo $ip; ?></b> est pr&eacute;sente dans le DHCP</p>
	<table class="list" cellspacing="1" cellpadding="1">
	<tr><th width="160">Utilisateur</th><th width="60">Chambre</th><th width="100">Nom de machine</th></tr>
<?php
	while($a) {
		$bline = false;
		echo "<tr class=\"normal".($bline?2:"")."\"><td><a href=\"user.php?id=".$a[0]."\">".$a[1]." ".$a[2]."</a></td>";
		echo "<td><a href=\"room.php?id=".$a[4]."\">".$a[5]."</a></td><td>".$a[3]."</td></tr>\n";
		$bline = !$bline;

		$a = pg_fetch_array($r);
	}
	echo "</table>\n";
}

echo "</blockquote>\n";
include "inc/inc.footer.php";

?>

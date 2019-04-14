<?php
$page_name = "Liste du mat&eacute;riel";

include "inc/roles.php";
include "inc/inc.header.php";

$mode = $_GET['mode'];
$filter = $_GET['filter'];
$vlan = $_GET['vlan'];
if($vlan != "")
	$mode = "phone";
$o = $_GET['order'];

if($o == '') $o = 'name';
switch($o) {
	case 'name':	$order = "m.hostname"; break;
	case 'ip':	$order = "ip"; break;
	case 'type':	$order = "m.type, m.hostname"; break;
	case 'link':	$order = "m2.hostname, ifname"; break;
	default:	$order = "m.hostname"; break;
}
?>

<blockquote>
<form action="list.php" name="filterform">
<?php
	if($mode != "phone")
		echo "Filtrer par nom : <input type=\"text\" name=\"filter\" size=\"40\" value=\"$filter\">\n";
	else
		echo "Filtrer par VLAN : <input type=\"text\" name=\"vlan\" size=\"10\" value=\"$vlan\">\n";
?>
	<input type="submit" value="Filtrer">
</form>
</blockquote>
<hr>

<?php
if($mode != "phone") {
?>
	<h3>Mat&eacute;riel manageable</h3>
	<blockquote>
<?php
	$q = "SELECT m.idmateriel, hostname, type, manageable, ip, m.datelast FROM materiel m, ip
		WHERE m.idmateriel = ip.idmateriel AND manageable>0 AND main = 't'
		AND (capabilities & 128) <> 128 ";
	if($filter != "")
		$q .= "AND hostname ILIKE '%$filter%' ";
	$q .= "ORDER BY $order";
	$r = pg_query($q);
	DispMatList($r);
?>
	</blockquote>

	<h3>Mat&eacute;riel non manageable</h3>
	<blockquote>
<?php
	$q = "SELECT m.idmateriel, hostname, type, manageable, ip, m.datelast FROM materiel m, ip
		WHERE m.idmateriel = ip.idmateriel AND manageable=0 AND main = 't'
		AND (capabilities & 128) <> 128 AND hostname NOT LIKE 'SEP%' ";
	if($filter != "")
		$q .= "AND hostname ILIKE '%$filter%' ";
	$q .= "ORDER BY $order";
	$r = pg_query($q);
	DispMatList($r);
?>
	</blockquote>

<?php
} else {
?>
	<h3>T&eacute;l&eacute;phones</h3>
	<blockquote>
<?php
	$q = "SELECT m.idmateriel, m.hostname, m.type, m.manageable, ip, m.datelast, ifvoicevlan, m2.idmateriel, m2.hostname, ifname
		FROM materiel m, ip, link l, interface i, materiel m2
		WHERE m.idmateriel = ip.idmateriel
		AND m.idmateriel = l.iddstmateriel AND l.idinterface = i.idinterface AND i.idmateriel = m2.idmateriel
		AND main = 't' AND (m.capabilities & 128) = 128 ";
	if($vlan != "")
		$q .= "AND ifvoicevlan = $vlan ";
	$q .= "ORDER BY $order";
	$r = pg_query($q);
	DispMatList($r);
?>
	</blockquote>

	<h3>Boitiers ATA</h3>
	<blockquote>
<?php
	$q = "SELECT m.idmateriel, m.hostname, m.type, m.manageable, ip, m.datelast, ifvoicevlan, m2.idmateriel, m2.hostname, ifname
		FROM materiel m, ip, link l, interface i, materiel m2
		WHERE m.idmateriel = ip.idmateriel
		AND m.idmateriel = l.iddstmateriel AND l.idinterface = i.idinterface AND i.idmateriel = m2.idmateriel
		AND main = 't' AND (m.capabilities & 128) <> 128 AND m.hostname LIKE 'SEP%' ";
	if($vlan != "")
		$q .= "AND ifvoicevlan = $vlan ";
	$q .= "ORDER BY $order";
	$r = pg_query($q);
	DispMatList($r);
?>
	</blockquote>

<?php
}

include "inc/inc.footer.php";

function DispMatList($r) {
	global $mode, $o, $filter, $vlan;
?>
	<table class="list" cellpadding="1" cellspacing="1">
		<tr><th width="120">IP <a href="list.php?mode=<?php echo $mode; ?>&order=ip&filter=<?php echo $filter; ?>&vlan=<?php echo $vlan; ?>"><img src="arrow<?php echo ($o=='ip'?'2':''); ?>.gif"></a></th>
		<th width="160">Hostname <a href="list.php?mode=<?php echo $mode; ?>&order=name&filter=<?php echo $filter; ?>&vlan=<?php echo $vlan; ?>"><img src="arrow<?php echo ($o=='name'?'2':''); ?>.gif"></a></th>
		<th width="160">Type <a href="list.php?mode=<?php echo $mode; ?>&order=type&filter=<?php echo $filter; ?>&vlan=<?php echo $vlan; ?>"><img src="arrow<?php echo ($o=='type'?'2':''); ?>.gif"></a></th>
<?php
	if($mode == "phone") {
?>
		<th width="40">VLAN</th>
		<th width="240">Connect&eacute; &agrave; <a href="list.php?mode=<?php echo $mode; ?>&order=link&filter=<?php echo $filter; ?>&vlan=<?php echo $vlan; ?>"><img src="arrow<?php echo ($o=='link'?'2':''); ?>.gif"></a></th>
<?php
	}

	echo "</tr>\n";

	$res = pg_query("SELECT MAX(datelast) FROM materiel");
	$a = pg_fetch_array($res);
	$datelast = $a[0];

	$i = 0;
	$bline = false;
	$added = array();
	while($a = pg_fetch_array($r)) {
		if(array_search($a[0], $added) !== false)
			continue;

		array_push($added, $a[0]);

		if($a[5] < $datelast) $class = "red";
		else if($a[3] == 2) $class = "blue";
		else $class = "normal";
		if($bline) $class .= "2";

		if($mode == "phone") $proto = "http";
		else $proto = "telnet";

		echo "<tr class=\"$class\"><td><a href=\"$proto://${a[4]}/\">${a[4]}</a></td>";
		echo "<td><a href=\"materiel.php?id=${a[0]}\">${a[1]}</a></td><td>${a[2]}</td>";

		if($mode == "phone") {
			echo "<td>${a[6]}</td>";
			echo "<td><a href=\"materiel.php?id=${a[7]}\">${a[8]}</a> / ${a[9]}</td>";
		}

		echo "</tr>\n";

		$i++;
		$bline = !$bline;
	}
	echo "</table>\n";
	echo "<p>$i &eacute;quipements trouv&eacute;s.</p>\n";
}

?>

<?php
$page_name = "Recherche de MAC";

include "libfind.php";
$mac = $_GET['mac'];
$mac = CheckFormat($mac);
if ($mac == '') header('Location: index.php');

include "inc/roles.php";
include "inc/inc.header.php";

$mac = strtolower($mac);
$unique = (strlen($mac) == 17);
?>

<form action="findmac.php">Chercher une adresse MAC
	<input type="text" name="mac" value="<?php echo $mac; ?>">
	<input type="submit" value="Rechercher">
</form>

<?php
echo "<blockquote>\n";
if ($mac == '')
	echo "<p class=\"error\">L'adresse MAC ".$_GET["mac"]." n'est pas valide.</p>\n";
else
	SearchMAC($mac, $unique);
echo "</blockquote>\n";

include "inc/inc.footer.php";


function SearchMAC($mac, $unique) {
	// Vérifions si la MAC correspond à l'adresse d'une interface
	$r = pg_query("SELECT ifaddress, idinterface, ifname, i.idmateriel, hostname
		FROM interface i, materiel m
		WHERE i.idmateriel = m.idmateriel
		AND CAST(ifaddress AS VARCHAR(24)) ILIKE '%$mac%'
		ORDER BY ifaddress");
	$a = pg_fetch_array($r);
	if($a) {
?>
		<p>L'adresse <b><?php echo $mac; ?></b> correspond aux interfaces suivantes</p>
		<table class="list" cellspacing="1" cellpadding="1">
		<tr><th width="120">Adresse MAC</th><th width="160">Mat&eacute;riel</th><th width="140">Interface</th></tr>
<?php
		$bline = false;
		while($a) {
			echo "<tr class=\"normal".($bline?2:"")."\"><td>${a[0]}</td><td><a href=\"materiel.php?id=${a[3]}\">${a[4]}</a></td>";
			echo "<td><a href=\"interface.php?id=${a[1]}\">${a[2]}</a></td></tr>\n";
			$bline = !$bline;
			$a = pg_fetch_array($r);
		}
		echo "</table>\n";
	}

	$r = pg_query("SELECT MAX(datelast) FROM arpcache");
	$datelast = pg_fetch_array($r);
	$datelast = $datelast[0];

        if($unique) {
                $a = SearchMACinDHCP($mac);
                if($a) {
                        ?>
                                <p>L'adresse MAC <b><?php echo $mac; ?></b> est pr&eacute;sente dans le DHCP</p>
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

		// Recherche dans le cache ARP
		$r = pg_query("SELECT ip, foundon, hostname, a.datefirst, a.datelast
			FROM arpcache a, materiel m
			WHERE foundon = idmateriel AND mac = '$mac'
			ORDER BY datelast DESC, ip");
		$a = pg_fetch_array($r);
		if($a) {
?>
			<p>L'adresse <b><?php echo $mac; ?></b> a &eacute;t&eacute; associ&eacute;e aux IP suivantes</p>
			<table class="list" cellspacing="1" cellpadding="1">
			<tr><th width="100">IP</th><th width="160">Associ&eacute; par</th><th width="140">Associ&eacute;e le</th>
			<th width="140">Jusqu'au</th><th width="160">Equipement correspondant</th></tr>
<?php
			$bline = false;
			while($a) {
				echo "<tr class=\"normal".($bline?2:"")."\"><td>";
				$bline = !$bline;
				if($a[4] == $datelast)
					echo "<b>${a[0]}</b>";
				else
					echo $a[0];
				echo "</td><td><a href=\"materiel.php?id=${a[1]}\">${a[2]}</a></td><td>".date("D d/m/Y H:i", $a[3])."</td><td>".date("D d/m/Y H:i", $a[4])."</td>";

				$r2 = pg_query("SELECT m.idmateriel, hostname FROM materiel m, ip
					WHERE m.idmateriel = ip.idmateriel AND ip = '${a[0]}' AND ip.datelast = ${a[4]}");
				$a2 = pg_fetch_array($r2);
				if($a2)
					echo "<td><a href=\"materiel.php?id=${a2[0]}\">${a2[1]}</a></td>";
				else
					echo "<td></td>";
				echo "</tr>\n";

				$a = pg_fetch_array($r);
			}
			echo "</table>\n";
		}

                $a = SearchMAConInterface($mac);
		if($a) {
?>
			<p>L'adresse <b><?php echo $mac; ?></b> a &eacute;t&eacute; trouv&eacute;e sur les interfaces suivantes</p>
			<table class="list" cellspacing="1" cellpadding="1">
			<tr><th width="220">Mat&eacute;riel / Interface</th><th width="40">VLAN</th><th width="100">Date enr</th>
			<th width="100">M&agrave;J</th></tr>
<?php
			$bline = false;
			while($a) {
				if($a[5] == $datelast)
					$host = "<b>".$a[1]."</b>";
				else
					$host = $a[1];

				echo "<tr class=\"normal".($bline?2:"")."\"><td><a href=\"materiel.php?id=${a[0]}\">$host</a> / <a href=\"interface.php?id=${a[6]}\">${a[2]}</a></td>";
				echo "<td>${a[3]}</td><td>".date("d/m/Y H:i", $a[4])."</td><td>".date("d/m/Y H:i", $a[5])."</td></tr>\n";
				$bline = !$bline;
				$a = pg_fetch_array($r);
			}
			echo "</table>\n";
		}
	}
	else {
		// Recherche dans le cache ARP
		$r = pg_query("SELECT mac, ip, foundon, hostname, a.datefirst, a.datelast
			FROM arpcache a, materiel m
			WHERE foundon = idmateriel AND CAST(mac AS VARCHAR(24)) ILIKE '%$mac%'
			ORDER BY mac");
		$a = pg_fetch_array($r);
		if($a) {
?>
			<p>Des adresses MAC contenant <b><?php echo $mac; ?></b> ont &eacute;t&eacute; associ&eacute;es aux IPs suivantes</p>
			<table class="list" cellspacing="1" cellpadding="1">
			<tr><th width="120">MAC</th><th width="100">IP</th><th width="160">Associ&eacute; par</th>
			<th width="140">Associ&eacute;e le</th><th width="140">Jusqu'au</th><th width="160">Equipement correspondant</th></tr>
<?php
			$bline = false;
			while($a) {
				echo "<tr class=\"normal".($bline?2:"")."\"><td>${a[0]}</td><td>${a[1]}</td>";
				echo "<td><a href=\"materiel.php?id=${a[2]}\">${a[3]}</a></td>";
				echo "<td>".date("D d/m/Y H:i", $a[4])."</td><td>".date("D d/m/Y H:i", $a[5])."</td>";

				$r2 = pg_query("SELECT m.idmateriel, hostname FROM materiel m, ip
					WHERE m.idmateriel = ip.idmateriel AND ip = '${a[1]}' AND ip.datelast = ${a[5]}");
				$a2 = pg_fetch_array($r2);
				if($a2)
					echo "<td><a href=\"materiel.php?id=${a2[0]}\">${a2[1]}</a></td>";
				else
					echo "<td></td>";
				echo "</tr>\n";
				$bline = !$bline;

				$a = pg_fetch_array($r);
			}
			echo "</table>\n";
		}

		// Recherche sur les interfaces.
		$r = pg_query("SELECT mac, m.idmateriel, hostname, ifname, vlan, fdb.datefirst, fdb.datelast
			FROM materiel m, interface i, fdb
			WHERE m.idmateriel = i.idmateriel AND i.idinterface = fdb.idinterface
			AND CAST(mac AS VARCHAR(24)) ILIKE '%$mac%' ORDER BY fdb.datelast DESC, mac, i.idmateriel, ifnumber");
		$a = pg_fetch_array($r);
		if($a) {
?>
			<p>L'adresse <b><?php echo $mac; ?></b> a &eacute;t&eacute; trouv&eacute;e sur les interfaces suivantes</p>
			<table class="list" cellspacing="1" cellpadding="1">
			<tr><th width="220">Mat&eacute;riel / Interface</th><th width="120">MAC</th><th width="40">VLAN</th>
			<th width="100">Date enr</th><th width="100">M&agrave;J</th></tr>
<?php
			$bline = false;
			while($a) {
				echo "<tr class=\"normal".($bline?2:"")."\"><td><a href=\"materiel.php?id=${a[1]}\">${a[2]}</a> / ${a[3]}</td>";
				echo "<td>${a[0]}</td><td>${a[4]}</td><td>".date("d/m/Y H:i", $a[5])."</td><td>".date("d/m/Y H:i", $a[6])."</td></tr>\n";
				$bline = !$bline;
				$a = pg_fetch_array($r);
			}
			echo "</table>\n";
		}
	}
}

?>

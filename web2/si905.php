<?php
$page_name = "Salle informatique";

include "inc/roles.php";
include "inc/inc.header.php";
setlocale(LC_CTYPE, 'fr_FR.utf8');

$id = 53;
$mode = $_GET['mode'];
$r = pg_query("SELECT i.idmateriel, hostname, ifnumber, ifname, ifdescription, ifaddress, ifspeed, ifadminstatus,
	ifoperstatus, iftype, ifvlan, ifvoicevlan, ifnativevlan, ifmodule, ifport, portdot1d, portfast, portsecenable,
	portsecstatus, portsecmaxmac, portseccurrmac, portsecviolation, portseclastsrcaddr, portsecsticky FROM interface i, materiel m
	WHERE i.idmateriel = m.idmateriel AND idinterface = $id");
if(!$r || !($a = pg_fetch_array($r))) {
	echo "<p class=\"error\">Aucune interface ne correspond &agrave; cet identifiant</p>\n";
	include "inc/inc.footer.php";
	exit();
}

if($mode == "conf" && $_POST['num'] != "") {
	$r = pg_query("SELECT MAX(idaction) FROM action");
	if($am = pg_fetch_array($r))
		$newid = $am[0]+1;
	else
		$newid = 1;
	$num = pg_escape_string($_POST['num']);
	$opt = iconv("UTF-8", "ASCII//TRANSLIT", $_POST['opt']);
	if($num == -1) {
		pg_query("INSERT INTO action VALUES($newid, $id, 0, '$opt')");
		pg_query("INSERT INTO action VALUES($newid+1, $id, 1, '$opt')");
	}
	elseif($num == 73) {
		pg_query("INSERT INTO action VALUES($newid, $id, 7, 3)");
        }
        elseif($num == 7964) {
		pg_query("INSERT INTO action VALUES($newid, $id, 7, 964)");
        }
        else {
		pg_query("INSERT INTO action VALUES($newid, $id, $num, '$opt')");
        }
	// Logging actions
	$r = pg_query("SELECT MAX(idlog) FROM action_log");
	if($am = pg_fetch_array($r))
		$newid = $am[0]+1;
	else
		$newid = 1;
	if($num == -1) {
		pg_query("INSERT INTO action_log VALUES($newid, '$auth_user', ".time().", $id, 0, ${a[7]})");
		pg_query("INSERT INTO action_log VALUES($newid+1, '$auth_user', ".time().", $id, 1, ${a[7]})");
	}
        elseif($num == 73) {
                $oldopt = $a[10];
                pg_query("INSERT INTO action_log VALUES($newid, '$auth_user', ".time().", $id, 7, '$oldopt', 3)"); 
        }
        elseif($num == 7964) {
                $oldopt = $a[10];
                pg_query("INSERT INTO action_log VALUES($newid, '$auth_user', ".time().", $id, 7, '$oldopt', 964)");
        }
	else {
		switch($num) {
			case 0:
			case 1: $oldopt = $a[7]; break;
			case 2: $oldopt = $a[4]; break;
			case 3: $oldopt = $a[19]; break;
			default: $oldopt = ''; break;
		}
		pg_query("INSERT INTO action_log VALUES($newid, '$auth_user', ".time().", $id, $num, '$oldopt', '$opt')");
	}
}

function DisplayActions() {
	global $id;

	$r = pg_query("SELECT numaction, option FROM action WHERE idinterface = $id ORDER BY idaction");
	if($a = pg_fetch_array($r)) {
?>
		<h4>Prochaines actions</h4>
		<ul>
<?php
		while($a) {
			switch($a[0]) {
				case 0: $line = "shutdown"; break;
				case 1: $line = "no shutdown"; break;
				case 2: $line = "description ".$a[1]; break;
				case 3: $line = "switchport port-security maximum ".$a[1]; break;
				case 4: $line = "no switchport port-security mac-address sticky ";
					$mac = explode(":", strtolower($a[1]));
					$mac = $mac[0].$mac[1].".".$mac[2].$mac[3].".".$mac[4].$mac[5];
					$line .= $mac; break;
                                case 7: $line = "vlan ".$a[1]; break;
				default: $line = "";
			}
			echo "<li>$line</li>\n";
			$a = pg_fetch_array($r);
		}
?>
		</ul>
<?php
	}
}

function DisplayConfigForm($roles) {
	global $id, $a;
?>
	<table>
	<tr><td>
        <?php
        if($roles['wifi_vlan4']) {
        ?>
	        <form method="post" action="si905.php?mode=conf">
	        <input type="hidden" name="num" value="73"><input type="submit" value="Isoler"></form>
	<?php
        }
        ?>
        </td>
	<td>
                <form method="post" action="si905.php?mode=conf">
                <input type="hidden" name="num" value="7964"><input type="submit" value="R&eacute;int&eacute;grer"></form>
	</td>
	<td>
                <form method="post" action="si905.php?mode=conf">
                <input type="hidden" name="num" value="-1"><input type="submit" value="Shut no shut"></form>
	</td></tr>
	<tr><form method="post" action="si905.php?mode=conf">
		<input type="hidden" name="num" value="2">
		<td>Description</td>
		<td><input type="text" name="opt" value="<?php echo $a[4]; ?>"></td>
		<td><input type="submit" value="Changer"></td>
	</form></tr>
        <?php
        if($roles['wifi_vlan4']) {
        ?>
        <tr><form method="post" action="si905.php?mode=conf">
                <input type="hidden" name="num" value="3">
                <td>Nombre de MACs autoris&eacute;es</td>
                <td><select name="opt">
<?php
		for($i=1; $i<4; $i++) {
			echo "<option ";
			if($a[19] == $i) echo "selected";
			echo ">$i</option>\n";
		}
		if($a[19] > 3)
			echo "<option selected>${a[19]}</option>\n";
?>
		</select></td>
                <td><input type="submit" value="Changer"></td>
        </form></tr>
<?php   }
	// Stickies !
        $r2 = pg_query("SELECT mac FROM fdb WHERE idinterface = $id AND type = 2 and datelast = (SELECT MAX(datelast) FROM fdb)");
        while($a2 = pg_fetch_array($r2)) {
?>
		<tr><form method="post" action="si905.php?mode=conf">
			<input type="hidden" name="num" value="4">
			<input type="hidden" name="opt" value="<?php echo $a2[0]; ?>">
			<td>Sticky</td><td><?php echo $a2[0]; ?></td><td><input type="submit" value="Supprimer">
		</form></tr>
<?php
	}
?>

	</table>
	</blockquote>
<?php
	DisplayActions();
	include "inc/inc.footer.php";
	exit();
}

$types=array(1=>'Other', 6=>'Ethernet CSMA/CD', 9=>'Token ring', 15=>'FDDI', 18=>'DSL', 19=>'DSL', 20=>'ISDN',
	21=>'ISDN', 22=>'S&eacute;rie', 23=>'PPP', 24=>'Loopback', 28=>'SLIP', 32=>'Frame relay', 53=>'Virtual', 71=>'802.11',
	117=>'Ethernet Gigabit', 135=>'VLAN');

$rroom = pg_query("SELECT idroom, name FROM room WHERE idinterface = '$id'");
if($aroom = pg_fetch_array($rroom))
{
?>
	<h3><a href="room.php?id=<?php echo $aroom[0]; ?>">Chambre <?php echo $aroom[1]."</a>"; ?></h3>
<?php
}
?>
	<h3><a href="materiel.php?id=<?php echo $a[0]; ?>"><?php echo $a[1]."</a>/".$a[3]; ?></h3>
	<blockquote class="list">

<?php
	if($mode == "conf")
		DisplayConfigForm($roles);
?>

	<table width="100%">
	<tr><td>

	<table>
	<tr><td><b>ifNumber</b></td><td><?php echo $a[2]; ?></td></tr>
	<tr><td><b>ifAddress</b></td><td><?php echo $a[5]; ?></td></tr>
	<tr><td><b>ifType</b></td><td><?php echo $a[9]." (".$types[$a[9]].")"; ?></td></tr>
	<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
	<tr><td><b>Description</b></td><td><?php echo ($a[4]==""?"Aucune":$a[4]); ?></td></tr>
	<tr><td><b>Etat</b></td><td><?php echo ($a[7]==0?"SHUT":($a[8]==1?"UP":"DOWN")); ?></td></tr>
	<tr><td><b>Vitesse</b></td><td><?php echo $a[6]/1000000; ?>M</td></tr>
<?php
	switch($a[10]) {
		case 0: $vlan = "Ind&eacute;termin&eacute;"; $voice = ""; break;
		case -1: $vlan = "Trunk"; $voice = ""; break;
		default:
			$vlan = $a[10];
                        if($vlan == 3) $vlan .= " ==> En isolement";
			if($a[11] != 0 && $a[11] != 4096) $voice = $a[11];
			else $voice = "";
			break;
	}
?>
	<tr><td><b>VLAN</b></td><td><?php echo $vlan; ?></td></tr>
<?php
	if($vlan == "Trunk")
		echo "<tr><td><b>Native VLAN</b></td><td>${a[12]}</td></tr>\n";
	if($voice != "")
		echo "<tr><td><b>Voice VLAN</b></td><td>$voice</td></tr>\n";
?>
	<tr><td><b>SpanningTree Portfast</b></td><td><?php echo (($a[16]=='t')?"oui":"non"); ?></td></tr>
	</table>
<?php
	if($roles['wifi_vlan4']) {?>
                [<a href="si905.php?mode=conf">Configurer</a>]
<?php   }?>
	</td><td>

	<h4>Port Security</h4>
	<table>
	<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
	<tr><td><b>Activ&eacute;</b></td><td><?php echo (($a[17]=='t')?"oui":"non"); ?></td></tr>
<?php
	if($a[17] == 't') {
		switch($a[18]) {
			case 1: $status = "Secure-up"; break;
			case 2: $status = "Secure-down"; break;
			case 3: $status = "Secure-shutdown"; break;
		}
?>
	<tr><td><b>Etat</b></td><td><?php echo $status; ?></td></tr>
	<tr><td><b>Nb de MAC max</b></td><td><?php echo $a[19]; ?></td></tr>
	<tr><td><b>Nb de MAC actuel</b></td><td><?php echo $a[20]; ?></td></tr>
	<tr><td><b>Nb de violations</b></td><td><?php echo $a[21]; ?></td></tr>
	<tr><td><b>Derni&egrave;re MAC</b></td><td><a href="findmac.php?mac=<?php echo $a[22]; ?>"><?php echo $a[22]; ?></a>&nbsp;&nbsp;(00:00:00:00:00:00 peut &ecirc;tre normal)</td></tr>
	<tr><td><b>Sticky activ&eacute;</b></td><td><?php echo (($a[23]=='t')?"oui":"non"); ?></td></tr>
<?php
	}
?>
	</table>
<?php
        $r = pg_query("SELECT mac, type FROM fdb WHERE idinterface = $id AND type <> 0 and datelast = (SELECT MAX(datelast) FROM fdb)");
        if($a = pg_fetch_array($r)) {
?>
	<h4>Stickies</h4>
	<ul>
<?php
		while($a) {
			echo "<li>".$a[0].($a[1]==1?" (static)":"")."</li>\n";
			$a = pg_fetch_array($r);
		}
?>
	</ul>
<?php
        }
?>
	</td></tr>
	</table>

	</blockquote>

<?php
	$r = pg_query("SELECT MAX(datelast) FROM link");
	$datelast = pg_fetch_array($r);
	$datelast = $datelast[0];
	$r = pg_query("SELECT idmateriel, hostname, dstifname, link.datelast FROM link, materiel
		WHERE idmateriel = iddstmateriel AND idinterface = $id ORDER BY datelast DESC");
	if($a = pg_fetch_array($r)) {
?>
		<h4>Mat&eacute;reil connect&eacute; sur cette interface</h4>
		<ul>
<?php
		while($a) {
			echo "<li>".($a[3]==$datelast?"<b>":"")."<a href=\"materiel.php?id=${a[0]}\">${a[1]}</a> / ${a[2]}";
			echo ($a[3]==$datelast?"</b>":"")." (".date("D d/m/Y H:i", $a[3]).")</li>\n";
			$a = pg_fetch_array($r);
		}
	}
?>
	</ul>
<?php
	DisplayActions();

include "inc/inc.footer.php";
?>

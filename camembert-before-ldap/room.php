<?php
$page_name = "Chambres";
include "inc/roles.php";
include "inc/inc.header.php";
include "inc/libsql.php";

$count_root = 0;
$count_admin = 0;
$count_membre_ca = 0;
$count_tresorier = 0;
$count_all_rooms = 0;
$count_no_cert = 0;
$count_datedeco = 0;
$count_green = 0;
$count_empty = 0;
$count_special_case = 0;

function room_list($bat) {
        global $count_root, $count_admin, $count_membre_ca, $count_tresorier, $count_all_rooms, $count_no_cert, $count_datedeco, $count_green, $count_empty, $count_special_case;
	$rooms = "";
	foreach($bat as $room) {
		$room['class'] = "normal";
		$room['img'] = "";
		$r = pg_query("SELECT idroom, datedeco, certif, iduser, special_case FROM user_pac WHERE idroom = ".$room['idroom']);
		if($a = pg_fetch_array($r)) {
                        $r2 = pg_query("SELECT groupe FROM cam_user c, id i WHERE i.iduser = ".$a['iduser']." AND i.idcam_user = c.iduser"); 
                        if($a2 = pg_fetch_array($r2)) {
                                switch($a2['groupe']) {
                                        case 2:
                                                $room['img'] = "<img src=\"img/root.gif\" class=\"img\">";
                                                $count_root ++;
                                                break;
                                        case 3:
                                                $room['img'] = "<img src=\"img/admin.png\" class=\"img\">";
                                                $count_admin ++;
                                                break;
                                        case 4:
                                                $room['img'] = "<img src=\"img/membre_ca.gif\" class=\"img\">";
                                                $count_membre_ca ++;
                                                break;
                                        case 5:
                                                $room['img'] = "<img src=\"img/tresorier.gif\" class=\"img\">";
                                                $count_tresorier ++;
                                                break;
                                        default:
                                                $room['img'] = "";
                                                break;
                                }
                        }
                        if($a['special_case'] == 't') {
                                $room['special_case'] = "*";
                                $count_special_case ++;
                        }
                        else {
                                $room['special_case'] = "";
                        }
			if(strtotime(date("Y-m", strtotime($a['datedeco']." +1 month"))) < time()) {
				$room['class'] = "red";
                                $count_datedeco ++;
                        }
			else if((date("n") < 9 || date("n") > 10) && $a['certif'] != 't') {
				$room['class'] = "orange";
                                $count_no_cert ++;
                        }
			else {
				$room['class'] = "green";
                                $count_green ++;
                        }
		}
                else {
                        $count_empty ++;       
                }

		switch($room['name'][0]) {
			case 0:
			case '0':
				$floor0[] = $room;
				break;
			case 1:
			case '1':
				$floor1[] = $room;
				break;
			case 2:
			case '2':
				$floor2[] = $room;
				break;
			case 3:
			case '3':
				$floor3[] = $room;
				break;
			case 4:
			case '4':
				$floor4[] = $room;
				break;
			default:
				$floor0[] = $room;
				break;
		}
	}
?>
	<table class="list realtable" cellpadding="1" cellspacing="1">
		<tr><th width="50">rdc</th><th width="50">1er</th><th width="50">2e</th><th width="50">3e</th><th width="50">4e</th></tr>
<?php
	$bline = false;
	for($i=0; $i<max(count($floor0), count($floor1), count($floor2), count($floor3), count($floor4)); $i++) {
		echo "<tr class=\"normal".($bline?"2":"")."\">";
		echo "<td class=\"".$floor0[$i]['class'].($bline?"2":"")." td\"><a class=\"block2\" href=\"?id=".$floor0[$i]['idroom']."\">".$floor0[$i]['name'].($floor0[$i]['special_case']).$floor0[$i]['img']."</a></td>";
		echo "<td class=\"".$floor1[$i]['class'].($bline?"2":"")." td\"><a class=\"block2\" href=\"?id=".$floor1[$i]['idroom']."\">".$floor1[$i]['name'].($floor1[$i]['special_case']).$floor1[$i]['img']."</a></td>";
		echo "<td class=\"".$floor2[$i]['class'].($bline?"2":"")." td\"><a class=\"block2\" href=\"?id=".$floor2[$i]['idroom']."\">".$floor2[$i]['name'].($floor2[$i]['special_case']).$floor2[$i]['img']."</a></td>";
		echo "<td class=\"".$floor3[$i]['class'].($bline?"2":"")." td\"><a class=\"block2\" href=\"?id=".$floor3[$i]['idroom']."\">".$floor3[$i]['name'].($floor3[$i]['special_case']).$floor3[$i]['img']."</a></td>";
		echo "<td class=\"".$floor4[$i]['class'].($bline?"2":"")." td\"><a class=\"block2\" href=\"?id=".$floor4[$i]['idroom']."\">".$floor4[$i]['name'].($floor4[$i]['special_case']).$floor4[$i]['img']."</a></td></tr>\n";
		$bline = !$bline;
	}
?>
	<tr><th>rdc</th><th>1er</th><th>2e</th><th>3e</th><th>4e</th></tr></table>
<?php
}

function DisplayRoomList() {
        global $count_root, $count_admin, $count_membre_ca, $count_tresorier, $count_all_rooms, $count_no_cert, $count_datedeco, $count_green, $count_empty, $count_special_case;
	$r = pg_query("SELECT idroom, name FROM room ORDER BY name");
	$spec = array();
	while($a = pg_fetch_array($r)) {
                $count_all_rooms ++;
		$digit2 = $a['name'][1];
		if(is_numeric($digit2)) {
			if($digit2 < 5)
				$sud[] = $a;
			else
				$nord[] = $a;
		}
		else
			$spec[] = $a;
	}
	$nord = array_merge($nord, $spec);
	echo "<h3>Liste des $count_all_rooms chambres</h3>\n";

	// Tables dans une table, c'est pas beau, comme le PHP et l'HTML
?>
	<table cellpadding="1" cellspacing="1" class="realtable"><tr><th>Sud</th><th>Nord</th></tr>
	<tr><td><?php room_list($sud); ?></td><td><?php room_list($nord); ?></td>
	</tr></table>
	
        <div style="position:absolute; top:220px; margin-left:860px;">
        <table cellpadding="1" cellspacing="1" class="realtable">
                <tr><th colspan="2">L&eacute;gende</th></tr>
                <tr><td width="40" class="green"></td><td>Chambre connect&eacute;e</td><td><?php echo $count_green ; ?></td></tr>
		<tr><td class="red"></td><td>Date de fin de connexion pass&eacute;e</td><td><?php echo $count_datedeco ; ?></td></tr>
		<tr><td class="orange"></td><td>Certificat non rendu</td><td><?php echo $count_no_cert ; ?></td></tr>
		<tr><td class="normal"></td><td>Chambre vide</td><td><?php echo $count_empty ; ?></td></tr>
		<tr><td>*</td><td>Cas particulier</td><td><?php echo $count_special_case ; ?></td></tr>
		<tr><td><img src="img/root.gif" class="img"></td><td>Responsable r&eacute;seau</td><td><?php echo $count_root ; ?></td></tr>
		<tr><td><img src="img/admin.png" class="img"></td><td>Admin</td><td><?php echo $count_admin ; ?></td></tr>
		<tr><td><img src="img/membre_ca.gif" class="img"></td><td>Membre CA</td><td><?php echo $count_membre_ca ; ?></td></tr>
		<tr><td><img src="img/tresorier.gif" class="img"></td><td>Tr&eacute;sorier et pr&eacute;sident</td><td><?php echo $count_tresorier ; ?></td></tr>
        </table>
        </div>
<?php
}

function DisplayRoomInfo($id) {
	global $roles;

	$r = pg_query("SELECT idroom, idinterface, name FROM room WHERE idroom = ".$id);
	if($a = pg_fetch_array($r)) {
		echo "<h3>Chambre ".$a['name']."</h3>\n<blockquote class=\"list\">";
		$r = pg_query("SELECT iduser, nom, prenom FROM user_pac WHERE idroom = ".$a['idroom']);
		if($a2 = pg_fetch_array($r))
			echo "<p>Occup&eacute;e par <a href=\"user.php?id=".$a2['iduser']."\">".$a2['nom']." ".$a2['prenom']."</a>";
		else
			echo "<p>Vide";
		if($roles['inscription'])
			echo "<br />\n[<a href=\"user.php?act=new&room=$id\">Nouveau</a>]";
		echo "</p>";

		return true;
	}
	else {
		echo "<p class=\"error\">Erreur : cet id n'existe pas</p>\n";
		return false;
	}
}

function DisplayRoomInterface($id) {
	global $roles;

	$r = pg_query("SELECT idroom, idinterface, name FROM room WHERE idroom = ".$id);
	$a = pg_fetch_array($r);
	$r = pg_query("SELECT ifname, i.idmateriel AS idm, hostname FROM interface i, materiel m WHERE i.idmateriel = m.idmateriel AND idinterface = ".$a['idinterface']);
	$a2 = pg_fetch_array($r);
	echo "<p>Sur l'interface <a href=\"materiel.php?id=".$a2['idm']."\">".$a2['hostname']."</a> / ";
	echo "<a href=\"interface.php?id=".$a['idinterface']."\">".$a2['ifname']."</a>";
	if($roles['root'])
		echo " - [<a href=\"?id=".$a['idroom']."&change=1\">Changer</a>]";
	if($roles['inscription'])
		echo " - [<a href=\"?id=".$a['idroom']."&move=1\">D&eacute;m&eacute;nager</a>]";
	echo "</p>\n";
}

function DisplayInterfaceForm($id) {
	global $roles;

	if(!$roles['root'])
		return;

	$r = pg_query("SELECT idroom, idinterface, name FROM room WHERE idroom = ".$id);
	$a = pg_fetch_array($r);
	if(isset($_GET['idint'])) {
		pg_query("UPDATE room SET idinterface = ".$_GET['idint']." WHERE idroom = ".$a['idroom']);
		DisplayRoomInterface($id);
	}
	else {
		$r = pg_query("SELECT idinterface, ifname, m.idmateriel AS idm, hostname
			FROM interface i, materiel m
			WHERE i.idmateriel = m.idmateriel 
			ORDER BY m.idmateriel, ifnumber");
		$last_id = 0;
		echo "<p><form method=\"get\" action=\"\">Sur l'interface <input type=\"hidden\" name=\"id\" value=\"".$a['idroom']."\" />";
		echo "<input type=\"hidden\" name=\"change\" value=\"1\" /><select name=\"idint\">\n";
		while($a2 = pg_fetch_array($r)) {
			if($a2['idm'] != $last_id) {
				if($last_id > 0)
					echo "</optgroup>";
				echo "<optgroup label=\"".$a2['hostname']."\">";
				$last_id = $a2['idm'];
			}
			echo "<option value=\"".$a2['idinterface']."\"";
			if($a2['idinterface'] == $a['idinterface'])
				echo " selected=\"selected\"";
			echo ">".$a2['ifname']."</option>\n";
		}
		echo "</optgroup><input type=\"submit\" /></form></p>\n";
	}
}

function DisplayRoomForm($id) {
	global $roles;
        global $auth_user;

	if(!$roles['inscription'])
		return;

	$r = pg_query("SELECT idroom, idinterface, name FROM room WHERE idroom = ".$id);
	$a = pg_fetch_array($r);
	if(isset($_GET['idr'])) {
	        RemoveUser($_GET['idr']);	
                pg_query("UPDATE user_pac SET idroom = ".$_GET['idr']." WHERE idroom = ".$a['idroom']);
		// récupérer les infos de l'interface de idroom
                $r2 = pg_query("SELECT ifdescription, portsecmaxmac FROM interface WHERE idinterface = (SELECT idinterface FROM room WHERE idroom = ".$a['idroom'].")");
                $a2 = pg_fetch_array($r2);
                // effacer l'interface de idroom (mais pas les ordi ni l'utilisateur)
                RemoveInterface($a['idroom']);
                // configurer l'interface de idr
                $r3 = pg_query("SELECT iduser FROM user_pac WHERE idroom = ".$_GET['idr']);
                $a3 = pg_fetch_array($r3);
                UpdateInterfaceForUser($a3['iduser']);                
                // configurer a2 cf $mode == "conf" au début de interface.php
                $r = pg_query("SELECT MAX(idaction) FROM action");
                if($am = pg_fetch_array($r))
                        $newid = $am[0]+1;
                else
                        $newid = 1;
                $r4 = pg_query("SELECT idinterface FROM room WHERE idroom = ".$_GET['idr']); 
                $a4 = pg_fetch_array($r4);
                $idint = $a4['idinterface'];
                $opt = $a2['portsecmaxmac'];
                pg_query("INSERT INTO action VALUES($newid, $idint, 3, $opt)");
                // logging_action "déménagement"
                $r = pg_query("SELECT MAX(idlog) FROM action_log");
                if($am = pg_fetch_array($r))
                        $newid = $am[0]+1;
                else
                        $newid = 1;
                $oldopt = $a2['ifdescription'];
                $r = pg_query("SELECT ifdescription FROM interface WHERE idinterface = ".$idint);
                $a = pg_fetch_array($r);
                $opt = $a['ifdescription'];
                pg_query("INSERT INTO action_log VALUES($newid, '$auth_user', ".time().", $idint, 6, '$oldopt', '$opt')");
                
                echo "<script>location=\"room.php?id=".$_GET['idr']."\"</script>";
	}
	else {
		$r = pg_query("SELECT name, idroom FROM room ORDER BY idroom ASC");
		$last_id = 0;
		echo "<p><form method=\"get\" action=\"\">Vers la chambre <input type=\"hidden\" name=\"id\" value=\"".$a['idroom']."\" />";
		echo "<input type=\"hidden\" name=\"move\" value=\"1\" /><select name=\"idr\">\n";
		while($a2 = pg_fetch_array($r)) {
			echo "<option value=\"".$a2['idroom']."\"";
			if($a2['idroom'] == $a['idroom'])
				echo " selected=\"selected\"";
			echo ">".$a2['name']."</option>\n";
		}
		echo "<input type=\"submit\" /></form></p>\n";
	}
}

if(isset($_GET['id'])) {
	$ok = DisplayRoomInfo($_GET['id']);
	if($ok) {
		if(isset($_GET['change']) && $roles['root'])
			DisplayInterfaceForm($_GET['id']);
                elseif(isset($_GET['move']) && $roles['inscription'])
                        DisplayRoomForm($_GET['id']);
		else
			DisplayRoomInterface($_GET['id']);
		echo "</blockquote>\n"; //ouvert dans DisplayRoomInfo
	}
}
DisplayRoomList();

include "inc/inc.footer.php";
?>

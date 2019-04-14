<?php
$page_name = "Utilisateurs";
include "inc/roles.php";
//include "inc/inc.db.php"; ConnectDB();

if(!$roles['roles']) {
	include "denied.php";
	exit();
}

include "inc/inc.header.php";

if(isset($_GET['id'])) {
	$r = pg_query("SELECT iduser, login, groupe FROM cam_user WHERE iduser = ".$_GET['id']);
	if($a = pg_fetch_array($r)) {
		if(isset($_GET['del']) && $_GET['del'] == 1) {
			pg_query("DELETE FROM cam_user WHERE iduser = ".$a['iduser']);
			pg_query("DELETE FROM id WHERE idcam_user = ".$a['iduser']);
			echo "<p>User <b>".$a['login']."</b> supprim&eacute;.</p>\n";
		}
		else {
			if(isset($_GET['group'])) {
				pg_query("UPDATE cam_user SET groupe = ".$_GET['group']." WHERE iduser = ".$a['iduser']);
				$a['groupe'] = $_GET['group'];
                                // insertion dans la trésorerie
                                $roles2 = array();
                                $r = pg_query("SELECT login FROM cam_user WHERE iduser = ".$a['iduser']);
                                $login = pg_fetch_array($r);
                                get_roles($login[0], $roles2);
                                if ($roles2['inscription']) {
                                        $r = pg_query("SELECT * FROM account WHERE iduser = ".$a['iduser']);
                                        // si n'existe pas déjà dans la trésorerie
                                        if (! $a2 = pg_fetch_array($r)) {
                                                $r = pg_query("SELECT iduser FROM id WHERE idcam_user = ".$a['iduser']);
                                                if ($a3 = pg_fetch_array($r)) {
                                                        $r = pg_query("SELECT nom, prenom FROM user_pac WHERE iduser = ".$a3['iduser']);
                                                        $a4 = pg_fetch_array($r);
                                                        pg_query("INSERT INTO account(iduser, nom, prenom, amount) VALUES('".$a['iduser']."', '".$a4['nom']."', '".$a4['prenom']."', 0)");
                                                }
                                                // else wtf !?
                                        }
                                }
			}

			echo "<h3>".$a['login']."</h3>\n";
			echo "<blockquote><p><form method=\"get\" action=\"\">Groupe : <select name=\"group\">\n";
			$r = pg_query("SELECT idgroupe, name FROM groupe ORDER BY idgroupe");
			while($a2 = pg_fetch_array($r)) {
				echo "<option value=\"".$a2['idgroupe']."\" ";
				if($a2['idgroupe'] == $a['groupe'])
					echo "selected=\"selected\"";
				echo ">".$a2['name']."</option>\n";
			}
			echo "</select><input type=\"hidden\" name=\"id\" value=\"".$a['iduser']."\" /><input type=\"submit\" /></form></p></blockquote>\n";
		}
	}
	else
		echo "<p class=\"error\">Cet id n'existe pas</p>\n";
}

?>
	<h3>Liste des utilisateurs</h3>
	<blockquote>
<?php
$r = pg_query("SELECT iduser, login, name FROM cam_user u, groupe g WHERE groupe = idgroupe ORDER BY iduser");
if($a = pg_fetch_array($r)) {
?>
	<table class="list" cellpadding="1" cellspacing="1"><tr><th width="20">id</th><th width="100">login</th><th width="30">chambre</th><th width="50">groupe</th><th width="30">suppr</th></tr>
<?php
	$bline = false;
	while($a) {
                $r2 = pg_query("SELECT r.idroom, r.name FROM user_pac u, id i, room r WHERE i.idcam_user = ".$a['iduser']." AND u.iduser = i.iduser AND u.idroom = r.idroom");
                $a2 = pg_fetch_array($r2);
		echo "<tr class=\"normal"; if($bline) echo "2";
		echo "\"><td>".$a['iduser']."</td><td><a href=\"?id=".$a['iduser']."\">".$a['login']."</a></td><td><a href=\"room.php?id=${a2['idroom']}\">".$a2['name']."</a></td><td>".$a['name']."</td>";
		echo "<td><a href=\"?id=".$a['iduser']."&del=1\">suppr</a></td></tr>\n";
		$a = pg_fetch_array($r);
		$bline = !$bline;
	}
	echo "</table>\n";
}
?>
	</blockquote>
<?php
include "inc/inc.footer.php";
?>

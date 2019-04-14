<?php
setlocale(LC_CTYPE, 'fr_FR.utf8');
function RemoveInterface($idroom) {
        $r = pg_query("SELECT name, idinterface FROM room WHERE idroom = $idroom");
        if($a = pg_fetch_array($r)) {
                $r = pg_query("SELECT MAX(idaction) FROM action");
                $a2 = pg_fetch_array($r);
                if($a2)
                        $newid = $a2[0]+1;
                else
                        $newid = 1;

                // vérifier que cette ligne est bien exécutée en cas de déménagement
                pg_query("INSERT INTO action(idaction, idinterface, numaction) VALUES($newid, ".$a['idinterface'].", 0)");
                $newid++;
                pg_query("INSERT INTO action VALUES($newid, ".$a['idinterface'].", 7, 3)");
                $newid++;
                pg_query("INSERT INTO action(idaction, idinterface, numaction, option) VALUES($newid, ".$a['idinterface'].", 2, 'Chambre ".$a['name']." VIDE')");
                $newid++;
                $r = pg_query("SELECT mac FROM fdb WHERE idinterface = ".$a['idinterface']." AND type = 2 AND datelast = (SELECT MAX(datelast) FROM fdb)");
                while($a3 = pg_fetch_array($r)) {
                        pg_query("INSERT INTO action(idaction, idinterface, numaction, option) VALUES($newid, ".$a['idinterface'].", 4, '".$a3['mac']."')");
                        $newid++;
                }
                pg_query("INSERT INTO action(idaction, idinterface, numaction, option) VALUES($newid, ".$a['idinterface'].", 3, '10')");
		$newid++;
                pg_query("INSERT INTO action(idaction, idinterface, numaction) VALUES($newid, ".$a['idinterface'].", 1)");
        }
}

function RemoveUser($idroom) {
        RemoveInterface($idroom);
        $r = pg_query("SELECT iduser, nom, prenom FROM user_pac WHERE idroom = $idroom");
        if($a = pg_fetch_array($r)) {
                pg_query("UPDATE ip_user SET free = '1' WHERE ip IN (SELECT ip FROM computer WHERE iduser = ".$a['iduser'].")");
                pg_query("DELETE FROM computer WHERE iduser = ".$a['iduser']);
                pg_query("DELETE FROM user_pac WHERE iduser = ".$a['iduser']);
                pg_query("DELETE FROM id WHERE iduser = ".$a['iduser']);
                echo "<p>Utilisateur <em>".$a['nom']." ".$a['prenom']."</em> supprim&eacute; (ainsi que toutes les machines associ&eacute;es)</p>\n";
        }
}

function UpdateInterfaceForUser($iduser) {
	$r = pg_query("SELECT nom, prenom, datedeco, certif, name, idinterface FROM room r, user_pac u WHERE u.idroom = r.idroom AND iduser = $iduser");
	$a = pg_fetch_array($r);

	$r = pg_query("SELECT MAX(idaction) FROM action");
	if($a2 = pg_fetch_array($r))
		$newid = $a2[0]+1;
	else
		$newid = 1;

	$ddeco = date("M Y", strtotime($a['datedeco']));
	$desc = iconv("UTF-8", "ASCII//TRANSLIT", "Chambre ".$a['name']." ".$a['nom']." ".$a['prenom']." $ddeco");
	if((date("n") < 9 || date("n") > 10) && $a['certif'] != 't') { // TODO: vérifier la date de deco
		$desc .= " CERTIF";
                pg_query("INSERT INTO action VALUES($newid, ".$a['idinterface'].", 7, 3)");
	}
	else {
                pg_query("INSERT INTO action VALUES($newid, ".$a['idinterface'].", 7, 964)");
                $newid++;
		pg_query("INSERT INTO action(idaction, idinterface, numaction) VALUES($newid, ".$a['idinterface'].", 1)");
	}
        $newid++;
	pg_query("INSERT INTO action(idaction, idinterface, numaction, option) VALUES($newid, ".$a['idinterface'].", 2, '$desc')");
}

?>

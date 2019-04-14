<?php
$page_name = "Utilisateur";
include "inc/roles.php";
include "inc/inc.header.php";
include "inc/libsql.php";
include "inc/libdate.php";

// TODO pour encoder en utf-8 $s si ce n'est pas déjà le cas : mb_detect_encoding($s, "UTF-8") == "UTF-8" ? : $s = utf8_encode($s);
// Ouais bon en fait c'est pas que du displaying
function DisplayUser($id, $act, $idroom) {
        global $roles;

        if(!$roles['inscription'])
                $act = ""; // Bourrin, et cause une erreur quand on fait new, mais ça bloque alors osef

        if($act != "new") {
                $r = pg_query("SELECT iduser, nom, prenom, datedeco, mail, idroom, certif, special_case, comment FROM user_pac WHERE iduser = ".$id);
                $a = pg_fetch_array($r);
                if(!$a) {
                        echo "<p class=\"error\">Erreur: cet identifiant n'existe pas</p>\n";
                        return false;
                }
                $rroom_name = pg_query("SELECT name FROM room WHERE idroom = '$a[5]'"); 
                $aroom_name = pg_fetch_array($rroom_name);

                echo "<h3><a href=\"room.php?id=" . $a[5] . "\">Chambre " . $aroom_name[0] . "</a></h3>";
                echo "<h3>Utilisateur</h3>\n";
                echo "<p>Les modifications effectuées ici prendront effet dans un délai maximum de 1 heure.</p>\n";
        }
        else {
                $r = pg_query("SELECT iduser, nom, prenom FROM user_pac WHERE idroom = $idroom");
                // -- Supression de l'ancien user
                if($a = pg_fetch_array($r)) {
                        if(!isset($_GET['confirm']) || $_GET['confirm'] != "1") {
                                echo "<p>Ajouter un utilisateur sur cette chambre ($idroom) supprimera l'ancien (".$a['nom']." ".$a['prenom'].").<br />\n";
                                echo "Voulez vous continuer ? [<a href=\"?room=$idroom&act=new&confirm=1\">Oui</a>] - [<a href=\"?id=".$a['iduser']."\">Non</a>]</p>\n";
                                return false;
                        }
                        else {
                                RemoveUser($idroom);	
                        }
                }
                // --

                $a['nom'] = $a['prenom'] = $a['datedeco'] = $a['mail'] = $a['certif'] = "";
                $a['idroom'] = $idroom;
        }

        if($act != "") {
                echo "<form action=\"?act=$act&room=$idroom";
                if($act == "edit")
                        echo "&id=$id";
                echo "\" method=\"post\"><input type=\"hidden\" name=\"idroom\" value=\"".$a['idroom']."\" />";
        }
        echo "<table><tr><td>Nom</td><td>";
        if($act != "")
                echo "<input name=\"name\" type=\"text\" value=\"";
        echo $a['nom'];
        if($act != "")
                echo "\" />";
        echo "</td></tr>\n";

        echo "<tr><td>Pr&eacute;nom</td><td>";
        if($act != "")
                echo "<input name=\"firstname\" type=\"text\" value=\"";
        echo $a['prenom'];
        if($act != "")
                echo "\" />";
        echo "</td></tr>\n";

        echo "<tr><td>Mois de fin de connexion</td><td>";
        if($act != "") {
                if($act == "edit") {
                        $mdeco = get_month($a['datedeco']);
                        $ydeco = get_year($a['datedeco']);                   
                }
                else
                        $mdeco = 0;
                $month = date("n");
                $year = date("Y");
                if ($roles['inscription_adv'] || ($act == "new") || ($mdeco == 9 && (($month == 9)||($month == 10)))) {
                        echo "<select name=\"datedeco\">";
                        if($month < 9)
                                for($i=$month; $i<=9; $i++) {
                                        echo "<option value=\"".$year."-".$i."-28\"";
                                        if($mdeco == $i)
                                                echo " selected=\"selected\"";
                                        echo ">".date("F", strtotime("1970/$i/01"))."</option>";
                                }
                        else {
                                echo "<optgroup label=\"".date("Y")."\">";
                                for($i=$month; $i<=12; $i++) {
                                        echo "<option value=\"".$year."-".$i."-28\"";
                                        if($mdeco == $i)
                                                echo " selected=\"selected\"";
                                        echo ">".date("F", strtotime("1970/$i/01"))."</option>";
                                }
                                echo "</optgroup>\n<optgroup label=\"".($year + 1)."\">";
                                for($i=1; $i<=9; $i++) {
                                        echo "<option value=\"".($year + 1)."-".$i."-28\"";
                                        if($mdeco == $i)
                                                echo " selected=\"selected\"";
                                        echo ">".date("F", strtotime("1970/$i/01"))."</option>";
                                }
                                echo "</optgroup>";
                        }
                        echo "</select>\n";
                }
                else {
                        $deco = (strtotime(date("Y-m", strtotime($a['datedeco']." +1 month"))) < time());
                        echo ($deco?"<strong>":"").date("F Y", strtotime($a['datedeco'])).($deco?"</strong>":"");
                        echo "<input type=\"hidden\" name=\"datedeco\" value=\"".$a['datedeco']."\" />";
                }
                if(($act == "edit") && ($mdeco != 9) && (CompareDate($a['datedeco'], StartDate()) == 2)) {
                        echo "<tr><td>&Eacute;tendre &agrave; l'ann&eacute;e ?</td><td>";
                        echo "<input name=\"extend\" type=\"checkbox\" value=\"checked\" ";
                        echo "/>";
                }
                echo "</td></tr>";
        }
        else {
                $deco = (strtotime(date("Y-m", strtotime($a['datedeco']." +1 month"))) < time());
                echo ($deco?"<strong>":"").date("F Y", strtotime($a['datedeco'])).($deco?"<strong>":"");
        }
        echo "</td></tr>\n";

        echo "<tr><td>eMail</td><td>";
        if($act != "")
                echo "<input name=\"mail\" type=\"text\" value=\"";
        if ($roles['inscription']) {
                echo $a['mail'];}
        if($act != "")
                echo "\" />";
        echo "</td></tr>\n";

        echo "<tr><td>Chambre</td><td>";
        $r = pg_query("SELECT name FROM room WHERE idroom = ".$a['idroom']);
        $a2 = pg_fetch_array($r);
        echo $a2['name'];
        echo "</td></tr>\n";

        echo "<tr><td>Certificat ?</td><td>";
        if($act != "") {
                echo "<input name=\"certif\" type=\"checkbox\" ";
                if($act == "edit" && $a['certif'] == "t")
                        echo "checked=\"checked\" ";
                echo "/>";
        }
        else if($a['certif'] == "t")
                echo "Oui";
        else
                echo "<strong>Non</strong>";
        echo "</td></tr>";
        
        echo "<tr><td>Cas particulier ?</td><td>";
        if($act != "" && $roles['inscription_adv']) {
                echo "<input name=\"special_case\" type=\"checkbox\" ";
                if($act == "edit" && $a['special_case'] == "t")
                        echo "checked=\"checked\" ";
                echo "/>";
        }
        else if($a['special_case'] == "t")
                echo "<strong>Oui</strong>";
        else
                echo "Non";
        echo "</td></tr>";
        echo "<tr><td>Commentaire</td><td>";
        if($act != "" && $roles['inscription_adv']) {
                echo "<input name=\"comment\" type=\"text\" value=\"".$a['comment']."\"";
                echo "/>";
        }
        else if($roles['inscription_adv'])
                echo $a['comment'];
        echo "</td></tr>";
        echo "</table>\n";

        if($act == "" && $roles['inscription'])
                echo "<p>[<a href=\"?id=$id&act=edit\">Corriger</a>]</p>\n";
        else if($act != "")
                echo "<p><input type=\"submit\" /></form>\n";

        return ($act != "new");
}

function ValidateForm() {
        global $roles;

        if(!$roles['inscription'])
                return false;

        if(!isset($_POST['name'])||$_POST['name'] == "" || !isset($_POST['firstname'])||$_POST['firstname'] == "" || !isset($_POST['datedeco'])||$_POST['datedeco'] == "" || 
           (isset($_POST['mail']) && $_POST['mail'] != "" && preg_match("/^[a-z0-9_.-]+@[a-z0-9_.-]+\.[a-z]{2,4}$/",$_POST['mail']) == 0)) {
                echo "<p class=\"error\">Erreur dans le formulaire</p>\n";
                DisplayUser($_GET['id'], $_GET['act'], $_GET['room']);
                return false;
        }

        $_POST['name'] = str_replace("'", " ", $_POST['name']);
        $_POST['firstname'] = str_replace("'", " ", $_POST['firstname']);
        if ($_POST['extend'] == "checked") {
                $datedeco = (get_year(StartDate())+1)."-9-28";
        }
        else {
                        $datedeco = $_POST['datedeco'];
        }
        if($_POST['certif'] == "1" || $_POST['certif'] == "on" || $_POST['certif'] == "checked")
                $certif = 1;
        else
                $certif = 0;
        if(!isset($_POST['mail']) || $_POST['mail'] == "")
                $mail = "NULL";
        else
                $mail = "'".$_POST['mail']."'";
        if($_POST['special_case'] == "1" || $_POST['special_case'] == "on" || $_POST['special_case'] == "checked") 
                {$special_case = 1;}
        else {$special_case = 0;}
       
        if(isset($_POST['comment'])) 
                {$comment = $_POST['comment'];}
        else {$comment = "";}

        if($_GET['id']) {
                // needed for logging_actions
                $r = pg_query("SELECT nom, prenom, datedeco, certif FROM user_pac WHERE idroom = ".$_POST['idroom']);
                $am = pg_fetch_array($r);
                $edit = "edit";
                if (new DateTime($am[2]) != new DateTime($datedeco)) {
                        $edit = "new";
                        if(! isset($_POST['confirm_amount'])) {
                                confirm_amount($am[2]);
                                return false; 
                        }
                }
                if($am[3] == t)
                        $cert = 'cert';
                else
                        $cert = 'nocert';

                $oldopt = $am[0] . ' ' . $am[1] . ' ' . $am[2] . ' ' . $cert;

                // Update
                pg_query("UPDATE user_pac SET nom = '".strtoupper($_POST['name'])."', prenom = '".$_POST['firstname']."', datedeco = '$datedeco', mail = $mail, certif = '$certif', special_case = '$special_case', comment = '$comment' WHERE iduser = ".$_GET['id']);
                $r = pg_query("SELECT datedeco FROM user_pac WHERE iduser = ".$_GET['id']);
                $datedeco_db = pg_fetch_array($r);
                logging_actions($oldopt, $certif, $datedeco_db[0], $_POST['name'], $_POST['firstname'], $_GET['id'], $edit);	
                return $_GET['id'];
        }
        else {
                // Insert
                if(! isset($_POST['confirm_amount'])) {
                        confirm_amount("0");
                        return false;
                }
                $r = pg_query("SELECT MAX(iduser) FROM user_pac");
                if($a = pg_fetch_array($r))
                        $newid = $a[0]+1;
                else
                        $newid = 1;

                pg_query("INSERT INTO user_pac(iduser, nom, prenom, datedeco, mail, certif, idroom)
                         VALUES($newid, '".strtoupper($_POST['name'])."', '".$_POST['firstname']."', '$datedeco', $mail, '$certif', ".$_POST['idroom'].")");
                $oldopt = " ";
                logging_actions($oldopt, $certif, $datedeco, $_POST['name'], $_POST['firstname'], $newid, "new");
                return $newid;
        }
}

function SellCable() {
        global $roles;
        $amount = 2;
        if(!$roles['inscription'])
                return false;

        if(! isset($_POST['confirm_amount'])) {
                if(AreAccountsClosed()) {
                         echo "Impossible : trésorerie de l'année précédente non clôturée.";
                         return false;
                }
                echo "<h4>Vous devez faire payer '$amount'&euro;.</h4> <p>Attention, si vous confirmez, cet argent vous sera demand&eacute;. Merci de remettre l'argent r&eacute;guli&egrave;rement aux tr&eacute;soriers en pr&eacute;cisant absolument votre nom.</p>";
                echo "<form action=\"?id=".$_GET['id'];
                echo "\" method=\"post\">";
                echo "<input type=\"hidden\" name=\"cable\" value=\"1\" />";
                echo "<input type=\"hidden\" name=\"confirm_amount\" value=\"$amount\" />";
                echo "<input type=\"submit\" value=\"Confirmer\" /></form>";
                echo "<form action=\"?id=".$_GET['id'];
                echo "\" method=\"post\">";
                echo "<input type=\"submit\" value=\"Annuler\" /></form>\n"; 
                return false; 
        }
        UpdateAccount($amount);
        logging_actions(" ", " ", " ", " ", " ", $_GET['id'], "cable");	
        return true;
}

function UpdateAccount($amount) {
        global $auth_user; 
        $r = pg_query("SELECT iduser FROM cam_user WHERE login = '$auth_user'");
        $am = pg_fetch_array($r);
        $idcam_user = $am[0];
        $r = pg_query("UPDATE account SET amount=amount+'$amount' WHERE iduser='$idcam_user'");
}

// calcule le nombre de mois à faire payer quelle que soit la situation
function months_to_pay($olddatedeco, $newdatedeco) {
        $startdate=date('Y-n-j');
        if($olddatedeco != "0") {
                if(LowestDate($olddatedeco,$startdate) == $startdate) {
                        $startdate=$olddatedeco;
                }
        }
        $year = get_year($newdatedeco);
        if ($year > get_year($startdate)) {
                        $months = 12 - get_month($startdate) + get_month($newdatedeco) + 1;
                }
                else 
                        $months = get_month($newdatedeco) - get_month($startdate) + 1;
                // si la personne est déjà déconnectée, le 20 du mois est passé et au moins 2 mois sont demandés alors le premier mois n'est pas facturé
                if (($startdate != $olddatedeco) && (date(j) > 20) && ($months > 1))
                        $months--;
        return $months;
}
function AreAccountsClosed() {
        $r = pg_query("SELECT MAX(year) FROM money");
        if($a = pg_fetch_array($r)) {
                if(isset($a[0]) && CompareDate($a[0], StartDate()) != 0) {
                        $r = pg_query("SELECT SUM(amount) FROM account");
                        if($a = pg_fetch_array($r)) {
                                if($a[0] != 0) {
                                        return true;
                                }
                                else {
                                        $r = pg_query("INSERT INTO money(year, amount) VALUES('".StartDate()."', '0')");
                                }
                        }
                }
                elseif(! isset($a[0])) {
                        $r = pg_query("INSERT INTO money(year, amount) VALUES('".StartDate()."', '0')");
                }
        }
        return false;
}

function confirm_amount($olddatedeco) {
        $months = 0;
        $amount = 0;
        $new = $_GET['act'];
        if(AreAccountsClosed()) {
                echo "Impossible d'inscrire : trésorerie de l'année précédente non clôturée.";
                return false;
        }
        if($olddatedeco != "0") {
                // pour septembre, vérifier dans les logs que l'inscription n'a pas eu lieu cette année (paiement mensuel)
                if(LowestDate($olddatedeco,StartDate()) == $olddatedeco) {
                       $new = "new";
                }
        }
        $newdatedeco=$_POST['datedeco'];
        if($new == "new")
        {
                $months=months_to_pay("0", $newdatedeco);
                $amount = min($months * 6, 50);
        }
        else { 
                if ($_POST['extend'] == "checked") {
                        $newdatedeco=(get_year(StartDate())+1)."-9-28";
                        $r = pg_query("SELECT MAX(idlog) FROM action_log WHERE iduser = ".$_GET['id']." AND numaction = 5");
                        $am = pg_fetch_array($r);
                        if ($am[0] != "") {
                                $r = pg_query("SELECT amount FROM action_log WHERE idlog = ".$am[0]);
                                $am = pg_fetch_array($r);
                                if ($am[0] != "N/A") {
                                        if (! strstr($am[0], 'tresorier')) {
                                                $months=months_to_pay($olddatedeco, $newdatedeco);
                                                $amount = min(50 - intval($am[0]),$months*6);
                                        }
                                        else {
                                                echo "Erreur : il s'agit d'un cas particulier, merci de demander l'aide du tr&eacute;sorier.";
                                                return false;}
                                }
                                else { 
                                        echo "Erreur : inscription introuvable dans les logs.";
                                        return false;}
                        }
                        else { 
                                echo "Erreur : inscription introuvable dans les logs.";
                                return false;}

                }
        }
        if(($new == "new") || ($_POST['extend'] == "checked")) {
                echo "<h4>Vous devez faire payer '$amount'&euro;.</h4> <p>Attention, si vous confirmez, cet argent vous sera demand&eacute;. Merci de remettre l'argent r&eacute;guli&egrave;rement aux tr&eacute;soriers en pr&eacute;cisant absolument votre nom.</p>";
        }
        else {
                echo "<p>Montant ?</p>";
        }
        echo "<form action=\"?act=".$_GET['act']."&room=".$_GET['room'];
        if($_GET['act'] == "edit")
                echo "&id=".$_GET['id'];
        echo "\" method=\"post\"><input type=\"hidden\" name=\"idroom\" value=\"".$_POST['idroom']."\" />";
        echo "<input type=\"hidden\" name=\"name\" value=\"".$_POST['name']."\" />";
        echo "<input type=\"hidden\" name=\"firstname\" value=\"".$_POST['firstname']."\" />";
        echo "<input type=\"hidden\" name=\"datedeco\" value=\"".$newdatedeco."\" />";
        echo "<input type=\"hidden\" name=\"mail\" value=\"".$_POST['mail']."\" />";
        echo "<input type=\"hidden\" name=\"certif\" value=\"".$_POST['certif']."\" />";
        if(($new == "new") || ($_POST['extend'] == "checked")) {
                echo "<input type=\"hidden\" name=\"confirm_amount\" value=\"$amount\" />";
                echo "<input type=\"submit\" value=\"Confirmer\" /></form>";
        }
        else {
                echo "<input type=\"hidden\" name=\"tresorier\" value=\"1\" />";
                echo "<input type=\"text\" name=\"confirm_amount\" />";
                echo "<input type=\"submit\" value=\"Valider\" /></form>";
        }
        echo "<form action=\"?act=".$_GET['act'];        
        if($_GET['act'] == "edit")
                echo "&id=".$_GET['id'];
        else 
                echo "&room=".$_GET['room'];
        echo "\" method=\"post\">";
        echo "<input type=\"submit\" value=\"Annuler\" /></form>\n";       
}
function logging_actions($oldopt, $certif, $datedeco, $name, $firstname, $iduser, $act) {
        global $auth_user;
        if(isset($_POST['idroom'])) {
                $r = pg_query("SELECT idinterface FROM room WHERE idroom = ".$_POST['idroom']);	
                if($am = pg_fetch_array($r)) {
                        $id = $am[0];
                }
        }
        $r = pg_query("SELECT MAX(idlog) FROM action_log");
        if($am = pg_fetch_array($r))
                $newid = $am[0]+1;
        else
                $newid = 1;
        if($certif)
                $cert = 'cert';
        else
                $cert = 'nocert';

        $opt = $name . ' ' .$firstname . ' ' . $datedeco . ' ' . $cert;
        $amount = "N/A";
        if ($act=="edit") {
                $num = 8;
        }
        elseif ($act=="new") {
                $num = 5;
                $amount = $_POST['confirm_amount'];
                UpdateAccount($amount);
                if (isset($_POST['tresorier'])) {
                        $amount .= " (tresorier)";
                }
        }
        elseif ($act=="cable") {
                $num=9;
                $r = pg_query("SELECT nom, prenom, datedeco, certif, idroom FROM user_pac WHERE iduser = $iduser");
                $a = pg_fetch_array($r);
                if($a[3])
                        $cert = 'cert';
                else
                        $cert = 'nocert';
                $opt = $a[0] . ' ' .$a[1] . ' ' . $a[2] . ' ' . $cert;
                $r = pg_query("SELECT idinterface FROM room WHERE idroom = ".$a[4]);	
                if($am = pg_fetch_array($r)) {
                        $id = $am[0];
                }
                $amount = $_POST['confirm_amount'];
        }
        $r = pg_query("INSERT INTO action_log VALUES($newid, '$auth_user', ".time().", $id, $num, '$oldopt', '$opt', '$iduser', '$amount')");
}

function DisplayComputers($id) {
        global $roles;

        echo "<h3>Liste des machines</h3>\n";
        $r = pg_query("SELECT idcomp, name, mac, ip FROM computer WHERE iduser = $id");
        $n = pg_num_rows($r);

        if($n > 0) {
                echo "<ul>";
                while($a = pg_fetch_array($r)) {
                        echo "<li>".$a['name']." - ".strtoupper($a['mac'])." - ".$a['ip']." -";
                        if($roles['edit_comp'])
                                echo " [<a href=\"comp.php?id=".$a['idcomp']."\">Changer</a>]";
                        if($roles['inscription'])
                                echo " [<a href=\"comp.php?del=".$a['idcomp']."\">Supprimer</a>]";
                        echo "</li>\n";
                }
                echo "</ul>\n";
        }
        else
                echo "<p>Aucune machine</p>\n";

        if($n < 3 && $roles['inscription'])
                echo "<p>[<a href=\"comp.php?user=$id\">Ajouter</a>]</p>\n";
}

function DisplayEthernetCable() {
        global $roles;
        if($roles['inscription']) {
                echo "<form action=\"?id=".$_GET['id']."\" method=\"post\">";
                echo "<input type=\"hidden\" name=\"cable\" value=\"1\" />";
                echo "<input type=\"submit\" value=\"Vendre un cable Ethernet\" />";
                echo "</form>\n<br>";}
}

function DisplayRegistration($idcam) {
        global $roles;
        global $auth_user;

        echo "<h3>Compte camembert</h3>\n";
        if($_POST['create'] != 1) {
                $r = pg_query("SELECT login FROM cam_user WHERE iduser = $idcam");
                $a = pg_fetch_array($r);
        }
        if($roles['roles']) {
                echo "<form action=\"?id=".$_GET['id']."\" method=\"post\">";}
        echo "<table><tr><td>Pseudo</td><td>";
        if($roles['roles']) {
                echo "<input type=\"text\" name=\"pseudo\"";
                if($_POST['create'] != 1) {
                        echo "value=\"".$a['login']."\"/></td></tr>";
                        echo "<input type=\"hidden\" name=\"create\" value=\"3\" />";
                }
                else {
                        echo "\"/></td></tr>";
                        echo "<input type=\"hidden\" name=\"create\" value=\"2\" />";
                }
        }
        else {
                echo $a['login']."</td></tr>";
        }
        echo "</table>\n";
        if($roles['roles']) {
                echo "<input type=\"submit\" value=\"Valider\" />";
                echo "</form>\n";}
        if(($roles['inscription'] && $auth_user == $a['login']) || $roles['inscription_adv']) {
        $r = pg_query("SELECT amount FROM account WHERE iduser = $idcam");
        $a = pg_fetch_array($r);
        echo "<h5>Montant dû : </h5>".$a['amount']." €";
        }
}

function RegisterUser($id) {
        echo "<h3>Compte camembert</h3>\n";
        echo "<form action=\"?id=$id\" method=\"post\">";
        echo "<input type=\"hidden\" name=\"create\" value=\"1\" />";
        echo "<input type=\"submit\" value=\"Créer un compte\" /></form>\n";
}

function NewRegistration($id) {
        $r = pg_query("SELECT MAX(iduser) FROM cam_user");
                if($a = pg_fetch_array($r))
                        $newid = $a[0]+1;
                else
                        $newid = 1;
        $r = pg_query("SELECT iduser FROM cam_user WHERE login = '".$_POST['pseudo']."'");
        if(($a = pg_fetch_array($r)) === false) {
                pg_query("INSERT INTO cam_user(iduser, login, groupe) VALUES($newid, '".$_POST['pseudo']."', 1)");
                pg_query("INSERT INTO id(idcam_user, iduser) VALUES($newid, $id)");
                return $newid;
        }
        else {
                return 0;
        } 
}

function UpdateRegistration($idcam) {
        $r = pg_query("SELECT iduser FROM cam_user WHERE login = '".$_POST['pseudo']."'");
        if(($a = pg_fetch_array($r)) === false) {
                $r2 = pg_query("SELECT login FROM cam_user WHERE iduser = $idcam");
                $a2 = pg_fetch_array($r2);
                pg_query("UPDATE cam_user SET login='".$_POST['pseudo']."' WHERE iduser = ".$idcam);
                return 0;
        }
        else {
                return 1;
        } 
}

if((!isset($_POST['idroom'])) && (!isset($_POST['cable']))) {
        $ok = DisplayUser($_GET['id'], $_GET['act'], $_GET['room']);
        if($ok && ($_GET['act'] != "edit")) {
                DisplayComputers($_GET['id']);
                DisplayEthernetCable(); 
                $r = pg_query("SELECT idcam_user FROM id WHERE iduser = ".$_GET['id']);
                if($a = pg_fetch_array($r)) {
                        if(isset($_POST['pseudo']) && $roles['roles'] && ($_POST['pseudo'] != "") ) {
                                $value = UpdateRegistration($a['idcam_user']);
                                if($value) {
                                        echo "Pseudo d&eacute;j&agrave; utilis&eacute;.";
                                }
                        }
                        DisplayRegistration($a['idcam_user']);
                }
                elseif($roles['roles']) {
                        if(!isset($_POST['create'])) {
                                RegisterUser($_GET['id']);}
                        elseif($_POST['create'] == 1) {
                                DisplayRegistration(0);
                        }
                        elseif(($_POST['create'] == 2) && isset($_POST['pseudo']) && ($_POST['pseudo'] != "") ) {
                                $value = NewRegistration($_GET['id']);
                                if(! $value) {
                                        echo "Pseudo d&eacute;j&agrave; utilis&eacute;.";
                                }
                                else {
                                        DisplayRegistration($value);}
                        }
                }
        }
}
elseif(isset($_POST['cable'])) {
        $id = $_GET['id'];
        if(SellCable()) {
                DisplayUser($id, "", "");
                DisplayComputers($id);
                DisplayEthernetCable(); 
        }
}
else {
        $id = ValidateForm();
        if($id !== false) {
                UpdateInterfaceForUser($id);
                echo "<p><a href=\"user.php?id=$id\">Retour à l'utilisateur</a></p>";                
        }
}

include "inc/inc.footer.php";
?>


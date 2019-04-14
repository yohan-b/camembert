<?php
$page_name = "Tresorerie";

include "inc/roles.php";
include "inc/inc.header.php";
include "inc/libdate.php";

if($roles['inscription_adv']) {
?>

<table style="text-align:center;">
        <thead>
                <tr><th width="100">pr&eacute;nom</th><th width="100">nom</th><th width="70">chambre</th><th width="150">derni&egrave;re &eacute;dition</th><th width="70">montant</th></tr>
        </thead>
        <tbody>
<?php
if(isset ($_POST['id'])) {
        $r = pg_query("UPDATE account SET amount=amount-".$_POST['montant'].", last='".$_POST['montant']." &euro; / ".date("d-m-Y")."' WHERE iduser=".$_POST['id']);
        $r = pg_query("SELECT MAX(year) FROM money");
        if($a = pg_fetch_array($r)) {
                $r = pg_query("UPDATE money SET amount=amount+".$_POST['montant']." WHERE year='".$a[0]."'");
        }
}
$r = pg_query("SELECT * FROM account ORDER BY prenom ASC");
$bline = false;
while($a = pg_fetch_array($r)) {
        $class = "normal".($bline?"2":"");
        $bline = !$bline;
        $erased = 0;
        $r2 = pg_query("SELECT iduser FROM id WHERE idcam_user = ".$a['iduser']);
        $a2 = pg_fetch_array($r2);
        if (isset ($a2)) {
                $r3 = pg_query("SELECT idroom FROM user_pac WHERE iduser = ".$a2['iduser']);
                $a3 = pg_fetch_array($r3);
        }
        if ($a['amount'] == 0) {
                $r3 = pg_query("SELECT login FROM cam_user WHERE iduser = ".$a['iduser']);
                if (! $login = pg_fetch_array($r3)) {
                        pg_query("DELETE FROM account WHERE iduser = ".$a['iduser']);
                        $erased = 1;
                }
                else {
                        $roles2 = array();
                        get_roles($login[0], $roles2);
                        if (! $roles2['inscription']) {
                                pg_query("DELETE FROM account WHERE iduser = ".$a['iduser']);
                                $erased = 1;
                        }
                }
        }
        if (! $erased) {
                echo "<tr class=\"$class\"><td>".$a['prenom']."</td><td>".$a['nom']."</td><td>";
                if (isset ($a3)) {
                        echo "<a href=\"room.php?id=".$a3['idroom']."\">".$a3['idroom']."</a>";
                }
                else {
                        echo "N/A";
                }
                echo "</td><td>".$a['last'];
                echo "</td><td>".$a['amount']."</td>";
                echo "<td><form action=\"\" method=\"post\">";
                echo "<input name=\"montant\" type=\"text\" size=\"5\" value=\"0\" />";
                echo "<input name=\"id\" type=\"hidden\" value=\"".$a['iduser']."\" />";
                echo "<input type=\"submit\" value=\"Soustraire\" /></form></td></tr>\n";
        }
}
?>

        </tbody>
</table>
<table style="text-align:center;">
        <thead>
                <tr><th width="100">année</th><th width="70">montant</th></tr>
        </thead>
        <tbody>

<?php

$r = pg_query("SELECT * FROM money");
$bline = false;
while($a = pg_fetch_array($r)) {
        $class = "normal".($bline?"2":"");
        $bline = !$bline;
        echo "<tr class=\"$class\"><td>".$a['year']."</td><td>".$a['amount']."</td></tr>";
}
?>
        </tbody>
</table>
<?php
include "inc/inc.footer.php";
}
else {
        include "denied.php";
}

?>

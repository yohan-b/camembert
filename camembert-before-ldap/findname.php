<?php
$page_name = "Recherche de nom";

$name = $_GET['name'];
$name = strtolower(trim($name));

if ($name == '') header('Location: index.php');

include "inc/roles.php";
include "inc/inc.header.php";

?>

<form action="findname.php">Chercher un nom
<input type="text" name="name" value="<?php echo $name; ?>">
<input type="submit" value="Rechercher">
</form>

<?php
echo "<blockquote>\n";
if ($name == '')
echo "<p class=\"error\">Le nom ".$_GET["name"]." n'est pas valide.</p>\n";
else
SearchNAME($name);
echo "</blockquote>\n";

include "inc/inc.footer.php";


function CreateTable() {
        ?>
                <p>Le nom <b><?php echo $name; ?></b> a &eacute;t&eacute; trouv&eacute; dans les chambres suivantes</p>
                <table class="list" cellspacing="1" cellpadding="1">
                <tr><th width="40">chambre</th><th width="200">nom</th><th width="100">prenom</th>
                </tr>
                <?php
}

function SearchNAME($name) {
        $seuil = 3;
        // Les accents comptent pour deux caractères
        // Recherche sur les noms et prénoms
        $r = pg_query("SELECT nom, prenom, idroom FROM user_pac WHERE LEVENSHTEIN(LOWER('$name'), lower(prenom)) < $seuil OR LEVENSHTEIN(LOWER('$name'), lower(nom)) < $seuil");
        $tab = array();
        while($a = pg_fetch_array($r)) {
                $tab[$a[2]] = array($a[0], $a[1]);
        }

        $pieces = explode(" ", $name);
        foreach($pieces as $piece) {
                $r = pg_query("SELECT nom, prenom, idroom FROM user_pac WHERE LEVENSHTEIN(LOWER('$piece'), lower(prenom)) < $seuil OR LEVENSHTEIN(LOWER('$piece'), lower(nom)) < $seuil");
                while($a2 = pg_fetch_array($r)) {
                        $tab[$a2[2]] = array($a2[0], $a2[1]);
                }
        }
        if ($tab)
        {
                CreateTable(); 
                foreach($tab as $id => $row) {
                        echo "<tr class=\"normal".($bline?2:"")."\">";
                        echo "<td><a href=\"room.php?id=${id}\">$id</a></td>";
                        echo "<td>$row[0]</td><td>$row[1]</td>";
                        $bline = !$bline;
                } 
                echo "</table>\n";
        }
}

?>

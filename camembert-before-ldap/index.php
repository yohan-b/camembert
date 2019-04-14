<?php
$page_name = "Accueil";

include "inc/roles.php";
include "inc/inc.header.php";
include "libfind.php";

function report()
{
      $fp = fopen ('anomalies.php', 'w');
      fputs($fp, "<?php \$time0 = ".time()."; ?>");
      // TODO : trouver et afficher les anomalies
      $r = pg_query("SELECT idinterface, ifname, ifdescription, ifadminstatus, ifoperstatus, 
        ifvlan, ifvoicevlan, portsecenable, portsecstatus, i.idmateriel AS idm, hostname FROM interface i, materiel m WHERE i.idmateriel = m.idmateriel ORDER BY ifnumber");
      fputs($fp, "<h5>Interfaces en ERR-DIS :</h5><ul>\n");
      while($a = pg_fetch_array($r)) {
        if ($errdisable = (($a[7] == 't') && ($a[8] == 3) && (! $a[3]==0)))
                {
                 fputs($fp, "<li><a href=\"interface.php?id=${a[0]}\">${a[10]} / ${a[1]}</a></li>\n");
                }
        }
      fputs($fp, "</ul><h5>Incoh&eacute;rence DHCP-ARP :</h5><ul>\n");
      // pg_query arpcache and compare with dhcp entries

      fputs($fp, "</ul><h5>Incoh&eacute;rence DHCP-Sticky :</h5><ul>\n");
      // vérifions que les stickies sont tous sur le DHCP   
      $r = pg_query("SELECT mac, type FROM fdb WHERE type <> 0 and datelast = (SELECT MAX(datelast) FROM fdb)");
      while($a = pg_fetch_array($r)) {
        $a2 = SearchMACinDHCP($a[0]);
        if(! $a2) {
           fputs($fp, "<li><a href=\"findmac.php?mac=${a[0]}\">".$a[0]."</a></li>\n");
        }
      }
      fputs($fp, "</ul>\n");
      fclose ($fp); 
}
?>
<ul class="search">
  <li><form action="findip.php"><label class="align">Chercher une adresse IP</label>
    <input type="text" name="ip" class="lmargin"><input type="submit" value="Rechercher"></form>
  <li><form action="findmac.php"><label class="align">Chercher une adresse MAC</label>
    <input type="text" name="mac" class="lmargin"><input type="submit" value="Rechercher"></form>
  <li><form action="findname.php"><label class="align">Chercher un nom</label>
    <input type="text" name="name" class="lmargin"><input type="submit" value="Rechercher"></form>
</ul>

<h4>Rapport d'anomalies :</h4>
<?php 

if (! file_exists("anomalies.php")) {
        report();
}
$time0 = 0; 
include "anomalies.php";
if ($time0 == 0 OR time() - $time0 > 600)
{
        report();
        echo "<script>location=\"index.php\"</script>";
}

include "inc/inc.footer.php"; ?>

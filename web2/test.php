<?php
$cookie='SESS'.md5('pacaterie.u-psud.fr');
$drupal_sid = $_COOKIE[$cookie];
print_r($drupal_sid);
      if (empty($drupal_sid)) {
         return; // Drupal session does not exist; send user to login page
      }
$link = mysql_connect("localhost", "SitePacatUser", "SitePacatDB")
    or die("Impossible de se connecter : " . mysql_error());
echo 'Connexion réussie';
mysql_select_db('SitePacatnet', $link);
$query = sprintf("SELECT name FROM sessions s, users u WHERE s.uid=u.uid AND sid='%s'",
    mysql_real_escape_string($drupal_sid));

// Exécution de la requête
$result = mysql_query($query);

// Vérification du résultat
// Ceci montre la requête envoyée à MySQL ainsi que l'erreur. Utile pour déboguer.
if (!$result) {
    $message  = 'Requête invalide : ' . mysql_error() . "\n";
    $message .= 'Requête complète : ' . $query;
    die($message);
}

$row = mysql_fetch_assoc($result);

$drupal_user=$row['name'];
print_r($drupal_user);
mysql_close($link);
?>

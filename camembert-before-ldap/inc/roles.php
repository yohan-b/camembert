<?php
$cookie='SESS'.md5('pacaterie.u-psud.fr');
$drupal_sid = $_COOKIE[$cookie];
if (empty($drupal_sid)) {
    include "redirect.php";
    exit(); // Drupal session does not exist; send user to login page
}
$link = mysql_connect("localhost", "SitePacatUser", "SitePacatDB")
    or die("Impossible de se connecter : " . mysql_error());
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
mysql_close($link);


include "inc.db.php";

if(!ConnectDB()) {
	echo "<p class=\"error\">Erreur: impossible de se connecter &agrave; la base de donn&eacute;es</p>";
	include "inc.footer.php";
	exit();
}

$roles = array();

# à décommenter si en maintenance :
####
#if($user->name !== 'yohan' && $user->name !== 'Souli') {
#	include "maintenance.php";
#	die();
#}
####

function get_roles($login, &$roles) {
$r = pg_query("SELECT roles FROM groupe g, cam_user u WHERE groupe = idgroupe AND login = '$login'");
if(($a = pg_fetch_array($r)) === false) {
	include "denied.php";
	die();
}

$rolesmask = $a['roles'];

$r = pg_query("SELECT idrole, name FROM role");
while($a = pg_fetch_array($r)) {
	$roles[$a['name']] = (($rolesmask & (1 << $a['idrole'])) > 0);
}

if(array_key_exists('root', $roles) && $roles['root'])
	foreach($roles as $k => $v)
		$roles[$k] = true;
}

get_roles($drupal_user, $roles);
global $auth_user;
$auth_user = $drupal_user;
?>

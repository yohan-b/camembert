</head> 
 
<body> 
        <table id="primary-menu" summary="Navigation elements." border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td id="home" width="10%">
              <a href="http://www.pacaterie.u-psud.fr" title="Accueil"><img src="/www/files/logo.png" alt="Accueil" border="0" /></a>
          </td>

    <td id="site-info" width="20%">
              <div class='site-name'><a href="/" title="Accueil">Camembert</a></div>
                </td>

    <td class="primary-links" width="70%" align="center" valign="middle">
      <ul class="links" id="navlist"><li class="first menu-1-1-2"><a href="http://wiki.aurore.u-psud.fr/" class="menu-1-1-2">Aurore</a></li>
<li class="menu-1-2-2"><a href="http://www.ile.u-psud.fr/" title="Les Branchés de l&#039;Ile" class="menu-1-2-2">BDI</a></li>
<li class="menu-1-3-2"><a href="http://www.rub.u-psud.fr/" title="Club Informatique de Bures sur Yvette" class="menu-1-3-2">CIBY-Net</a></li>
<li class="last menu-1-4-2"><a href="http://www.fleming.u-psud.fr/" class="menu-1-4-2">Flemnet</a></li>
<li class="last menu-1-5-2"><a href="http://www.pacaterie.u-psud.fr/logout" class="menu-1-5-2">Se d&eacute;connecter</a></li>
</ul>    </td>
  </tr>
</table>

<table id="secondary-menu" summary="Navigation elements." border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td class="secondary-links" width="100%"  align="left" valign="middle">
<ul id="navigation">
	<li><a href="index.php"<?php echo $page_name == "Accueil" ? " class=\"active\"" : " class=\"inactive\"";?>>Accueil</a></li>
	<li><a href="room.php"<?php echo $page_name == "Chambres" ? " class=\"active\"" : " class=\"inactive\"";?>>Liste des chambres</a></li>
	<li><a href="list.php"<?php echo $page_name == "Liste du mat&eacute;riel" ? " class=\"active\"" : " class=\"inactive\"";?>>Liste du mat&eacute;riel</a></li>
	<?php if($roles['roles']) {
?>
        <li><a href="cam_users.php"<?php echo $page_name == "Utilisateurs" ? " class=\"active\"" : " class=\"inactive\"";?>>Liste des utilisateurs</a></li>
	<li><a href="groups.php"<?php echo $page_name == "Groupes" ? " class=\"active\"" : " class=\"inactive\"";?>>Liste des groupes</a></li>
	<li><a href="roles_edit.php"<?php echo $page_name == "Roles" ? " class=\"active\"" : " class=\"inactive\"";?>>Roles</a></li>
<?php } ?>
	<li><a href="logs.php"<?php echo $page_name == "Logs" ? " class=\"active\"" : " class=\"inactive\"";?>>Logs</a></li>
	<?php if($roles['inscription_adv']) {
?>
        <li><a href="tresorerie.php"<?php echo $page_name == "Tresorerie" ? " class=\"active\"" : " class=\"inactive\"";?>>Tr&eacute;sorerie</a></li>
<?php } ?>
	<?php if($roles['wifi_vlan4']) {
?>
        <li><a href="si905.php"<?php echo $page_name == "Salle informatique" ? " class=\"active\"" : " class=\"inactive\"";?>>Salle informatique</a></li>
<?php } ?>
</ul>
</td>
  </tr>
  <tr>
    <td colspan="2"><div></div></td>
  </tr>
</table>
<div class="node">
<div class="content">

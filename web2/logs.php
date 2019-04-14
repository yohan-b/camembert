<?php
$page_name = "Logs";

include "inc/roles.php";
include "inc/inc.header2.php";
?>

<style type="text/css" media="screen">

	@import "tablefilter/filtergrid.css";
	/*====================================================
		- html elements
	=====================================================*/
	/*body{ 
		margin:15px; padding:15px; border:1px solid #666;
		font-family:Arial, Helvetica, sans-serif; font-size:88%; 
	}*/
	caption{ margin:10px 0 0 5px; padding:10px; text-align:left; }
	th{
		background:#4A4A4A url(tablefilter/img/bg_th.jpg) left top repeat-x; 
		border-left:1px solid #C7C7C7;
		padding:5px; color:#fff; height:25px;
	}
	pre{ margin:5px; padding:5px; background-color:#f4f4f4; border:1px solid #ccc; }
	/* for elements added by sortable.js in th tags*/
	th img{ border:0; } 
	th a{ color:#fff; font-size:13px; text-transform: uppercase; text-decoration:none; }
	
	/* custom class for Columns Visibility Manager Extension */
	.colsMngContainer{
		position:absolute;
		display:none;
		border:2px outset #999;
		height:auto; width:250px;
		background:#E4FAE4;
		margin:18px 0 0 0; z-index:10000;
		padding:10px 10px 10px 10px;
		text-align:left; font-size:12px;
	}
	
</style>

<script src="tablefilter/tablefilter_all.js" language="javascript" type="text/javascript"></script>
<script src="tablefilter/TFExt_ColsVisibility/TFExt_ColsVisibility.js" language="javascript" type="text/javascript"></script>

<script src="tablefilter/sortabletable.js" language="javascript" type="text/javascript"></script>
<script src="tablefilter/tfAdapter.sortabletable.js" language="javascript" type="text/javascript"></script> 
<!-- -->
<?php
include "inc/inc.body.php";
?>

	<table id="demo" class="list" cellpadding="1" cellspacing="1">
	<thead>
	<tr><th width="30">Id</th><th width="120">Date</th><th width="120">Utilisateur</th><th width="80">User</th><th width="120">Hostname</th>
		<th width="90">Interface</th><th width="80">Action</th><th width="240">Option</th><th width="240">Ancienne valeur</th><th width="30">Iduser</th><th width="70">Montant</th></tr>
</thead>
<tbody>
<?php
$r = pg_query("SELECT idlog, logdate, loggeduser, m.idmateriel, hostname, i.idinterface, ifname, numaction, oldoption, newoption, iduser, amount
	FROM action_log l, interface i , materiel m WHERE l.idinterface = i.idinterface AND i.idmateriel = m.idmateriel
	ORDER BY idlog DESC");
$bline = false;
while($a = pg_fetch_array($r)) {
	switch($a[7]) {
		case 0: $action = "shutdown"; break;
		case 1: $action = "no shutdown"; break;
		case 2: $action = "description"; break;
		case 3: $action = "maximum"; break;
		case 4: $action = "no sticky"; break;
		case 5: $action = "inscription"; break;
		case 6: $action = "d&eacute;m&eacute;nagement"; break;
		case 7: $action = "isolation"; break;
		case 8: $action = "&eacute;dition inscription"; break;
		case 9: $action = "vente cable"; break;
		default: $action = ""; break;
	}
	if($a[7] == 0 || $a[7] == 1) {
		if($a[8] == 0)
			$oldopt = "shutdown";
		else
			$oldopt = "no shutdown";
		$opt = "";
	}
	else if($a[7] == 4) {
		$opt = explode(":", strtolower($a[9]));
		$opt = $opt[0].$opt[1].".".$opt[2].$opt[3].".".$opt[4].$opt[5];
		$oldopt = "";
	}
	else {
		$opt = $a[9];
		$oldopt = $a[8];
	}
	$class = "normal".($bline?"2":"");
	$bline = !$bline;
        $r2 = pg_query("SELECT prenom, nom, idroom FROM user_pac u, cam_user c, id i WHERE c.login = '".$a['loggeduser']."' AND i.idcam_user = c.iduser AND u.iduser = i.iduser");
        $a2 = pg_fetch_array($r2);
        
	echo "<tr class=\"$class\"><td class=\"tdlog\">${a[0]}</td><td class=\"tdlog\">".date("D d/m/Y H:i", $a[1])."</td><td class=\"tdlog\"><a href=\"room.php?id=${a2['idroom']}\">".$a2['prenom']." ".$a2['nom']."</a></td><td class=\"tdlog\">${a[2]}</td><td class=\"tdlog\"><a href=\"materiel.php?id=${a[3]}\">${a[4]}</a></td>\n";
	echo "<td class=\"tdlog\"><a href=\"interface.php?id=${a[5]}\">${a[6]}</a></td><td class=\"tdlog\">$action</td><td class=\"tdlog\">$opt</td><td class=\"tdlog\">$oldopt</td><td class=\"tdlog\">".$a[10]."</td><td class=\"tdlog\">".$a[11]."</td></tr>\n";
}
?>
	</tbody>
	</table>
<div style="clear:both"></div>
<script language="javascript" type="text/javascript">
//<![CDATA[	
	var props = {
		paging: true,
		paging_length: 50,
		sort: true,
		sort_config: {
			sort_types:['number','date','string','string','string','string','string','string','string','number','string']
		},
		remember_grid_values: true,
		alternate_rows: true,
		rows_counter: true,
		btn_reset: true,
		btn_reset_text: "Clear",
		status_bar: true,
		col_3: "select",
		col_4: "select",
		col_5: "select",
		col_6: "select",
		display_all_text: "< Show all >",
		
		/*** Extensions manager ***/
		extensions: { 
						/*** Columns Visibility Manager extension load ***/	
						name:['ColsVisibility'], 
						src:['tablefilter/TFExt_ColsVisibility/TFExt_ColsVisibility.js'], 
						description:['Columns visibility manager'], 
						initialize:[function(o){o.SetColsVisibility();}] 
					},
					
		/*** Columns Visibility Manager extension properties ***/
		showHide_cols_at_start: [0,3,9],
		showHide_cols_text: 'Columns: '
					
	}
	setFilterGrid("demo",props);
//]]>
</script>

<?php
include "inc/inc.footer.php";
?>


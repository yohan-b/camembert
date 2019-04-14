<?php
function SearchMACinDHCP($mac) {
        $r = pg_query("SELECT u.iduser, nom, prenom, c.name, r.idroom, r.name FROM computer c, user_pac u, room r
                      WHERE c.iduser = u.iduser AND u.idroom = r.idroom AND mac = '$mac'");
        $a = pg_fetch_array($r);
        return $a;
}        

function SearchMAConInterface($mac) {
        $r = pg_query("SELECT m.idmateriel, hostname, ifname, vlan, fdb.datefirst, fdb.datelast, i.idinterface
                      FROM materiel m, interface i, fdb
                      WHERE m.idmateriel = i.idmateriel AND i.idinterface = fdb.idinterface
                      AND mac = '$mac' ORDER BY datelast DESC");
        $a = pg_fetch_array($r);
        return $a;
}

function CheckFormat($mac) {
        $m1 = explode('.', $mac);
        $m2 = explode(':', $mac);
        $m3 = explode('-', $mac);

        if (count($m2)>1 || ((strlen($m1[0])<=2) && (count($m2)==1))) {
                // format XX:XX:XX:XX:XX:XX	=> OK
                return $mac;
        }

        if (count($m3)>1 && count($m1)==1 && count($m2)==1) {
                // format XX-XX-XX-XX-XX-XX => convertir
                return (str_replace('-', ':', $mac));
        }

        if (count($m1)>1 || ((strlen($m1[0])<=4) && (count($m1)==1))) {
                // format XXXX.XXXX.XXXX => convertir
                $result = ''; $curr = 0;
                for ($i=0; $i<strlen($mac); $i++) {
                        if ($mac[$i] != '.') {
                                $curr++;
                                $result .= $mac[$i];
                                if (($curr % 2 == 0) && ($curr != 12) && ($i+1 != strlen($mac))) $result .= ':';
                        }
                }

                return $result;
        }

        if (count($m1)==1 && count($m2)==1 && count($m3)==1 && strlen($mac)==12) {
                // format XXXXXXXXXX => convertir
                $result  = $mac[0].$mac[1].':'.$mac[2].$mac[3].':';
                $result .= $mac[4].$mac[5].':'.$mac[6].$mac[7].':';
                $result .= $mac[8].$mac[9].':'.$mac[10].$mac[11];

                return ($result);
        }

        return '';
}

?>

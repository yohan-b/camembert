#!/bin/sh

SQL="/usr/bin/psql -U camembert -d camembert -t -c"
MY_PATH=/home/camembert/scripts
DHCPCONF=/etc/dhcp/dhcpd.conf
DNSCONF=/etc/bind/named.pacaterie.users
RESTARTSCRIPT=/etc/restartDHCPDNS.sh

cat ${MY_PATH}/dhcpd.conf.head >/tmp/dhcpd.conf
res=`${SQL} "SELECT r.name, nom, prenom, datedeco, c.name, mac FROM user_pac u, room r, computer c WHERE c.iduser = u.iduser AND u.idroom = r.idroom ORDER BY r.idroom"`
echo "${res}" | awk -F '|' -v cur_room=0 '{
	gsub(" ", "", $1);
	gsub(" ", "", $2);
	gsub(" ", "", $3);
	gsub(" ", "", $4);
	gsub("-28", "", $4);
	gsub(" ", "", $5);
	gsub(" ", "", $6);

	if($1 != cur_room) {
		print "\n# Chambre "$1" "$2" "$3" "$4;
		cur_room = $1;
	}

	print "\thost "$5" {"
	print"\t\thardware ethernet "toupper($6)";";
	print"\t\tfixed-address "$5".pacaterie.u-psud.fr;";
	print"\t}"
}' >>/tmp/dhcpd.conf
echo "}" >>/tmp/dhcpd.conf

res=`${SQL} "SELECT r.name, c.name, ip FROM computer c, user_pac u, room r WHERE c.iduser = u.iduser AND u.idroom = r.idroom ORDER BY ip"`
echo "${res}" | awk -F '|' '{
	gsub(" ", "", $1);
	gsub(" ", "", $2);
	gsub(" ", "", $3);
	print "; Chambre "$1"\n"$2" IN A "$3"\n";
}' >/tmp/named.pacaterie.users

restart=false
diffs=`diff /tmp/dhcpd.conf ${DHCPCONF} | wc -l`
if [ ${diffs} -ne 0 ]; then
	mv /tmp/dhcpd.conf ${DHCPCONF}
	restart=true
fi
diffs=`diff /tmp/named.pacaterie.users ${DNSCONF} | wc -l`
if [ ${diffs} -ne 0 ]; then
	mv /tmp/named.pacaterie.users ${DNSCONF}
	restart=true
fi

if ${restart}; then
	${RESTARTSCRIPT} >/dev/null
fi

rm -rf /tmp/dhcpd.conf /tmp/named.pacaterie.users

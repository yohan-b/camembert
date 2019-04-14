#!/bin/sh

SQL="/usr/bin/psql -U camembert -d camembert -t -c"
SMAIL="/usr/sbin/sendmail"
MY_PATH=/home/camembert/scripts

today=`date +%F`
id=`${SQL} "SELECT MAX(idaction) FROM action"`
id=$(($id+1))
res=`${SQL} "SELECT name, nom, prenom, i.idinterface FROM user_pac u, room r, interface i
	WHERE u.idroom = r.idroom AND r.idinterface = i.idinterface AND datedeco < '${today}' AND ifadminstatus <> 0"`

# Aucun résultat : rien à faire
if [ -z "${res}" ]; then
	exit 0
fi

# Deco switch
echo "${res}" | awk -F '|' -v SQL="${SQL}" -v id=${id} '{
	gsub(" ", "", $1);
	gsub(" ", "", $2);
	gsub(" ", "", $3);
	gsub(" ", "", $4);
	system(SQL" \"INSERT INTO action VALUES("id", "$4", 0, '"''"')\" >/dev/null");
	id = id+1;
	system(SQL" \"INSERT INTO action VALUES("id", "$4", 2, '"'"'Chambre "$1" "$2" "$3" PAS PAYE'"'"')\" >/dev/null");
	id = id+1;
}'

# Mail aux admins
cat ${MY_PATH}/deco_periode.mail >/tmp/deco_periode.mail
echo "${res}" | awk -F '|' '{
	gsub(" ", "", $1);
	gsub(" ", "", $2);
	gsub(" ", "", $3);
	print "Chambre "$1" "$2" "$3;
}' >>/tmp/deco_periode.mail
${SMAIL} pacanet-admins@googlegroups.com </tmp/deco_periode.mail
rm -rf /tmp/deco_periode.mail

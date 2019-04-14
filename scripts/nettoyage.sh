#!/bin/sh
#Pratiquement la même chose que deco_periode.sh, sauf que ça supprime l'user de la base et que ça envoie pas de mail

SQL="/usr/bin/psql -U camembert -d camembert -t -c"
SMAIL="/usr/sbin/sendmail"
MY_PATH=/home/camembert/scripts

today=`date +%F`
id=`${SQL} "SELECT MAX(idaction) FROM action"`
id=$(($id+1))
res=`${SQL} "SELECT name, iduser, idinterface FROM user_pac u, room r WHERE u.idroom = r.idroom AND datedeco < '${today}'"`

echo "${res}" | awk -F '|' -v SQL="${SQL}" -v id=${id} '{
	gsub(" ", "", $1);
	gsub(" ", "", $2);
	gsub(" ", "", $3);
	system(SQL" \"INSERT INTO action VALUES("id", "$4", 0, '"''"')\" >/dev/null");
	id = id+1;
	system(SQL" \"INSERT INTO action VALUES("id", "$4", 2, '"'"'Chambre "$1" VIDE'"'"')\" >/dev/null");
	id = id+1;
}'


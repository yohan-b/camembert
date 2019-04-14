#!/bin/sh

SQL="/usr/bin/psql -U camembert -d camembert -t -c"
SMAIL="/usr/sbin/sendmail"
MY_PATH=/home/camembert/scripts

id=`${SQL} "SELECT MAX(idaction) FROM action"`
id=$(($id+1))
res=`${SQL} "SELECT nom, prenom, datedeco, name, idinterface FROM room r, user_pac u WHERE r.idroom = u.idroom AND certif = '0'"`

echo "$res" | awk -F '|' -v SQL="${SQL}" -v id=$id '{
	gsub(" ", "", $1);
	gsub(" ", "", $2);
	gsub(" ", "", $3);
	gsub("-28", "", $3);
	gsub(" ", "", $4);
	gsub(" ", "", $5);
	system(SQL" \"INSERT INTO action VALUES("id", "$5", 0, '"''"')\" >/dev/null");
	id = id+1;
	system(SQL" \"INSERT INTO action VALUES("id", "$5", 2, '"'"'Chambre "$4" "$1" "$2" "$3" CERTIF'"'"')\" >/dev/null");
	id = id+1;
}'

cat ${MY_PATH}/deco_certif.mail >/tmp/certif.mail
echo "$res" | awk -F '|' '{
	gsub(" ", "", $1);
	gsub(" ", "", $2);
	gsub(" ", "", $4);
	print "Chambre "$4" "$1" "$2;
}' >>/tmp/certif.mail
${SMAIL} pacanet-admins@googlegroups.com </tmp/certif.mail
rm -rf /tmp/certif.mail

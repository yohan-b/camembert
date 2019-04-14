#!/bin/sh

SQL="/usr/bin/psql -U camembert -d camembert -t -c"

id=`${SQL} "SELECT MAX(idaction) FROM action"`
id=$(($id+1))
res=`${SQL} "SELECT name, idinterface FROM room WHERE idroom NOT IN(SELECT idroom FROM user_pac)"`

echo "$res" | awk -F '|' -v SQL="${SQL}" -v id=$id '{
	gsub(" ", "", $1);
	gsub(" ", "", $2);
	system(SQL" \"INSERT INTO action VALUES("id", "$2", 0, '"''"')\" >/dev/null");
	id = id+1;
	system(SQL" \"INSERT INTO action VALUES("id", "$2", 2, '"'"'Chambre "$1" VIDE'"'"')\" >/dev/null");
	id = id+1;
}'


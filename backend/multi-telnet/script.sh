#!/bin/bash 
#script.sh 

echo "veuillez donner le mot de passe" 
stty -echo                                                                       #[1] 
read password 
stty echo 

export ssh='./ssh.sh'                                                            #[2] 
export telnet='./telnet.sh' 
export erreur='./rapport_erreurs.log' 
export temp='./tmp_routeur.log' 
export cmdcisco='./commandes.txt' 
export liste='./liste.txt' 
export password 
export routeur 
export commande 

rm -f $erreur                                                                    #[3] 
rm -f $ssh 
rm -f $telnet 

cat $liste | while read routeur; 
do 
        if [ "$routeur" != "" ] 
        then 
                if[ ! -f $ssh ]                                                  #[4] 
                then 
                        echo 'expect 2>&1 << EOF'>> $ssh 
                        echo 'spawn ssh admin@$routeur' >> $ssh 
                        echo 'expect {' >> $ssh 
                        echo '"Password:" {send "$password\r"}' >> $ssh 
                        echo 'timeout {exit}' >> $ssh 
                        echo '        }' >> $ssh 
                        echo 'expect "#"' >> $ssh 

                        cat $cmdcisco | while read commande 
                        do 
                                echo "send \"$commande\r\"" 
                                echo 'expect "#"' 
                        done >> $ssh 

                        echo 'send "exit\r"' >> $ssh 
                        echo 'expect "closed"' >> $ssh 
                        echo 'exit' >> $ssh 
                        echo 'EOF' >> $ssh 

                        chmod +x $ssh                                            #[5] 
                fi 
                time -p $ssh > $temp 2>&1                                        #[6] 

                COD_RET=$? 

                auth='cat $temp | grep -c "Password: "'                          #[7] 
                if [ "$auth" -gt "1" ] 
                then 
                        echo "Problème d'authentification sur $routeur !" 
                        echo "$routeur : wrong log-in/password" >> $erreur 
                        continue 
                fi 

                temps='grep 'real ' $temp | sed 's/real /§/' | cut -d'§' -f2 | cut -d' ' -f1 | cut -d'.' -f1' 
                if [ $temps -ge 10 -a ! "'grep 'closed' $temp'" ]                #[8] 
                then 
                        echo "L'equipement $routeur ne réponds pas !"; 
                        echo "$routeur : connection timed out"  >> $erreur 
                        continue 
                fi 

                if [ "$COD_RET" != "0" ]                                          #[9] 
                then 
                        #Erreur de connexion a l'équipement en SSH 
                        if [ ! -f $telnet ] 
                        then 
                                echo 'expect 2>&1 << EOF'>> $telnet 
                                echo 'spawn telnet $routeur' >> $telnet 
                                echo 'send "admin\r"' >> $telnet 
                                echo 'expect "Password:"' >> $telnet 
                                echo 'send "$password\r"' >> $telnet 
                                echo 'expect "#"' >> $telnet 

                                cat $cmdcisco | while read commande 
                                do 
                                        echo "send \"$commande\r\"" 
                                        echo 'expect "#"' 
                                done >> $telnet 

                                echo 'send "exit\r"' >> $telnet 
                                echo 'expect "closed"' >> $telnet 
                                echo 'exit' >> $telnet 
                                echo 'EOF' >> $telnet 

                                chmod +x $telnet 
                        fi 
                        $telnet > $temp 2>&1 
                fi 
                COD_RET=$? 

                auth='cat $temp | grep -c "Password: "'                          #[10] 
                if [ "$auth" -gt "1" ] 
                then 
                        echo "Problème d'authentification sur $routeur !" 
                        echo "$routeur : wrong log-in/password" >> $erreur 
                elif [ "'grep 'Host name lookup failure' $temp'"  ] 
                then 
                        echo "l'equipement $routeur n'existe pas !" 
                        echo "$routeur : does not exist"  >> $erreur 
                elif [ "'grep 'Unknown host' $temp'" ] 
                then 
                        echo "la saisie de l'ip ou du nom $routeur est incorrecte !" 
                        echo "$routeur : wrong spelling" >> $erreur 
                elif [ "'grep 'send: spawn id exp4 not open' $temp'" ] 
                then 
                        echo "/!\ ERREUR dans la procédure. Consultez le fichier log de $routeur !!!" 
                        echo "$routeur : Expect script execution failed !" >> $erreur 
                        cp $temp $routeur.error.log 
                elif [ "'grep 'Authentication failed' $temp'" ] 
                then 
                        echo "Mot de passe erroné pour $routeur !" 
                        echo "$routeur : wrong log-in/password" >> $erreur 
                elif [ "'grep 'Connection refused' $temp'" ] 
                then 
                        echo "Connexion à distance sur $routeur désactivé !" 
                        echo "$routeur : vty connection disabled" >> $erreur 
                elif [ "'grep 'No route to host' $temp'" ] 
                then 
                        echo "Alias DNS $routeur existant mais IP invalide !" 
                        echo "$routeur : No route to host" >> $erreur 
                elif [ "'grep 'ProCurve' $temp'" ] 
                then 
                        echo "routeur $routeur HP et non Cisco !" 
                        echo "$routeur : non Cisco router (HP ProCurve)" >> $erreur 
                elif [ "'grep 'Alcatel' $temp'" ] 
                then 
                        echo "routeur $routeur Alcatel et non Cisco !" 
                        echo "$routeur : non Cisco router (Alcatel)" >> $erreur 
                elif [ "'grep 'Welcome to X1000' $temp'" ] 
                then 
                        echo "routeur $routeur X1000 et non Cisco !" 
                        echo "$routeur : non Cisco equipement (X1000)" >> $erreur 
                elif [ "'grep '% Unknown command' $temp'" -o "'grep '% Invalid' $temp'" ] 
                then 
                        echo "/!\ Commandes Cisco non reconnues par l'equipement. Consultez le fichier log de $routeur !!!" 
                        echo "$routeur : Unrecognized commands found" >> $erreur 
                        cp $temp $routeur.error.log 
                elif [ "'grep 'Connected to ' $temp'" -o "'grep 'Connection closed by foreign host.' $temp'" ] 
                then 
                        echo "$routeur Telnet OK !" 
                elif [ "'grep 'Connexion enregistree sur le terminal' $temp'" -o "'grep 'Connection to ' $temp'" ] 
                then 
                        echo "$routeur SSH OK !" 
                elif [ "$COD_RET" != "0" ] 
                then 
                        echo "Problème de connexion a l'equipement $routeur !" 
                        echo "$routeur : connection problem" >> $erreur 
                fi 
        fi 
done 
rm -f $temp                                                                      #[11] 
exit 


#Commentaires
#
#    1 : On cache la saisie du mot de passe
#    2 : Tous les fichiers sont stockés dans des variables en chemin relatif, pour que le script puisse être exécuté de n'importe où
#    3 : on supprime les fichiers générés existants si le script à déjà été exécuté
#    4 : on génère le fichier Expect si ce n'est pas déjà fait, en rentrant la procédure de connexion SSH ainsi que les commandes provenant de commandes.txt
#    5 : on attribue les droits d'exécution au script Expect généré, si on veut qu'il s'exécute correctement
#    6 : on execute le script expect, en regroupant la sortie d'erreur avec la sortie standard, en calculant le temps d'exécution pour gérer le timeout, et en crachant le tout dans un fichier temp
#    7 : on vérifie qu'il n'y ai pas un problème d'authentification en comptant le nombre d'occurrence "Password:" dans le fichier temp
#    8 : on récupère le nombre du temps d'execution, et on vérifie qu'il ne soit pas supérieur à 10 (valeur du timeout du expect)
#    9 : En cas d'erreur de connexion en SSH, on refait la procédure en Telnet
#    10 : On gère tous les cas d'erreur pris en compte par le script. (c.f. II)
#    11 : On supprime le fichier temp, qui devient inutile



#!/bin/sh

SQL="/usr/bin/psql -U camembert -d camembert -t -c"
SMAIL="/usr/sbin/sendmail"
MY_PATH=/home/camembert/scripts

mails=`${SQL} "SELECT mail FROM user_pac WHERE certif = '0'"`
mailline=`echo ${mails} | sed "s/\n/ /"`
${SMAIL} ${mailline} <${MY_PATH}/rappel_certif_user.mail

cat ${MY_PATH}/rappel_certif_admin.mail | sed "s/%RCPT%/${mailline}/" >/tmp/certif.mail
${SMAIL} root </tmp/certif.mail
rm -rf /tmp/certif.mail

#!/bin/sh

SQL="/usr/bin/psql -U camembert -d camembert -t -c"
SMAIL="/usr/sbin/sendmail"
MY_PATH=/home/camembert/scripts

mails=`${SQL} "SELECT mail FROM user_pac"`
mailline=`echo ${mails} | sed "s/\n/ /"`
${SMAIL} ${mailline} <${MY_PATH}/mail_everybody.mail


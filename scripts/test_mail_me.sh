#!/bin/sh

SQL="/usr/bin/psql -U camembert -d camembert -t -c"
SMAIL="/usr/sbin/sendmail"
MY_PATH=/home/camembert/scripts

mails=`${SQL} "SELECT mail FROM user_pac WHERE idroom='058'"`
mailline=`echo ${mails} | sed "s/\n/ /"`
${SMAIL} ${mailline} <${MY_PATH}/test_mail_me.mail


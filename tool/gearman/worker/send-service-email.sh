#!/bin/bash

## Crontab
#*/1 * * * * /path/to/script

## DominName
scriptDir=`echo $(cd $(dirname $0);pwd)`
dominName=`echo $scriptDir| awk -F/ '{print $4}'`

## Define
DAEMON_VAR="daemon"
PHP_BIN="/usr/local/php/bin/php"
PHP_SCRIPT="/data/vhosts/$dominName/www/public/cli.php"
REQUEST_URI="/cli/gearmanworker/send_service_email"

## Check arguments
if [[ x$1 != x ]]; then
    PHP_COMMAND="$PHP_SCRIPT request_uri=$REQUEST_URI/$DAEMON_VAR/$1"
else
    PHP_COMMAND="$PHP_SCRIPT request_uri=$REQUEST_URI"
fi

## Check process
process=`ps -ef | grep -v "grep" | grep "$PHP_COMMAND" | wc -l`

if [[ "$process" == "0" ]]; then
    ## Restart
    nohup $PHP_BIN $PHP_COMMAND &
fi

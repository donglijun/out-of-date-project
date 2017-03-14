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
REQUEST_URI="/cli/wallet/publish_system_red"

nohup $PHP_BIN $PHP_SCRIPT request_uri=$REQUEST_URI


#!/bin/bash

## Crontab
#30 0 * * * /path/to/script

## DominName

scriptDir=`echo $(cd $(dirname $0);pwd)`
dominName=`echo $scriptDir| awk -F/ '{print $4}'`


## Define
PHP_BIN="/usr/local/php/bin/php"
PHP_SCRIPT_CLI="/data/vhosts/$dominName/www/public/cli.php"
REQUEST_URI_CLEANUP_MYSQL_LOL_CHAMPION_PICKBAN="/cron/cleanup/mysqllolchampionpickban/day/21"

PLATFORMS="wt1 oc1 ru la1 la2 na1 br1 tr1 eun1 euw1 kr"

## Run
for platform in $PLATFORMS; do
    $PHP_BIN $PHP_SCRIPT_CLI request_uri="$REQUEST_URI_CLEANUP_MYSQL_LOL_CHAMPION_PICKBAN"/platform/"$platform"
done

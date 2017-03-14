#!/bin/bash

## Crontab
#30 0 * * * /path/to/script

## DominName
scriptDir=`echo $(cd $(dirname $0);pwd)`
dominName=`echo $scriptDir| awk -F/ '{print $4}'`

## Define
PHP_BIN="/usr/local/php/bin/php"
PHP_SCRIPT="/data/vhosts/$dominName/www/public/cron.php"
REQUEST_URI="/cron/report/dau"

## Run
$PHP_BIN $PHP_SCRIPT request_uri=$REQUEST_URI

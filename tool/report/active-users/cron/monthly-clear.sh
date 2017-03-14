#!/bin/bash

## Crontab
#50 0 1 * * /path/to/script

## DominName
scriptDir=`echo $(cd $(dirname $0);pwd)`
dominName=`echo $scriptDir| awk -F/ '{print $4}'`

## Define
PHP_BIN="/usr/local/php/bin/php"
PHP_SCRIPT="/data/vhosts/$dominName/www/public/cron.php"
REQUEST_URI="/cron/report/cleardau/day/180"
REQUEST_URI2="/cron/report/clearmau/month/6"

## Run
$PHP_BIN $PHP_SCRIPT request_uri=$REQUEST_URI
$PHP_BIN $PHP_SCRIPT request_uri=$REQUEST_URI2

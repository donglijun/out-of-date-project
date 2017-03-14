#!/bin/bash

## Crontab
#35 0 * * 1 /path/to/script

## DominName
scriptDir=`echo $(cd $(dirname $0);pwd)`
dominName=`echo $scriptDir| awk -F/ '{print $4}'`

## Define
PHP_BIN="/usr/local/php/bin/php"
PHP_SCRIPT="/data/vhosts/$dominName/www/public/cron.php"
REQUEST_URI="/cron/report/lol_champion_weekly"

## Run
$PHP_BIN $PHP_SCRIPT request_uri="$REQUEST_URI"/platform/br1
$PHP_BIN $PHP_SCRIPT request_uri="$REQUEST_URI"/platform/eun1
$PHP_BIN $PHP_SCRIPT request_uri="$REQUEST_URI"/platform/euw1
$PHP_BIN $PHP_SCRIPT request_uri="$REQUEST_URI"/platform/kr
$PHP_BIN $PHP_SCRIPT request_uri="$REQUEST_URI"/platform/la1
$PHP_BIN $PHP_SCRIPT request_uri="$REQUEST_URI"/platform/la2
$PHP_BIN $PHP_SCRIPT request_uri="$REQUEST_URI"/platform/na1
$PHP_BIN $PHP_SCRIPT request_uri="$REQUEST_URI"/platform/oc1
$PHP_BIN $PHP_SCRIPT request_uri="$REQUEST_URI"/platform/ru
$PHP_BIN $PHP_SCRIPT request_uri="$REQUEST_URI"/platform/tr1

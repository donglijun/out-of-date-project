#!/bin/bash

## DominName
scriptDir=`echo $(cd $(dirname $0);pwd)`
dominName=`echo $scriptDir| awk -F/ '{print $4}'`


## Define
PHP_BIN="/usr/local/php/bin/php"
PHP_SCRIPT="/data/vhosts/$dominName/www/public/cron.php"
REQUEST_URI="/cron/report/regweekly/at/"
DATE_BIN="/bin/date"
DAY_STEP=604800

## For Mac
#FROM=`$DATE_BIN -j -f "%Y-%m-%d %H:%M:%S" "2013-06-01 00:00:00" "+%s"`
#TO=`$DATE_BIN -j -f "%Y-%m-%d %H:%M:%S" "2014-02-17 00:00:00" "+%s"`
## For Linux
FROM=`$DATE_BIN --date "2013-06-01 00:00:00" "+%s"`
TO=`$DATE_BIN --date "2014-02-17 00:00:00" "+%s"`

## Run
for (( i=$FROM; i<=$TO; i=i+$DAY_STEP ))
do
    $PHP_BIN $PHP_SCRIPT request_uri=$REQUEST_URI$i
done

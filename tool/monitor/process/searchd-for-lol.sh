#!/bin/bash

## Crontab
#*/1 * * * * /path/to/script

## Define
SEARCHD_BIN="/usr/local/sphinx/bin/searchd"
SEARCHD_CONF="/etc/sphinx/9312/sphinx.conf"
RUN_COMMAND="$SEARCHD_BIN -c $SEARCHD_CONF"

## Check process
process=`ps -ef | grep -v "grep" | grep "searchd" | grep "\-c" | grep "$SEARCHD_CONF" | wc -l`

if [[ "$process" == "0" ]]; then
    ## Restart
    $RUN_COMMAND
fi
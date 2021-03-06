#!/bin/bash

## Crontab
#35 0 * * 1 /path/to/script

## DominName
scriptDir=`echo $(cd $(dirname $0);pwd)`
dominName=`echo $scriptDir| awk -F/ '{print $4}'`

## Define
SPHINX_BIN_INDEXER="/usr/local/sphinx/bin/indexer"
SPHINX_BIN_SEARCHD="/usr/local/sphinx/bin/searchd"
SPHINX_CONFIG_9314="/etc/sphinx/9314/sphinx.conf"

PHP_BIN="/usr/local/php/bin/php"
PHP_SCRIPT_CRON="/data/vhosts/$dominName/www/public/cron.php"
PHP_SCRIPT_CLI="/data/vhosts/$dominName/www/public/cli.php"
REQUEST_URI_REPORT_CHAMPION_WEEKLY="/cron/report/lol_champion_weekly"
REQUEST_URI_CALCULATE_CHAMPION_SUMMONER_DATA_WEEKLY="/cli/lolranking/calculate_champion_summoner_data_weekly"

PLATFORMS="wt1 kr"

# Check timestamp
if [[ "$1" -gt 0 ]] 2>/dev/null ; then
    at=$1
else
    at=0
fi

for platform in $PLATFORMS; do
    # Rebuild index
    AT=$at $SPHINX_BIN_INDEXER -c $SPHINX_CONFIG_9314 --rotate lol_user_"$platform" lol_match_"$platform"

    # Calculate ranking
    $PHP_BIN $PHP_SCRIPT_CLI request_uri="$REQUEST_URI_CALCULATE_CHAMPION_SUMMONER_DATA_WEEKLY"/platform/"$platform"/at/"$at"
done

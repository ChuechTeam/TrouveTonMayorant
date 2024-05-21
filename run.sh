#! /bin/bash

# The Run Script - runs TTM!
# USAGE
# ./run.sh [HOST]
# with HOST being an optional hostname with the port to which the PHP development server will listen to.
# (default: localhost:8080)
# ENVIRONMENT VARIABLES
# PUBLIC=1 : If set, the script will attempt to find the local IP address of the machine to setup the HOST value.
#            This setting will override the HOST value.
# Examples:
# ./run.sh                 -> runs TTM on localhost:8080
# ./run.sh localhost:9876  -> runs TTM on localhost:9876
# PUBLIC=1 ./run.sh        -> runs TTM on the local IP address of the machine
#
# This script also initializes databases for the first time if no database has been found;
# if this happens, a default admin user -- Mister Egg -- is created!

# How to run : PUBLIC=1 ./run.sh if public, ./run.sh for local purpose only

PROJECT_DIR=$(realpath "$(dirname "$0")")

if [ "${PUBLIC:-0}" -eq 1 ]; then   #public
    IPOUT="$(nmcli --fields IP4.ADDRESS device show | grep -oP -m 1 '(\d+\.?){4}')"
    HOST="$IPOUT:8080"
else    #Local 
    HOST="${1:-localhost:8080}"
fi


if [ ! -e "$PROJECT_DIR/users.json" ]; then
    echo "Creating the databases for the first time, with the default admin user";
    bash "$PROJECT_DIR/upgrade.sh"
    php -c "$PROJECT_DIR/php.ini" "$PROJECT_DIR/scripts/createAdminAccount.php"
fi 

echo "Running server at http://$HOST"
php -S "$HOST" -c "$PROJECT_DIR/php.ini" -t "$PROJECT_DIR/src"

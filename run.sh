#! /bin/bash

# How to run : PUBLIC=1 ./run.sh if public, ./run.sh for local purpose only

PROJECT_DIR=$(realpath "$(dirname "$0")")

if [ "${PUBLIC:-0}" -eq 1 ]; then   #public
    IPOUT="$(nmcli --fields IP4.ADDRESS device show eno1 | grep -oP '(\d+\.?){4}')"
    HOST="$IPOUT:8080"
else    #Local 
    HOST="${1:-localhost:8080}"
fi


if [ ! -e "$PROJECT_DIR/users.json" ]; then
    echo "Creating the databases for the first time";
    bash "$PROJECT_DIR/upgrade.sh"
fi 

echo "Running server at http://$HOST"
php -S "$HOST" -c "$PROJECT_DIR/php.ini" -t "$PROJECT_DIR/src"

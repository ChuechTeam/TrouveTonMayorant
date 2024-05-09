#! /bin/bash

PROJECT_DIR=$(realpath "$(dirname "$0")")

if [ "${PUBLIC:-0}" -eq 1 ]; then
    IPOUT="$(nmcli --fields IP4.ADDRESS device show eno1 | grep -oP '(\d+\.?){4}')"
    HOST="$IPOUT:8080"
else
    HOST="${1:-localhost:8080}"
fi

echo "Running server at http://$HOST"
php -S "$HOST" -c "$PROJECT_DIR/php.ini" -t "$PROJECT_DIR/src"

PROJECT_DIR=$(realpath $(dirname "$0"))

HOST="${1:-localhost:8080}"

php -S "$HOST" -t "$PROJECT_DIR/src"
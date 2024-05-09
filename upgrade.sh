#! /bin/bash

PROJECT_DIR=$(realpath "$(dirname "$0")")

php "$PROJECT_DIR/scripts/upgradeDatabases.php"
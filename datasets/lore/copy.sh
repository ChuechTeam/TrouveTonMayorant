#!/usr/bin/env bash

# Copies the database data to the server, in the right folders.

DATASET_DIR=$(realpath "$(dirname "$0")")
PROJECT_DIR=$(realpath "$DATASET_DIR/../..")

cp -i "$DATASET_DIR/users.json" "$PROJECT_DIR/users.json"
cp -i "$DATASET_DIR/moderation.json" "$PROJECT_DIR/moderation.json"
cp -ri "$DATASET_DIR/views/" "$PROJECT_DIR/views/"
cp -ri "$DATASET_DIR/conversations/" "$PROJECT_DIR/conversations/"
cp -ri "$DATASET_DIR/user-image-db/" "$PROJECT_DIR/src/user-image-db/"
PROJECT_DIR=$(realpath "$(dirname "$0")")

# Removes all stored user data. Pretty good solution for GDPR compliance.

# Make sure the file exists to avoid doing massive accidental damage.
if [ ! -e "$PROJECT_DIR/users.json" ]; then
    echo "No users.json file found in $PROJECT_DIR! Aborting."
    exit 1
fi

rm -v "$PROJECT_DIR/users.json"
rm -v "$PROJECT_DIR/moderation.json"
rm -rv "$PROJECT_DIR/views/" || true
rm -rv "$PROJECT_DIR/conversations/" || true
rm -rv "$PROJECT_DIR/src/user-image-db/" || true
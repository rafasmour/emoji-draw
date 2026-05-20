#!/bin/bash
set -e

# Validate required environment variables provided by docker-compose
required_vars=(
  MONGO_INITDB_ROOT_USERNAME
  MONGO_INITDB_ROOT_PASSWORD
  MONGODB_DB
  MONGODB_USER
  MONGODB_PASS
)

for var in "${required_vars[@]}"; do
  if [ -z "${!var}" ]; then
    echo "Missing required environment variable: ${var}" >&2
    exit 1
  fi
done

# Root user is already created by the official MongoDB image when
# MONGO_INITDB_ROOT_USERNAME and MONGO_INITDB_ROOT_PASSWORD are set.
# Create application user in target DB only.
mongosh -u "$MONGO_INITDB_ROOT_USERNAME" -p "$MONGO_INITDB_ROOT_PASSWORD" --authenticationDatabase admin <<EOF
use $MONGODB_DB
if (!db.getUser("$MONGODB_USER")) {
  db.createUser({
    user: "$MONGODB_USER",
    pwd: "$MONGODB_PASS",
    roles: [ { role: "dbOwner", db: "$MONGODB_DB" } ]
  })
}
EOF

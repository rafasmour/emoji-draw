#!/bin/bash
set -e

# Load required environment variables
export $(grep -E "^(MONGO_INITDB_ROOT_USERNAME|MONGO_INITDB_ROOT_PASSWORD|MONGO_INITDB_DATABASE|MONGODB_DB|MONGODB_USER|MONGODB_PASS)=" /docker-entrypoint-initdb.d/.env)

# Create the root user in the admin DB
mongosh <<EOF
use admin
db.createUser({
  user: "$MONGO_INITDB_ROOT_USERNAME",
  pwd: "$MONGO_INITDB_ROOT_PASSWORD",
  roles: [ { role: "root", db: "admin" } ]
})
EOF

# Create the application user in the target DB
mongosh <<EOF
use $MONGODB_DB
db.createUser({
  user: "$MONGODB_USER",
  pwd: "$MONGODB_PASS",
  roles: [ { role: "dbOwner", db: "$MONGODB_DB" } ]
})
EOF

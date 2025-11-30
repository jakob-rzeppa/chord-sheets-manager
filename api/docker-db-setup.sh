#!/bin/sh

# This script sets up the database for the Symfony application.

set -e

# Wait for database to be ready
echo "Waiting for database to be ready..."
until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
  echo "Database is unavailable - sleeping"
  sleep 2
done

echo "Database is ready!"

# Create database if it doesn't exist
echo "Creating database..."
php bin/console doctrine:database:create --if-not-exists --no-interaction

# Create schema
echo "Creating schema..."
php bin/console doctrine:schema:create --no-interaction || echo "Schema already exists, skipping..."

echo "Database setup complete!"

# Execute the CMD from Dockerfile
exec "$@"

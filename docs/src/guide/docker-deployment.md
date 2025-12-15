# Docker Deployment

This guide explains how to deploy the Chord Sheets Manager application using Docker and Docker Compose.

## Architecture

The application consists of four Docker services:

- **database**: PostgreSQL 16 database
- **api**: Symfony PHP-FPM backend application
- **nginx**: Nginx web server (reverse proxy for PHP-FPM)
- **web**: Vue.js frontend application

All services communicate through a custom Docker network called `chord-sheets-network`.

## Prerequisites

- Docker Engine 20.10+
- Docker Compose V2
- At least 2GB of available RAM

## Configuration

### Environment Variables

Create a `.env.local` file in the root directory with your database credentials:

```bash
# PostgreSQL Database Configuration
POSTGRES_PASSWORD=your_secure_password
POSTGRES_USER=your_username
POSTGRES_DB=your_database_name
```

**Important**: The `.env.local` file is gitignored and should never be committed to version control.

### Database Configuration

The database service includes:
- **Healthcheck**: Uses `pg_isready` to verify PostgreSQL is accepting connections
- **Automatic initialization**: The API service waits for the database to be healthy before starting

### API Service

The API service automatically:
1. Waits for the database to be ready
2. Creates the database if it doesn't exist
3. Creates the database schema
4. Runs any pending migrations (if available)

This is handled by the `docker-db-setup.sh` entrypoint script.

The database connection url is defined in the .env.prod file.

## Deployment Steps

### 1. Initial Setup

Clone the repository and navigate to the project directory:

```bash
cd chord-sheets-manager
```

### 2. Configure Environment

Create your `.env.local` file with the required database credentials (see Configuration section above).

### 3. Build and Start Services

Build the Docker images and start all services:

```bash
docker compose build
docker compose up -d
```

The `-d` flag runs containers in detached mode (background).

### 4. Verify Services

Check that all services are running:

```bash
docker compose ps
```

All services should show status as "Up" or "healthy".

### 5. View Logs

To monitor the application logs:

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f api
docker compose logs -f nginx
```

## Maintenance

### Backup Database

```bash
docker compose exec database pg_dump -U main main > backup.sql
```

### Restore Database

```bash
cat backup.sql | docker compose exec -T database psql -U main -d main
```

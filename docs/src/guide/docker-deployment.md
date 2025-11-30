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

## Accessing the Application

Once deployed, the application is accessible at:

- **Frontend (Web)**: http://localhost:8080
- **Backend API**: http://localhost:8000
- **Database**: localhost:5432 (if you need direct access)

## Service Details

### Database Service

- **Image**: `postgres:alpine`
- **Port**: 5432
- **Healthcheck**: Runs every 5 seconds with 5 retries
- **Data persistence**: Data is stored in Docker volumes

### API Service

- **Base Image**: `php:8.3-fpm`
- **PHP Extensions**: intl, pdo, pdo_mysql, pdo_pgsql, zip, opcache
- **Environment**: Production mode (`APP_ENV=prod`)
- **Auto-setup**: Database initialization on startup

### Nginx Service

- **Image**: `nginx:stable-alpine`
- **Port**: 8000
- **Configuration**: Custom nginx config for Symfony routing
- **FastCGI**: Proxies PHP requests to API service on port 9000

### Web Service

- **Port**: 8080
- **Build**: Production-optimized Vue.js build

## Common Operations

### Restart Services

```bash
docker compose restart
```

### Restart Specific Service

```bash
docker compose restart api
```

### Stop Services

```bash
docker compose stop
```

### Stop and Remove Containers

```bash
docker compose down
```

### Rebuild After Code Changes

```bash
docker compose down
docker compose build
docker compose up -d
```

### Execute Commands in Containers

Run Symfony console commands:

```bash
docker compose exec api php bin/console <command>
```

### View Service Resources

```bash
docker compose stats
```

## Troubleshooting

### Database Connection Errors

If you see "password authentication failed" errors:

1. Verify your `.env.local` file has matching credentials
2. Restart the services: `docker compose restart`

### API Returns 500 Errors

1. Check API logs: `docker compose logs api`
2. Clear Symfony cache: `docker compose exec api php bin/console cache:clear`
3. Verify database connection: `docker compose exec api php bin/console doctrine:query:sql "SELECT 1"`

### Database Not Ready

If the API starts before the database is ready:

1. The healthcheck should prevent this, but if it occurs, restart: `docker compose restart api`
2. Check database status: `docker compose exec database pg_isready -U main`

## Network Configuration

All services communicate through the `chord-sheets-network` bridge network. This provides:
- Service discovery by container name
- Network isolation
- Inter-container communication

## Maintenance

### Backup Database

```bash
docker compose exec database pg_dump -U main main > backup.sql
```

### Restore Database

```bash
cat backup.sql | docker compose exec -T database psql -U main -d main
```

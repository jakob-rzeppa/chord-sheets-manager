# Chord Sheets Manager

A full-stack application for managing and organizing chord sheets with transpose and formatting capabilities.

## Features

- Manage chord sheets for songs
- Organize by artists and tags
- Transpose chords to different keys
- Format chord sheets with consistent styling
- Backup and restore functionality

## Tech Stack

- **Backend**: Symfony 7.3 (PHP 8.3)
- **Frontend**: Vue.js 3
- **Database**: PostgreSQL 16 (production) / SQLite (development)
- **Web Server**: Nginx with PHP-FPM
- **Documentation**: VitePress

## Quick Start

### Using Docker (Recommended)

```bash
# Clone the repository
git clone https://github.com/jakob-rzeppa/chord-sheets-manager.git
cd chord-sheets-manager

# Create environment configuration
cp .env.local.example .env.local
# Edit .env.local with your database credentials

# Start all services
docker compose up -d

# Access the application
# Frontend: http://localhost:8080
# API: http://localhost:8000
```

### Local Development with Symfony CLI

```bash
# Backend
cd api
composer install
symfony serve

# Frontend
cd web
npm install
npm run dev
```

## Documentation

**Full documentation is available in the [docs](./docs) directory.**

To view the documentation locally:

```bash
cd docs
npm install
npm run dev
```

The documentation site will be available at http://localhost:5173, if the port is avaliable.

## Project Structure

```
chord-sheets-manager/
├── api/                  # Symfony backend application
│   ├── src/             # Application source code
│   ├── tests/           # PHPUnit tests
│   └── public/          # Web entry point
├── web/                 # Vue.js frontend application
├── nginx/               # Nginx configuration
├── docs/                # VitePress documentation
└── compose.yaml         # Docker Compose configuration
```

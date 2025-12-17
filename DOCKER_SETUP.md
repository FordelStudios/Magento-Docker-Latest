# Magento Docker Setup Guide

## Prerequisites
- Docker Desktop installed
- Docker Compose installed
- At least 4GB RAM allocated to Docker
- Ports 8080, 3306, 9200, and 6380 available on your machine

## Services in Docker Compose

The setup includes the following services:

- **web** - Nginx 1.18 (Port 8080)
- **php** - Custom PHP-FPM container (configured for Magento 2.4.7-p3)
- **db** - MySQL 8.0 (Port 3306)
- **opensearch** - OpenSearch 2.11.0 (Port 9200)
- **redis** - Redis 6.2 (Port 6380 mapped from 6379)

## Starting the Environment

### 1. Start all containers
```bash
docker-compose up -d
```

### 2. Check container status
```bash
docker-compose ps
```

All containers should show status as "Up".

### 3. View logs (optional)
```bash
# View all logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f web
docker-compose logs -f php
docker-compose logs -f db
```

## Stopping the Environment

### Stop all containers
```bash
docker-compose stop
```

### Stop and remove containers
```bash
docker-compose down
```

### Stop and remove containers + volumes (WARNING: deletes database data)
```bash
docker-compose down -v
```

## Running Magento Commands

All Magento commands should be run inside the PHP container:

```bash
# General format
docker-compose exec php php bin/magento <command>

# Or enter the container shell
docker-compose exec php bash
```

### Common Commands

```bash
# Setup upgrade (apply patches and schema changes)
docker-compose exec php php bin/magento setup:upgrade

# Clear cache
docker-compose exec php php bin/magento cache:flush

# Reindex
docker-compose exec php php bin/magento indexer:reindex

# Set permissions (if needed)
docker-compose exec php chmod -R 777 var/ generated/ pub/

# Enable developer mode
docker-compose exec php php bin/magento deploy:mode:set developer

# Compile DI
docker-compose exec php php bin/magento setup:di:compile

# Deploy static content
docker-compose exec php php bin/magento setup:static-content:deploy -f
```

## Accessing Services

### Magento Admin & Storefront
- **URL**: http://localhost:8080
- **Admin URL**: http://localhost:8080/admin (check your Magento configuration for actual admin path)

### Database (MySQL)
- **Host**: localhost
- **Port**: 3306
- **Database**: magento
- **Username**: magento
- **Password**: magento
- **Root Password**: magento

#### Connect via command line:
```bash
docker-compose exec db mysql -umagento -pmagento magento
```

#### Connect via MySQL client:
Use any MySQL client (MySQL Workbench, DBeaver, TablePlus, etc.) with the credentials above.

### OpenSearch
- **URL**: http://localhost:9200

### Redis
- **Host**: localhost
- **Port**: 6380

## Troubleshooting

### Containers won't start
```bash
# Check logs
docker-compose logs

# Restart services
docker-compose restart

# Rebuild containers
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Permission issues
```bash
docker-compose exec php chmod -R 777 var/ generated/ pub/ app/etc/
docker-compose exec php chown -R www-data:www-data var/ generated/ pub/
```

### Database connection errors
```bash
# Wait for MySQL to fully start (can take 30-60 seconds)
docker-compose exec db mysqladmin ping -h localhost -umagento -pmagento
```

### Clear everything and start fresh
```bash
docker-compose down -v
docker-compose up -d
# Then restore your database backup if needed
```

## Importing Database

If you have a database backup (like `magento_backup.sql`):

```bash
# Copy SQL file to container
docker cp magento_backup.sql magento-docker-latest-db-1:/tmp/

# Import
docker-compose exec db mysql -umagento -pmagento magento < /tmp/magento_backup.sql

# Or from host:
docker-compose exec -T db mysql -umagento -pmagento magento < magento_backup.sql
```

## Custom Modules

This installation includes the following Formula custom modules:
- Formula_ProductRoutine (with routine set flag support)
- Formula_Blog, Formula_Brand, Formula_Ingredient
- Formula_Categories, Formula_CategoryBanners
- Formula_Review, Formula_Wishlist, Formula_Wallet
- And many more (see CLAUDE.md for full list)

After making changes to custom modules, run:
```bash
docker-compose exec php php bin/magento setup:upgrade
docker-compose exec php php bin/magento cache:flush
```

## Environment Variables

Database configuration is in `docker-compose.yml`:
- `MYSQL_ROOT_PASSWORD=magento`
- `MYSQL_DATABASE=magento`
- `MYSQL_USER=magento`
- `MYSQL_PASSWORD=magento`

## Volumes

Persistent data is stored in Docker volumes:
- `dbdata` - MySQL database
- `osdata` - OpenSearch data

Your source code is mounted from `./src` to `/var/www/html` in the containers.

## Production Setup

For production, use `docker-compose.prod.yml`:
```bash
docker-compose -f docker-compose.prod.yml up -d
```

## Additional Resources

- Magento DevDocs: https://devdocs.magento.com
- Docker Compose Docs: https://docs.docker.com/compose/

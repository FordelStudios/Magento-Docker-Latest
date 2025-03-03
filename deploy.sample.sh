#!/bin/bash

echo "Starting deployment..."

# Pull latest changes
git pull origin main

# Run Magento deployment commands
docker-compose -f docker-compose.prod.yml exec -T php bin/magento setup:static-content:deploy -f
docker-compose -f docker-compose.prod.yml exec -T php bin/magento setup:upgrade
docker-compose -f docker-compose.prod.yml exec -T php bin/magento setup:di:compile
docker-compose -f docker-compose.prod.yml exec -T php bin/magento cache:flush
docker-compose -f docker-compose.prod.yml exec -T php chmod -R 777 var generated pub/static pub/media app/etc

echo "Deployment completed!"
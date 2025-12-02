FROM nginx:stable-alpine

# Copy nginx configuration
COPY nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy application files from the api directory
COPY api /var/www/html

EXPOSE 80

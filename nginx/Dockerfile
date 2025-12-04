# ==== Web Application Build Stage ====
FROM node:lts-alpine AS web-builder

# Make the 'app' folder the current working directory
WORKDIR /app

# Copy both 'package.json' and 'package-lock.json' (if available)
COPY web/package*.json ./

# Install project dependencies
RUN npm install

# Copy project files and folders to the current working directory (i.e. 'app' folder)
COPY web .

# Build app for production
RUN npm run build


# ==== Nginx Stage ====
FROM nginx:stable-alpine

# Copy nginx configuration
COPY nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy built web files from the web-builder stage
COPY --from=web-builder /app/dist /etc/nginx/web/html

# Copy the runtime injection script into the container
COPY web/env.sh /docker-entrypoint.d/env.sh
RUN dos2unix /docker-entrypoint.d/env.sh
RUN chmod +x /docker-entrypoint.d/env.sh

# Copy api public files from the api directory.
COPY api/public /etc/nginx/api/html/public

EXPOSE 80

ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["nginx", "-g", "daemon off;"]
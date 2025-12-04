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

# Copy api public files from the api directory.
COPY api/public /etc/nginx/api/html/public

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
# ==== Web Application Build Stage ====
FROM node:lts-alpine as web-builder

# Install simple http server for serving static content
RUN npm install -g http-server

# Make the 'app' folder the current working directory
WORKDIR /app

# Accept build argument for API endpoint (must be before COPY to use in build)
ARG WEB_SERVER_URL
ENV VITE_API_ENDPOINT="${WEB_SERVER_URL}/api"

# Copy both 'package.json' and 'package-lock.json' (if available)
COPY package*.json ./

# Install project dependencies
RUN npm install

# Copy project files and folders to the current working directory (i.e. 'app' folder)
COPY . .

# Build app for production with minification
RUN npm run build

EXPOSE 8080
CMD [ "http-server", "dist" ]


# ==== Nginx Stage ====
FROM nginx:stable-alpine

# Copy nginx configuration
COPY nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy built web files from the web-builder stage
COPY --from=web-builder /app/dist /etc/nginx/web/html

# Copy api files from the api directory. Only the php / public files are needed
COPY api/src /etc/nginx/api/html/src
COPY api/public /etc/nginx/api/html/public
COPY api/assets /etc/nginx/api/html/assets

EXPOSE 80

FROM php:8.2-cli

WORKDIR /app

# Install dependencies
RUN apt-get update && apt-get install -y \
    mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Copy application files
COPY . .

# Create upload directories
RUN mkdir -p assets/uploads/plats assets/uploads/avatars && \
    chmod 755 assets/uploads assets/uploads/plats assets/uploads/avatars

# Expose port
EXPOSE 8000

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:8000", "-t", "."]

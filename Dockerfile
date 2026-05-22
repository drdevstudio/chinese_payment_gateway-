# ── Ghora Pay — Render Deployment Dockerfile ──────────────────────────────────
# PHP 8.2 + Apache on Debian Bullseye (slim)
# Render Free: 512MB RAM, 0.1 CPU, spins down after 15 min inactivity
FROM php:8.2-apache-bullseye

# ── System deps ───────────────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        libssl-dev \
        ca-certificates \
        curl \
    && rm -rf /var/lib/apt/lists/*

# ── PHP extensions ────────────────────────────────────────────────────────────
# curl  → Firebase REST API calls
# openssl is built-in; nothing else needed (no MySQL, no PDO)
RUN docker-php-ext-install curl \
    && docker-php-ext-enable curl

# ── PHP config tweaks ─────────────────────────────────────────────────────────
RUN echo "expose_php = Off"           >> /usr/local/etc/php/php.ini \
 && echo "display_errors = Off"       >> /usr/local/etc/php/php.ini \
 && echo "log_errors = On"            >> /usr/local/etc/php/php.ini \
 && echo "error_log = /dev/stderr"    >> /usr/local/etc/php/php.ini \
 && echo "max_execution_time = 30"    >> /usr/local/etc/php/php.ini \
 && echo "memory_limit = 128M"        >> /usr/local/etc/php/php.ini \
 && echo "upload_max_filesize = 8M"   >> /usr/local/etc/php/php.ini \
 && echo "post_max_size = 8M"         >> /usr/local/etc/php/php.ini \
 && echo "session.gc_maxlifetime = 3600" >> /usr/local/etc/php/php.ini \
 && echo "date.timezone = Asia/Kolkata"  >> /usr/local/etc/php/php.ini

# ── Apache config ─────────────────────────────────────────────────────────────
# Enable mod_rewrite for clean URLs
RUN a2enmod rewrite

# Render passes the PORT env variable; Apache must listen on it.
# We use a startup script to inject $PORT into Apache's config.
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Main VirtualHost — allow .htaccess overrides
RUN sed -i 's|<Directory /var/www/>|<Directory /var/www/html/>|g' \
        /etc/apache2/apache2.conf 2>/dev/null || true

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# ── App files ─────────────────────────────────────────────────────────────────
WORKDIR /var/www/html
COPY . .

# Remove files that shouldn't be web-accessible
RUN rm -rf \
    docker/ \
    android/ \
    db/generate_admin_hash.php \
    .dockerignore \
    render.yaml \
    FIREBASE_SETUP.md

# Fix permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

# ── Startup script ────────────────────────────────────────────────────────────
# Render sets $PORT dynamically; Apache must bind to it
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080
CMD ["/start.sh"]

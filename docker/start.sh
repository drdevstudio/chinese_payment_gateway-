#!/bin/sh
# Render sets PORT dynamically (usually 10000).
# Apache needs to listen on that port, not 80.
PORT="${PORT:-8080}"
export PORT

echo "[start.sh] Starting Apache on port $PORT"

# Enable headers module for security headers
a2enmod headers > /dev/null 2>&1 || true

# Start Apache in foreground
exec apache2-foreground

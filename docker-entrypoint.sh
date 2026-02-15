#!/bin/bash
set -e

# Render provides the PORT environment variable
# Apache needs to listen on that port
if [ -n "$PORT" ]; then
    sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
    sed -i "s/:80/:$PORT/" /etc/apache2/sites-available/000-default.conf
fi

# Execute the original CMD
exec "$@"

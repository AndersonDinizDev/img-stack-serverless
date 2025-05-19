#!/bin/sh
set -e

php artisan serve --host=0.0.0.0 --port=80 &

exec tail -f /dev/null

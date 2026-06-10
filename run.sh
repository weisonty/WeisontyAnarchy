#!/bin/bash
# Weisonty Anarchy - PocketMine-MP Start Script (Linux/Mac)

PHP_BIN="./bin/php7/bin/php"
POCKETMINE_BIN="./PocketMine-MP.phar"

# If no local PHP, try system PHP
if [ ! -f "$PHP_BIN" ]; then
  PHP_BIN=$(command -v php8.2 || command -v php8.1 || command -v php8.0 || command -v php)
fi

if [ -z "$PHP_BIN" ]; then
  echo "[ERROR] PHP not found. Please install PHP 8.1+ or place php binary in ./bin/php7/bin/php"
  exit 1
fi

if [ ! -f "$POCKETMINE_BIN" ]; then
  echo "PocketMine-MP.phar not found. Downloading latest stable..."
  curl -L -o PocketMine-MP.phar "https://github.com/pmmp/PocketMine-MP/releases/latest/download/PocketMine-MP.phar"
  if [ $? -ne 0 ]; then
    echo "[ERROR] Failed to download PocketMine-MP.phar. Download it manually from:"
    echo "  https://github.com/pmmp/PocketMine-MP/releases"
    exit 1
  fi
fi

echo "Starting Weisonty Anarchy Server..."
echo "Press CTRL+C or type 'stop' to shut down."
echo ""

while true; do
  "$PHP_BIN" -dphar.readonly=0 "$POCKETMINE_BIN" --no-wizard
  EXIT_CODE=$?
  if [ $EXIT_CODE -eq 0 ]; then
    echo "Server stopped cleanly."
    break
  else
    echo "Server crashed (exit $EXIT_CODE). Restarting in 5 seconds..."
    sleep 5
  fi
done

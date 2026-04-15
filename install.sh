#!/bin/bash

# HestiaCP Monitor API Addon Installer
# Developed by JAMP Web Solutions

# Styling for terminal output
BLUE='\033[0;34m'
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}=========================================${NC}"
echo -e "${BLUE}   HestiaCP Monitor API Setup by JAMP    ${NC}"
echo -e "${BLUE}=========================================${NC}"

# Check for root/sudo privileges
if [ "$EUID" -ne 0 ]; then 
  echo -e "${RED}Error: Please run as root or using sudo.${NC}"
  exit 1
fi

# 1. Verify HestiaCP installation
if [ ! -d "/usr/local/hestia" ]; then
    echo -e "${RED}Error: HestiaCP is not installed on this server.${NC}"
    exit 1
fi

# 2. Setup directory
INSTALL_DIR="/usr/local/hestia/web/api/monitor"
echo -e "[*] Creating secure API directory..."
mkdir -p "$INSTALL_DIR"

# -- ΝΕΟ: Δυναμική εύρεση του ιδιοκτήτη του HestiaCP --
HESTIA_OWNER=$(stat -c '%U:%G' /usr/local/hestia/web/api)

# 3. Generate a random 24-character Secret Key
SECRET_KEY=$(head -c 32 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c 24)
echo "$SECRET_KEY" > "$INSTALL_DIR/secret.key"
chmod 600 "$INSTALL_DIR/secret.key"
chown $HESTIA_OWNER "$INSTALL_DIR/secret.key"

# 4. Download index.php from GitHub
echo -e "[*] Downloading bridge files..."
curl -s -o "$INSTALL_DIR/index.php" "https://raw.githubusercontent.com/jampwebsolutions/hestiacp-api-addon/main/index.php"

# Ensure correct ownership for Hestia's web server
chown -R $HESTIA_OWNER "$INSTALL_DIR"
chmod 755 "$INSTALL_DIR"

# 5. Output connection details
HOSTNAME=$(hostname)
echo -e ""
echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}   INSTALLATION COMPLETED SUCCESSFULLY   ${NC}"
echo -e "${GREEN}=========================================${NC}"
echo -e ""
echo -e "Use the following details in your mobile app:"
echo -e ""
echo -e "Server URL: ${BLUE}https://$HOSTNAME:8083/api/monitor/index.php${NC}"
echo -e "Secret Key: ${BLUE}$SECRET_KEY${NC}"
echo -e ""
echo -e "Keep this Secret Key private! It allows app access to your server stats."
echo -e "========================================="
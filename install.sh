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
echo -e "[*] Preparing secure API directory..."
mkdir -p "$INSTALL_DIR"

# 3. Generate a random 24-character Secret Key
SECRET_KEY=$(head -c 32 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c 24)

# 4. Download index.php from GitHub
echo -e "[*] Downloading bridge files from GitHub..."
curl -s -o "$INSTALL_DIR/index.php" "https://raw.githubusercontent.com/jampwebsolutions/hestiacp-api-addon/main/index.php"

# 5. INJECT THE KEY INTO THE PHP FILE (The Fix)
# We use 'sed' to replace the placeholder with the actual unique key
sed -i "s/JAMP_KEY_PLACEHOLDER/$SECRET_KEY/g" "$INSTALL_DIR/index.php"

# 6. Set correct ownership and permissions
# We detect the owner of the Hestia API folder to ensure compatibility
HESTIA_OWNER=$(stat -c '%U:%G' /usr/local/hestia/web/api)
chown -R $HESTIA_OWNER "$INSTALL_DIR"
chmod 755 "$INSTALL_DIR"
chmod 644 "$INSTALL_DIR/index.php"

# 6b. Configure Sudoers for the specific user
# Detect only the username from HESTIA_OWNER (before the colon)
JUST_USER=$(echo $HESTIA_OWNER | cut -d: -f1)

echo "[*] Configuring sudoers for user: $JUST_USER"
SUDO_FILE="/etc/sudoers.d/hestia-monitor-$JUST_USER"
echo "$JUST_USER ALL=(ALL) NOPASSWD: /usr/local/hestia/bin/*" > "$SUDO_FILE"
chmod 440 "$SUDO_FILE"

# 7. Output connection details
HOSTNAME=$(curl -s https://ifconfig.me || hostname -f)
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
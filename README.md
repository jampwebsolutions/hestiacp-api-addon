# HestiaCP Monitor - API Addon

![License](https://img.shields.io/badge/license-GPLv3-blue.svg)

This repository contains the server-side API bridge required for the **HestiaCP Monitor** mobile application (developed by JAMP Web Solutions). 

It provides a secure, lightweight, and automated way to connect your mobile app to your Hestia Control Panel without ever exposing your root SSH passwords.

## 🚀 Features

* **Zero SSH Credentials Required:** The app uses cryptographic API tokens instead of your server's root password.
* **Time-Based Security (TOTP):** The communication uses rolling tokens that change every 30 seconds, preventing replay attacks.
* **Restricted Commands:** The bridge is hardcoded to only allow safe, read-only commands (like checking stats and listing domains) and service restarts. It cannot delete users, databases, or websites.
* **Automated 1-Click Install:** No manual file uploads or complex firewall configurations needed.

## ⚙️ Installation

Log in to your HestiaCP server via SSH as `root` (or a user with `sudo` privileges) and run the following command:

```bash
wget -qO- [https://raw.githubusercontent.com/jampwebsolutions/hestiacp-api-addon/main/install.sh](https://raw.githubusercontent.com/jampwebsolutions/hestiacp-api-addon/main/install.sh) | sudo bash
```

**📱 Connecting the App**
Once the installation finishes successfully, the script will output two important pieces of information in your terminal:

Server URL (e.g., https://your-server.com:8083/api/monitor/index.php)

Secret Key (e.g., a1B2c3D4e5F6g7H8i9J0...)

Open your HestiaCP Monitor mobile app, add a new server, and paste these exact details.

**🔒 Security Architecture**
This addon places the index.php bridge script inside the secure /usr/local/hestia/web/api/monitor/ directory. By doing this:

It inherits HestiaCP's built-in Nginx security and SSL certificate.

It operates strictly on the HestiaCP administration port (8083).

It remains entirely completely independent of your public websites and user domains (it will continue to work even if your main website goes down).

**📝 License**
This project is licensed under the GNU GPLv3 License.

Developed with ❤️ by JAMP Web Solutions
# WP Plugin Inspector

**WP Plugin Inspector** is a WordPress plugin that adds a WP-CLI command to perform security audits on installed plugins.

This tool helps site administrators and developers identify outdated, closed, tampered, or unknown plugins — all directly from the command line.

---

## 🔧 Features

- ✅ **Detects closed plugins** and shows the reason for closure.
- 🔍 **Identifies plugins not found in the WordPress repository**.
- 🔐 **Verifies plugin checksums** to detect tampering (like `wp plugin verify-checksums`).
- 📂 **Compares plugin folder count** to registered plugins to spot orphan or rogue files.
- 🎯 Focuses only on potential problems — no noise.
- 🎨 Color-coded CLI output (red for closed, blue for unknown, etc.).

---

## 🧩 WP-CLI Command

After activating the plugin, run:

wp plugin-scan run

User Activity Tracker for WordPress
Plugin Name: User Activity Tracker
Description: Tracks anonymous user behavior (session time, browser, OS, visited pages, etc.) and sends it to an external analytics endpoint. Designed to be performance-optimized, security-aware, and user-configurable.
Version: 1.0.1
Author: Ashish Bajagain
License: GPL2

🚀 Overview
The User Activity Tracker is a lightweight WordPress plugin designed to collect anonymous user behavior data on your website. This data is then sent to a specified external analytics service for further analysis. The plugin prioritizes performance, security, and user configurability, ensuring minimal impact on site speed while respecting user privacy by not collecting Personally Identifiable Information (PII).

✨ Features
Anonymous Tracking: Collects non-personal user data.

Performance Optimized: Utilizes WordPress REST API, throttled client-side submissions, bot detection, and asynchronous data sending for a low server footprint.

Security Aware: No PII is collected. Uses cookie-based UUIDs for tracking sessions.

User Configurable: Easy enable/disable tracking option from the WordPress admin panel.

Environment Aware: Tracking is primarily active in production environments by default.

📦 Installation
Download: Download the plugin from its source (e.g., as a .zip file).

Upload:

Go to your WordPress Admin Dashboard.

Navigate to Plugins > Add New.

Click on the "Upload Plugin" button at the top of the page.

Choose the downloaded .zip file and click "Install Now".

Activate: Once installed, click "Activate Plugin".

🛠️ Usage
Configuring the Plugin
After activation, navigate to User Tracker in your WordPress admin menu.

On the settings page, you will find a toggle switch for "Enable Tracking".

Check the box to activate the frontend tracking script.

Uncheck the box to disable tracking.

Click "Save Changes" to apply your settings.

How it Works
When enabled, a small JavaScript file (tracker.js) is loaded on the frontend of your website.

This script collects anonymous data such as session start/end times, page title, browser, operating system, screen dimensions, language, timezone, and a generated UUID for the user session.

This data is then sent asynchronously to the plugin's internal WordPress REST API endpoint (/wp-json/tracker/v1/log/).

The WordPress backend processes this data, performs bot detection, and then forwards the sanitized payload to an external analytics API (https://user-events-api.azurewebsites.net/api/UserEvents).

Logged-in users are automatically excluded from tracking.

🔒 Privacy and Security
No PII: This plugin is strictly designed to collect anonymous data. No names, email addresses, IP addresses (they are anonymized before sending to the external service), or other personally identifiable information are stored or transmitted.

UUIDs: Users are tracked using a randomly generated Universal Unique Identifier (UUID) stored in a cookie, which is purely for session identification and does not link back to personal user data.

🤝 Contributing
(Optional section - if you plan to accept contributions)
If you'd like to contribute to this plugin, please feel free to fork the repository and submit pull requests.

📄 License
This plugin is released under the GPL2 License. See the LICENSE file for more details.

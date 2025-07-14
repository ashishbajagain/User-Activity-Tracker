# User Activity Tracker for WordPress

**Plugin Name:** User Activity Tracker
**Description:** A lightweight WordPress plugin designed to anonymously track user activity and send the data to an external analytics endpoint. Focuses on performance, security, and user control.
**Version:** 1.0.1
**Author:** Ashish Bajagain
**License:** GPL2
**License URI:** <https://www.gnu.org/licenses/gpl-2.0.html>

---

## 🌟 What it Does

The User Activity Tracker plugin helps you understand how users interact with your WordPress website without compromising their privacy. It collects anonymous data about user sessions, such as time spent on pages, browser information, operating system, and screen dimensions. This data is then securely sent to an external analytics service for your analysis.

## ✨ Key Features

* **Anonymous Data Collection:** Tracks user behavior without collecting Personally Identifiable Information (PII).

* **Performance-Optimized:** Utilizes WordPress REST API, asynchronous data submission, and bot detection to minimize server load and maintain site speed.

* **Secure & Private:** Employs cookie-based UUIDs for session tracking and includes strict data sanitization. Logged-in users are automatically excluded from tracking.

* **Configurable:** Easily enable or disable tracking directly from your WordPress admin dashboard.

* **Environment Aware:** By default, tracking is active only in `production` environments, leveraging WordPress's `WP_ENVIRONMENT_TYPE` constant.

## 🚀 Installation

1.  **Download:** Obtain the plugin's `.zip` file.

2.  **Upload:**

    * Log in to your WordPress admin dashboard.

    * Navigate to `Plugins` > `Add New`.

    * Click the "Upload Plugin" button at the top.

    * Select the downloaded `.zip` file and click "Install Now".

3.  **Activate:** After installation, click "Activate Plugin`.

## ⚙️ Configuration & Usage

Once activated, you can manage the plugin's settings:

1.  Go to your WordPress admin menu and click on `User Tracker`.

2.  On the settings page, you will see an "Enable Tracking" toggle.

3.  **Check the box** to activate the frontend tracking script and begin collecting data.

4.  **Uncheck the box** to pause or disable all tracking.

5.  Remember to click "Save Changes" to apply any modifications.

### How Data is Handled

* A small JavaScript file (`js/tracker.js`) is loaded on the frontend, which gathers anonymous user data.

* This data is then sent via an asynchronous `POST` request to the plugin's custom REST API endpoint (`/wp-json/tracker/v1/log/`).

* On the server-side, the plugin validates and sanitizes the incoming data, performs bot detection, and then forwards the cleaned payload to your configured external analytics service (`https://user-events-api.azurewebsites.net/api/UserEvents`).

## 🛡️ Privacy Considerations

This plugin is built with privacy in mind:

* It **does not** collect personal identifying information such as names, email addresses, or precise IP addresses (IPs are anonymized or not used for identification).

* User sessions are identified by a randomly generated, non-personal UUID stored in a cookie.

* Logged-in WordPress users are automatically excluded from tracking to avoid collecting data on administrators or other authenticated users.

## 📄 License

This plugin is open-source and distributed under the **GPL2 License**. For more details, please refer to the full license text.

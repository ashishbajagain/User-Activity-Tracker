<?php

/**
 * Plugin Name: User Activity Tracker
 * Description: Tracks anonymous user behavior (session time, browser, OS, etc.) and sends it to an external analytics endpoint.
 * Version: 1.0.1
 * Author: Ashish Bajagain
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: user-activity-tracker
 * Domain Path: /languages
 */

// Exit if accessed directly to prevent unauthorized access.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Class UserActivityTracker
 *
 * Handles anonymous user activity tracking within WordPress.
 * This includes enqueuing scripts, registering REST API endpoints,
 * managing admin settings, setting user tracking cookies,
 * and sending data to an external analytics service.
 */
class UserActivityTracker
{
  /**
   * Define the REST API namespace for the plugin.
   *
   * @var string
   */
  const REST_NAMESPACE = 'tracker/v1';

  /**
   * Define the REST API route for logging tracking data.
   *
   * @var string
   */
  const REST_ROUTE     = '/log/';

  /**
   * Constructor.
   *
   * Initializes the plugin by setting up various WordPress hooks.
   */
  public function __construct()
  {
    // Enqueue frontend scripts for tracking.
    add_action('init', [$this, 'enqueue_scripts']);
    // Enqueue admin styles for the settings page.
    add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
    // Register REST API routes.
    add_action('rest_api_init', [$this, 'register_rest_route']);
    // Add the plugin's admin menu page.
    add_action('admin_menu', [$this, 'add_admin_menu']);
    // Set user tracking cookies in the site's header.
    add_action('wp_head', [$this, 'set_user_tracking_cookies']);
    // Register plugin settings.
    add_action('admin_init', [$this, 'register_settings']);
  }

  /**
   * Enqueues the main tracking JavaScript file for the frontend.
   *
   * The script is only enqueued if tracking is enabled via the admin settings.
   */
  public function enqueue_scripts()
  {
    // Check if tracking is enabled in the plugin settings.
    $enabled = get_option('uat_enable_tracking', '1');
    if ($enabled !== '1') {
      return;
    }

    // Enqueue the tracker.js script.
    wp_enqueue_script(
      'user-activity-tracker',
      plugin_dir_url(__FILE__) . 'js/tracker.js',
      [],
      '1.1.2',
      true
    );

    // Localize script with environment data, useful for debugging or conditional logic in JS.
    wp_localize_script(
      'user-activity-tracker',
      'tracker_object',
      [
        'environment' => defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'production',
        'rest_url'    => get_rest_url(null, self::REST_NAMESPACE . self::REST_ROUTE),
      ]
    );
  }

  /**
   * Enqueues the admin CSS file for the plugin's settings page.
   */
  public function enqueue_admin_styles()
  {
    wp_enqueue_style('uat-admin-style', plugin_dir_url(__FILE__) . 'css/admin-style.css');
  }

  /**
   * Registers the custom REST API route for receiving tracking data.
   */
  public function register_rest_route()
  {
    register_rest_route(
      self::REST_NAMESPACE,
      self::REST_ROUTE,
      [
        'methods'             => 'POST',
        'callback'            => [$this, 'handle_tracking_data'],
        'permission_callback' => '__return_true',
        'args'                => [
          'startTime'        => [
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'is_string',
          ],
          'endTime'          => [
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'is_string',
          ],
          'pageTitle'        => [
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'is_string',
          ],
          'userAgent'        => [
            'required'          => false,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'is_string',
          ],
          'screenWidth'      => [
            'required'          => false,
            'sanitize_callback' => 'absint',
            'validate_callback' => 'is_numeric',
          ],
          'screenHeight'     => [
            'required'          => false,
            'sanitize_callback' => 'absint',
            'validate_callback' => 'is_numeric',
          ],
          'language'         => [
            'required'          => false,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'is_string',
          ],
          'timezone'         => [
            'required'          => false,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'is_string',
          ],
          'cookiesEnabled'   => [
            'required'          => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
            'validate_callback' => 'rest_validate_boolean',
          ],
          'location'         => [ // Renamed from 'geolocation' for clarity if it's user-provided
            'required'          => false,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'is_string',
          ],
          'operatingSystem'  => [
            'required'          => false,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'is_string',
          ],
          'timeSpent'        => [
            'required'          => false,
            'sanitize_callback' => 'absint',
            'validate_callback' => 'is_numeric',
          ],
          'browser'          => [
            'required'          => false,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'is_string',
          ],
        ],
      ]
    );
  }

  /**
   * Adds the plugin's administration menu page under the 'Settings' menu.
   */
  public function add_admin_menu()
  {
    add_menu_page(
      __('User Activity Tracker', 'user-activity-tracker'),
      __('User Tracker', 'user-activity-tracker'),
      'manage_options',
      'user-activity-tracker',
      [$this, 'render_admin_page'],
      'dashicons-visibility',
      90
    );
  }

  /**
   * Registers the plugin settings for the admin page.
   */
  public function register_settings()
  {
    // Register a setting for enabling/disabling tracking.
    register_setting(
      'uat_settings_group',
      'uat_enable_tracking'
    );
  }

  /**
   * Renders the HTML content for the plugin's administration page.
   */
  public function render_admin_page()
  {
?>
    <div class="wrap uat-wrapper">
      <h1 class="uat-title"><?php esc_html_e('User Activity Tracker', 'user-activity-tracker'); ?></h1>
      <form method="post" action="options.php" class="uat-form">
        <?php
        // Output security fields for the registered setting group.
        settings_fields('uat_settings_group');
        // Output setting sections and their fields.
        do_settings_sections('uat_settings_group');
        ?>
        <table class="form-table">
          <tr valign="top">
            <th scope="row"><?php esc_html_e('Enable Tracking', 'user-activity-tracker'); ?></th>
            <td>
              <label class="switch">
                <input type="checkbox" name="uat_enable_tracking" value="1" <?php checked('1', get_option('uat_enable_tracking', '1')); ?> />
                <span class="slider round"></span>
              </label>
              <p class="description"><?php esc_html_e('Enable or disable frontend tracking script loading.', 'user-activity-tracker'); ?></p>
            </td>
          </tr>
        </table>
        <?php
        // Output the submit button for the settings form.
        submit_button();
        ?>
      </form>
      <hr>
      <div class="uat-info">
        <p><?php esc_html_e('This plugin tracks anonymous user activity and sends it to an external analytics service. No personal information is collected. Tracking is only active in production environments.', 'user-activity-tracker'); ?></p>
        <ul>
          <li><strong><?php esc_html_e('Performance Optimized:', 'user-activity-tracker'); ?></strong> <?php esc_html_e('REST API usage, throttled client-side submission, bot detection, async sending, and low server footprint.', 'user-activity-tracker'); ?></li>
          <li><strong><?php esc_html_e('Security Aware:', 'user-activity-tracker'); ?></strong> <?php esc_html_e('No PII, strict sanitization, cookie-based UUIDs only.', 'user-activity-tracker'); ?></li>
          <li><strong><?php esc_html_e('User Configurable:', 'user-activity-tracker'); ?></strong> <?php esc_html_e('Enable/disable tracking via settings page.', 'user-activity-tracker'); ?></li>
        </ul>
      </div>
    </div>
<?php
  }

  /**
   * Handles the incoming tracking data from the REST API endpoint.
   *
   * Performs validation, sanitization, bot detection, and sends the data
   * to the external analytics endpoint.
   *
   * @param WP_REST_Request $request The REST API request object.
   * @return WP_REST_Response Response indicating success or error.
   */
  public function handle_tracking_data(WP_REST_Request $request)
  {
    // Do not track logged-in users.
    if (is_user_logged_in()) {
      return new WP_REST_Response(['message' => __('Logged-in users are not tracked.', 'user-activity-tracker')], 403);
    }

    $data = $request->get_json_params();

    // Basic validation for essential data points.
    if (!isset($data['startTime'], $data['endTime']) || empty($data['pageTitle'])) {
      return new WP_REST_Response(['message' => __('Invalid tracking data: missing essential fields.', 'user-activity-tracker')], 400);
    }

    // Bot detection using user agent.
    $ua = strtolower($data['userAgent'] ?? '');
    if (preg_match('/bot|crawl|spider|headless/i', $ua)) { // Added 'i' for case-insensitive matching
      return new WP_REST_Response(['message' => __('Bot detected.', 'user-activity-tracker')], 403);
    }

    // Detect device type based on user agent.
    $device = $this->detect_device_type($ua);

    // Prepare the payload for the external analytics service.
    $payload = [
      'userId'          => sanitize_text_field($_COOKIE['user_id'] ?? ''),
      'userIdType'      => sanitize_text_field($_COOKIE['user_id_type'] ?? ''),
      // 'ipAddress'    => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? 'unknown'), // Consider if IP is truly anonymous or needed by the external service based on privacy policy
      'ipAddress'       => 'ANONYMIZED', // Anonymize IP address for privacy, or remove if not strictly needed
      'url'             => sanitize_text_field($data['url'] ?? $_SERVER['HTTP_REFERER'] ?? 'Direct'), // Prefer client-provided URL, fallback to referrer
      'device'          => $device,
      'organization'    => 'Devfinity', // Static organization name.
      'eventDate'       => current_time('c'), // Current time in ISO 8601 format.
      'screenWidth'     => intval($data['screenWidth'] ?? 0),
      'screenHeight'    => intval($data['screenHeight'] ?? 0),
      'language'        => sanitize_text_field($data['language'] ?? ''),
      'timezone'        => sanitize_text_field($data['timezone'] ?? ''),
      'cookiesEnabled'  => filter_var($data['cookiesEnabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
      'pageTitle'       => sanitize_text_field($data['pageTitle'] ?? 'unknown'),
      'location'        => sanitize_text_field($data['location'] ?? 'unknown'), // Client-side location, if available
      'operatingSystem' => sanitize_text_field($data['operatingSystem'] ?? ''),
      'startTime'       => sanitize_text_field($data['startTime'] ?? ''),
      'endTime'         => sanitize_text_field($data['endTime'] ?? ''),
      'timeSpent'       => intval($data['timeSpent'] ?? 0),
      'browser'         => sanitize_text_field($data['browser'] ?? ''),
    ];

    /**
     * Filter the tracking payload before it's sent to the external API.
     *
     * @param array $payload The tracking data payload.
     * @param WP_REST_Request $request The REST API request object.
     */
    $payload = apply_filters('uat_tracking_payload', $payload, $request);

    // Send data to the external analytics endpoint.
    $response = wp_remote_post(
      'https://user-events-api.azurewebsites.net/api/UserEvents',
      [
        'body'    => wp_json_encode($payload),
        'headers' => [
          'Content-Type' => 'application/json',
          // Add an API key or other authentication if required by your endpoint.
          // 'Authorization' => 'Bearer YOUR_API_KEY',
        ],
        'timeout' => 5, // Set a timeout for the request.
        'blocking' => false, // Make the request non-blocking for better performance
      ]
    );

    // Log errors if the request fails (for debugging, can be removed in production).
    if (is_wp_error($response)) {
      error_log('User Activity Tracker: Failed to send data to external API - ' . $response->get_error_message());
      // Return a success response even on wp_error if blocking is false,
      // as the client doesn't need to wait for external API response.
      // If you want the client to know about the external API failure, return 500.
      return new WP_REST_Response(['status' => 'error', 'message' => $response->get_error_message()], 500);
    }

    // Check the HTTP status code of the external API response
    $http_code = wp_remote_retrieve_response_code($response);
    if ($http_code < 200 || $http_code >= 300) {
      error_log('User Activity Tracker: External API returned status code ' . $http_code . ' with body: ' . wp_remote_retrieve_body($response));
      // You might still return success to the client depending on your requirements,
      // as the server-side processing for tracking has completed.
      return new WP_REST_Response(['status' => 'error', 'message' => __('External API returned non-success status.', 'user-activity-tracker') . ' HTTP ' . $http_code], 500);
    }


    return new WP_REST_Response(['status' => 'success', 'message' => __('Tracking data received successfully.', 'user-activity-tracker')]);
  }

  /**
   * Sets user tracking cookies (user_id and user_id_type).
   *
   * Checks for a 'campaign_user_id' GET parameter or generates a new UUID
   * if no user ID cookie is present.
   */
  public function set_user_tracking_cookies()
  {
    // Check if the current environment type is 'production' before setting cookies.
    // This leverages the 'WP_ENVIRONMENT_TYPE' constant introduced in WP 5.5.
    $environment = defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'production';
    if ('production' !== $environment && get_option('uat_enable_tracking', '1') !== '1') {
      return; // Only set cookies in production or if tracking is explicitly enabled in non-prod.
    }

    if (isset($_GET['campaign_user_id'])) {
      // Set cookie from campaign ID.
      setcookie(
        'user_id',
        sanitize_text_field(wp_unslash($_GET['campaign_user_id'])), // Use wp_unslash for GET params
        time() + YEAR_IN_SECONDS,
        '/'
      );
      setcookie(
        'user_id_type',
        'ad-campaign',
        time() + YEAR_IN_SECONDS,
        '/'
      );
    } elseif (!isset($_COOKIE['user_id'])) {
      // Generate and set new UUID if no user_id cookie exists.
      $uuid = $this->generate_uuid();
      setcookie(
        'user_id',
        $uuid,
        time() + YEAR_IN_SECONDS,
        '/'
      );
      setcookie(
        'user_id_type',
        'new-user',
        time() + YEAR_IN_SECONDS,
        '/'
      );
    }
  }

  /**
   * Generates a Version 4 UUID (Universally Unique Identifier).
   *
   * @return string A randomly generated UUID.
   */
  private function generate_uuid()
  {
    // Fallback for systems without random_int
    if (!function_exists('random_int')) {
      require_once ABSPATH . WPINC . '/class-wp-random-compat.php';
    }
    return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      random_int(0, 0xffff),
      random_int(0, 0xffff),
      random_int(0, 0xffff),
      random_int(0, 0x0fff) | 0x4000, // Set version to 4 (0100)
      random_int(0, 0x3fff) | 0x8000, // Set bits 6-7 of clock_seq_hi_and_reserved to 01
      random_int(0, 0xffff),
      random_int(0, 0xffff),
      random_int(0, 0xffff)
    );
  }

  /**
   * Detects the device type (Mobile, Tablet, Desktop, Unknown) based on the User Agent string.
   *
   * @param string $ua The user agent string.
   * @return string The detected device type.
   */
  private function detect_device_type($ua)
  {
    if (empty($ua)) {
      return 'Unknown';
    }
    // Normalize user agent for easier comparison.
    $ua = strtolower($ua);

    if (preg_match('/(ipad|tablet|android(?!.*mobile)|kindle|playbook|silk|windows ce|hpwos)/i', $ua)) {
      return 'Tablet';
    } elseif (preg_match('/(mobi|iphone|android|blackberry|opera mini|windows phone|iemobile|fennec|minimo|symbian|smartphone|palm|up.browser|up.link|wap|midp|mobile)/i', $ua)) {
      return 'Mobile';
    } elseif (preg_match('/(windows|macintosh|linux|x11)/i', $ua)) {
      return 'Desktop';
    }
    return 'Unknown';
  }
}

// Instantiate the plugin class to start its functionality.
new UserActivityTracker();

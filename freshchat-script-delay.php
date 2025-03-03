<?php
/**
 * Plugin Name:        Freshchat Script Delay
 * Plugin URI:         https://github.com/WPSpeedExpert/freshchat-script-delay
 * Description:        Forcefully delays the loading of the Freshchat script until user interaction. Includes a settings page to configure the Freshchat token.
 * Version:            2.8.3
 * Author:             OctaHexa
 * Author URI:         https://octahexa.com
 * Text Domain:        freshchat-delay
 * License:            GPL-2.0+
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI:  https://github.com/WPSpeedExpert/freshchat-script-delay
 * Primary Branch:     main
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// === SETTINGS PAGE ===
add_action('admin_menu', 'freshchat_delay_settings_page');
function freshchat_delay_settings_page() {
    add_options_page(
        'Freshchat Delay', // Page title
        'Freshchat Delay', // Menu title
        'manage_options',  // Capability
        'freshchat-delay-settings', // Menu slug
        'freshchat_delay_settings_page_html' // Callback function
    );
}

function freshchat_delay_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save Freshchat token
    if (isset($_POST['freshchat_token']) && check_admin_referer('freshchat_delay_settings_save', 'freshchat_delay_settings_nonce')) {
        update_option('freshchat_widget_token', sanitize_text_field($_POST['freshchat_token']));
        echo '<div class="updated"><p>Token saved successfully.</p></div>';
    }

    $token = get_option('freshchat_widget_token', '');
    ?>
    <div class="wrap">
        <h1>Freshchat Delay</h1>
        <form method="post" action="">
            <?php wp_nonce_field('freshchat_delay_settings_save', 'freshchat_delay_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="freshchat_token">Freshchat Widget Token</label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="freshchat_token"
                            name="freshchat_token"
                            value="<?php echo esc_attr($token); ?>"
                            class="regular-text"
                        />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p><strong>How this plugin works:</strong></p>
        <ul>
            <li>The Freshchat widget loads only after user interaction (mouse movement, scroll, click, or key press).</li>
            <li>You can find your Freshchat widget token in the Freshchat plugin settings or your Freshchat account settings.</li>
        </ul>
    </div>
    <?php
}

// === REMOVE DEFAULT SCRIPT ===
add_action('wp_head', 'remove_default_freshchat_script', 1);
function remove_default_freshchat_script() {
    remove_action('wp_footer', 'add_fc'); // Ensure Freshchat's default script is removed
}

// === DELAYED SCRIPT LOADING ===
add_action('wp_footer', 'freshchat_delay_script', 99);
function freshchat_delay_script() {
    $token = get_option('freshchat_widget_token', '');

    if (empty($token)) {
        return; // Exit if no token is set
    }

    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let isWidgetLoaded = false;

            function loadFreshchatWidget() {
                if (isWidgetLoaded) return;
                isWidgetLoaded = true;

                console.log('Loading Freshchat widget...');
                window.fcSettings = {
                    token: "<?php echo esc_js($token); ?>",
                    host: "https://wchat.freshchat.com",
                    firstName: "<?php echo esc_js(wp_get_current_user()->display_name); ?>",
                    email: "<?php echo esc_js(wp_get_current_user()->user_email); ?>"
                };

                (function (d, t) {
                    var fc = d.createElement(t), s = d.getElementsByTagName(t)[0];
                    fc.src = "https://wchat.freshchat.com/js/widget.js";
                    fc.async = true;
                    fc.defer = true;
                    s.parentNode.insertBefore(fc, s);
                })(document, "script");
            }

            const interactionEvents = ['mousemove', 'scroll', 'click', 'keydown'];
            const triggerInteraction = () => {
                loadFreshchatWidget();
                interactionEvents.forEach(e => window.removeEventListener(e, triggerInteraction));
            };

            interactionEvents.forEach(e => window.addEventListener(e, triggerInteraction));
        });
    </script>
    <?php
}

// === SETTINGS LINK IN PLUGIN PAGE ===
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'freshchat_delay_action_links');
function freshchat_delay_action_links($links) {
    $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=freshchat-delay-settings')) . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

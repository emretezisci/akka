<?php
class Akka_Pro_Notification {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_notification_scripts'));
        add_action('wp_footer', array($this, 'add_notification_container'));
    }

    public function enqueue_notification_scripts() {
        wp_enqueue_script('akka-pro-notification-js', AKKA_PRO_PLUGIN_URL . 'assets/js/notification.js', array('jquery'), null, true);
        wp_enqueue_style('akka-pro-notification-css', AKKA_PRO_PLUGIN_URL . 'assets/css/notification.css', array(), null);

        // Pass default notification image URL to the script
        wp_localize_script('akka-pro-notification-js', 'akkaProNotification', array(
            'defaultImageUrl' => AKKA_PRO_PLUGIN_URL . 'assets/images/default-notification-icon.png' 
        ));
    }

    public function add_notification_container() {
        ?>
        <div class="notification-container">
            <!-- Notifications will be injected here -->
        </div>
        <?php
    }

    // Function to display a notification
    public static function show_notification($message, $type = 'success', $title = '', $imageUrl = null) {
        // Use JavaScript to display the notification
        ?>
        <script>
            jQuery(document).ready(function($) {
                createNotification("<?php echo esc_js($title); ?>", "<?php echo esc_js($message); ?>", "<?php echo esc_js($type); ?>", "<?php echo esc_js($imageUrl); ?>");
            });
        </script>
        <?php
    }
}

new Akka_Pro_Notification();
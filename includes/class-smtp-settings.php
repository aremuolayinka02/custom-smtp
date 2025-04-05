<?php
if (!defined('ABSPATH')) {
    exit;
}

class SMTP_Settings
{
    private $options;

    public function __construct()
    {
        $this->options = get_option('custom_smtp_settings');
        add_action('admin_head', array($this, 'add_admin_styles'));
    }

    public function add_settings_page()
    {
        add_menu_page(
            'Custom SMTP Settings',
            'SMTP Settings',
            'manage_options',
            'custom-smtp-settings',
            array($this, 'render_settings_page'),
            'dashicons-email-alt'
        );
    }

    public function register_settings()
    {
        register_setting('custom_smtp_settings_group', 'custom_smtp_settings', array($this, 'sanitize_settings'));

        add_settings_section(
            'custom_smtp_main_section',
            'SMTP Configuration',
            array($this, 'section_description'),
            'custom-smtp-settings'
        );

        // Add settings fields
        $this->add_settings_fields();
    }

    private function add_settings_fields()
    {
        $fields = array(
            'smtp_host' => 'SMTP Host',
            'smtp_port' => 'SMTP Port',
            'smtp_encryption' => 'Encryption',
            'smtp_auth' => 'Authentication',
            'smtp_username' => 'Username',
            'smtp_password' => 'Password',
            'from_email' => 'From Email',
            'from_name' => 'From Name'
        );

        foreach ($fields as $field_id => $field_label) {
            add_settings_field(
                $field_id,
                $field_label,
                array($this, 'render_field'),
                'custom-smtp-settings',
                'custom_smtp_main_section',
                array('field_id' => $field_id)
            );
        }
    }



    public function render_test_field($args)
    {
        $field_id = $args['field_id'];
?>
        <?php if ($field_id === 'test_message'): ?>
            <textarea id="<?php echo esc_attr($field_id); ?>"
                name="<?php echo esc_attr($field_id); ?>"
                rows="5"
                cols="50"><?php echo isset($_POST[$field_id]) ? esc_textarea($_POST[$field_id]) : ''; ?></textarea>
        <?php else: ?>
            <input type="text"
                id="<?php echo esc_attr($field_id); ?>"
                name="<?php echo esc_attr($field_id); ?>"
                value="<?php echo isset($_POST[$field_id]) ? esc_attr($_POST[$field_id]) : ''; ?>"
                class="regular-text">
        <?php endif; ?>
    <?php
    }

    // Add this method for test section description
    public function test_section_description()
    {
        echo '<p>Send a test email to verify your SMTP settings:</p>';
    }

    public function render_settings_page()
    {
        if (isset($_POST['send_test_email'])) {
            $this->handle_test_email();
        }
    ?>
        <div class="wrap">
            <h2>Custom SMTP Settings</h2>

            <!-- SMTP Settings Form -->
            <form method="post" action="options.php">
                <?php
                settings_fields('custom_smtp_settings_group');
                do_settings_sections('custom-smtp-settings');
                submit_button('Save Settings');
                ?>
            </form>

            <!-- Test Email Form -->
            <form method="post" action="">
                <h3>Send Test Email</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="test_to_email">To Email:</label></th>
                        <td><input type="email" name="test_to_email" id="test_to_email" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="test_subject">Subject:</label></th>
                        <td><input type="text" name="test_subject" id="test_subject" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="test_message">Message:</label></th>
                        <td><textarea name="test_message" id="test_message" rows="5" cols="50" required></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Send Test Email', 'secondary', 'send_test_email'); ?>
            </form>
        </div>
    <?php
    }

    // Add this method to the SMTP_Settings class
    public function send_test_email()
    {
        $to = get_option('admin_email');
        $subject = 'SMTP Test Email';
        $message = 'This is a test email from your WordPress site using the Custom SMTP plugin.';
        $headers = array('Content-Type: text/html; charset=UTF-8');

        $result = wp_mail($to, $subject, $message, $headers);

        return $result;
    }

    public function handle_test_email()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        $to = sanitize_email($_POST['test_to_email']);
        $subject = sanitize_text_field($_POST['test_subject']);
        $message = wp_kses_post($_POST['test_message']);
        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Send the email
        $result = wp_mail($to, $subject, $message, $headers);

        // Log the email
        $logger = new SMTP_Logger();
        $logger->log_email(
            $to,
            $subject,
            $message,
            $headers,
            $result ? 'success' : 'failed',
            $result ? '' : 'Failed to send email'
        );

        // Show success/error message
        if ($result) {
            add_settings_error(
                'custom_smtp_settings',
                'test_email_sent',
                'Test email sent successfully!',
                'updated'
            );
        } else {
            add_settings_error(
                'custom_smtp_settings',
                'test_email_failed',
                'Failed to send test email. Please check your SMTP settings.',
                'error'
            );
        }
    }

    public function add_admin_styles()
    {
    ?>
        <style>
            .nav-tab-wrapper {
                margin-bottom: 20px;
            }

            .tab-content {
                padding: 20px;
                background: #fff;
                border: 1px solid #ccd0d4;
                border-top: none;
            }
        </style>
        <?php
    }

    public function render_field($args)
    {
        $field_id = $args['field_id'];
        $value = isset($this->options[$field_id]) ? $this->options[$field_id] : '';

        switch ($field_id) {
            case 'smtp_encryption':
        ?>
                <select name="custom_smtp_settings[<?php echo $field_id; ?>]">
                    <option value="none" <?php selected($value, 'none'); ?>>None</option>
                    <option value="ssl" <?php selected($value, 'ssl'); ?>>SSL</option>
                    <option value="tls" <?php selected($value, 'tls'); ?>>TLS</option>
                </select>
            <?php
                break;

            case 'smtp_auth':
            ?>
                <select name="custom_smtp_settings[<?php echo $field_id; ?>]">
                    <option value="yes" <?php selected($value, 'yes'); ?>>Yes</option>
                    <option value="no" <?php selected($value, 'no'); ?>>No</option>
                </select>
            <?php
                break;

            case 'smtp_password':
            ?>
                <input type="password" name="custom_smtp_settings[<?php echo $field_id; ?>]" value="<?php echo esc_attr($value); ?>" class="regular-text">
            <?php
                break;

            default:
            ?>
                <input type="text" name="custom_smtp_settings[<?php echo $field_id; ?>]" value="<?php echo esc_attr($value); ?>" class="regular-text">
<?php
                break;
        }
    }

    public function section_description()
    {
        echo '<p>Configure your SMTP settings below:</p>';
    }

    public function sanitize_settings($input)
    {
        $sanitized = array();

        foreach ($input as $key => $value) {
            switch ($key) {
                case 'smtp_port':
                    $sanitized[$key] = absint($value);
                    break;
                case 'smtp_password':
                    $sanitized[$key] = $value; // Consider encrypting the password
                    break;
                default:
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
            }
        }

        return $sanitized;
    }
}

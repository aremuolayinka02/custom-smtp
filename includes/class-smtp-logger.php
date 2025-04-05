<?php
if (!defined('ABSPATH')) {
    exit;
}

class SMTP_Logger
{
    private $options;
    private $smtp_settings;

    public function __construct()
    {
        $this->options = get_option('custom_smtp_settings');
        $this->smtp_settings = new SMTP_Settings();
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

        // Add test email section
        add_settings_section(
            'custom_smtp_test_section',
            'Test Email Settings',
            array($this, 'test_section_description_import'),
            'custom-smtp-settings'
        );

        // Add test email fields
        $test_fields = array(
            'test_to_email' => 'To Email',
            'test_subject' => 'Subject',
            'test_message' => 'Message'
        );

        foreach ($test_fields as $field_id => $field_label) {
            add_settings_field(
                $field_id,
                $field_label,
                array($this, 'render_test_field'),
                'custom-smtp-settings',
                'custom_smtp_test_section',
                array('field_id' => $field_id)
            );
        }
    }

    public function render_settings_page()
    {

        // Handle test email submission
        if (isset($_POST['send_test_email'])) {
            $this->handle_test_email();
        }
        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
?>
        <div class="wrap">
            <h2>Custom SMTP Settings</h2>

            <!-- Tabs -->
            <nav class="nav-tab-wrapper">
                <a href="?page=custom-smtp-settings&tab=settings"
                    class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    SMTP Settings
                </a>
                <a href="?page=custom-smtp-settings&tab=test"
                    class="nav-tab <?php echo $current_tab === 'test' ? 'nav-tab-active' : ''; ?>">
                    Test Email
                </a>
            </nav>

            <div class="tab-content">
                <?php
                if ($current_tab === 'settings') {
                    $this->render_settings_tab();
                } else {
                    $this->render_test_email_tab();
                }
                ?>
            </div>
        </div>
    <?php
    }

    private function render_settings_tab()
    {
    ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('custom_smtp_settings_group');
            do_settings_sections('custom-smtp-settings');
            submit_button('Save Settings');
            ?>
        </form>
    <?php
    }

    private function render_test_email_tab()
    {
    ?>
        <form method="post" action="">
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

    public function test_section_description_import()
    {
        $this->smtp_settings->test_section_description();
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

<?php
if (!defined('ABSPATH')) {
    exit;
}

class SMTP_Mailer {
    private $options;

    public function __construct() {
        $this->options = get_option('custom_smtp_settings');
    }

    public function configure_smtp($phpmailer) {
        // If SMTP settings are not configured, return early
        if (empty($this->options['smtp_host'])) {
            return;
        }

        // Configure PHPMailer to use SMTP
        $phpmailer->isSMTP();
        $phpmailer->Host = $this->options['smtp_host'];
        $phpmailer->SMTPAuth = ($this->options['smtp_auth'] === 'yes');
        
        if ($phpmailer->SMTPAuth) {
            $phpmailer->Username = $this->options['smtp_username'];
            $phpmailer->Password = $this->options['smtp_password'];
        }

        // Set encryption type
        if ($this->options['smtp_encryption'] !== 'none') {
            $phpmailer->SMTPSecure = $this->options['smtp_encryption'];
        }

        $phpmailer->Port = $this->options['smtp_port'];

        // Set from email and name if configured
        if (!empty($this->options['from_email'])) {
            $phpmailer->From = $this->options['from_email'];
        }
        if (!empty($this->options['from_name'])) {
            $phpmailer->FromName = $this->options['from_name'];
        }

        // Enable debug mode for development
        // $phpmailer->SMTPDebug = 2;
    }
}
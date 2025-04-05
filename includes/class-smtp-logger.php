<?php
if (!defined('ABSPATH')) {
    exit;
}

class SMTP_Logger
{
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'smtp_email_logs';

        // Create the logging table if it doesn't exist
        $this->create_log_table();
    }

    private function create_log_table()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            to_email varchar(255) NOT NULL,
            subject varchar(255) NOT NULL,
            message text NOT NULL,
            headers text,
            status varchar(20) NOT NULL,
            error_message text,
            date_sent datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function log_email($to, $subject, $message, $headers, $status = 'success', $error_message = '')
    {
        global $wpdb;

        return $wpdb->insert(
            $this->table_name,
            array(
                'to_email' => $to,
                'subject' => $subject,
                'message' => $message,
                'headers' => is_array($headers) ? serialize($headers) : $headers,
                'status' => $status,
                'error_message' => $error_message,
                'date_sent' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }

    public function get_logs($limit = 50, $offset = 0)
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} ORDER BY date_sent DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );
    }

    public function delete_log($id)
    {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }

    public function clear_logs()
    {
        global $wpdb;

        return $wpdb->query("TRUNCATE TABLE {$this->table_name}");
    }
}
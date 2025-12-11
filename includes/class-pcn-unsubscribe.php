<?php
if (! defined('ABSPATH')) {
    exit;
}

class PCN_Unsubscribe {

    public static function init() {
        add_action('init', array(__CLASS__, 'handle_unsubscribe_request'));
    }

    public static function handle_unsubscribe_request() {
        if (isset($_GET['pcn_action']) && $_GET['pcn_action'] === 'unsubscribe' && isset($_GET['email']) && isset($_GET['key'])) {
            $email = sanitize_email($_GET['email']);
            $key = sanitize_text_field($_GET['key']);

            if (self::verify_key($email, $key)) {
                self::add_to_blocklist($email);
                wp_die(
                    __('您已成功取消订阅本站的评论回复通知。', 'wp-comment-notify'),
                    __('取消订阅成功', 'wp-comment-notify'),
                    array('response' => 200)
                );
            } else {
                wp_die(
                    __('链接无效或已过期。', 'wp-comment-notify'),
                    __('错误', 'wp-comment-notify'),
                    array('response' => 403)
                );
            }
        }
    }

    public static function get_unsubscribe_url($email) {
        $key = self::generate_key($email);
        return add_query_arg(array(
            'pcn_action' => 'unsubscribe',
            'email' => urlencode($email),
            'key' => $key
        ), home_url('/'));
    }

    public static function is_unsubscribed($email) {
        $blocklist = get_option('pcn_unsubscribe_list', array());
        return in_array($email, $blocklist);
    }

    private static function add_to_blocklist($email) {
        $blocklist = get_option('pcn_unsubscribe_list', array());
        if (! in_array($email, $blocklist)) {
            $blocklist[] = $email;
            update_option('pcn_unsubscribe_list', $blocklist);
        }
    }

    private static function generate_key($email) {
        return wp_hash($email . 'pcn_unsubscribe_salt');
    }

    private static function verify_key($email, $key) {
        return hash_equals(self::generate_key($email), $key);
    }
}

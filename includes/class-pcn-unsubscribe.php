<?php
if (! defined('ABSPATH')) {
    exit;
}

class PCN_Unsubscribe {

    private static function get_request_ip($request) {
        $server = array();
        if (is_object($request) && method_exists($request, 'get_server_params')) {
            $server = (array) $request->get_server_params();
        } else if (! empty($_SERVER) && is_array($_SERVER)) {
            $server = $_SERVER;
        }

        $ip = '';
        if (! empty($server['REMOTE_ADDR'])) {
            $ip = trim((string) $server['REMOTE_ADDR']);
        }

        // Fallback to common proxy headers (best-effort; used only for rate limiting/logging)
        if (empty($ip) && is_object($request) && method_exists($request, 'get_header')) {
            $candidates = array(
                $request->get_header('cf-connecting-ip'),
                $request->get_header('x-real-ip'),
                $request->get_header('x-forwarded-for'),
            );
            foreach ($candidates as $h) {
                if (! empty($h)) {
                    $raw = trim((string) $h);
                    // x-forwarded-for may contain a list
                    $parts = explode(',', $raw);
                    $maybe = trim((string) ($parts[0] ?? ''));
                    if ($maybe !== '') {
                        $ip = $maybe;
                        break;
                    }
                }
            }
        }

        return $ip;
    }

    public static function init() {
        add_action('init', array(__CLASS__, 'handle_unsubscribe_request'));
        // Register REST routes for secure unsubscribe link handling
        add_action('rest_api_init', array(__CLASS__, 'register_rest_routes'));
    }

    public static function register_rest_routes() {
        if (function_exists('register_rest_route')) {
            register_rest_route('pcn/v1', '/unsubscribe', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array(__CLASS__, 'rest_unsubscribe_handler'),
                'permission_callback' => '__return_true',
            ));
        }
    }

    public static function handle_unsubscribe_request() {
        if (isset($_GET['pcn_action']) && $_GET['pcn_action'] === 'unsubscribe' && isset($_GET['email']) && isset($_GET['ts']) && isset($_GET['sig'])) {
            $email = sanitize_email($_GET['email']);
            $ts = intval($_GET['ts']);
            $sig = sanitize_text_field($_GET['sig']);

            // Verify signature and expiry (短期有效 — 7 天)
            $max_age = 7 * 24 * 3600;
            if (! self::verify_sig($email, $ts, $sig)) {
                wp_die(esc_html__('链接无效或已被篡改。', 'wp-comment-notify'), esc_html__('错误', 'wp-comment-notify'), array('response' => 403));
                return;
            }
            if (time() - $ts > $max_age) {
                wp_die(esc_html__('链接已过期。请在后台重新生成并发送邮件。', 'wp-comment-notify'), esc_html__('链接过期', 'wp-comment-notify'), array('response' => 403));
                return;
            }

            // Check existing state
            if (self::is_unsubscribed($email)) {
                wp_die(esc_html__('该邮箱已取消订阅，无需重复操作。', 'wp-comment-notify'), esc_html__('已取消订阅', 'wp-comment-notify'), array('response' => 200));
                return;
            }

            // Record the unsubscribe with timestamp
            self::add_to_blocklist($email, $ts);
            wp_die(esc_html__('您已成功取消订阅本站的评论回复通知。', 'wp-comment-notify'), esc_html__('取消订阅成功', 'wp-comment-notify'), array('response' => 200));
        }
    }

    public static function get_unsubscribe_url($email) {
        $email = sanitize_email($email);
        $ts = time();
        $sig = self::generate_sig($email, $ts);
        // Point to REST route; keep same query params for compatibility
        $base = rest_url('pcn/v1/unsubscribe');
        $args = array(
            'email' => rawurlencode($email),
            'ts' => $ts,
            'sig' => $sig,
        );
        return esc_url_raw(add_query_arg($args, $base));
    }

    public static function rest_unsubscribe_handler($request) {
        $params = $request->get_params();
        $email = isset($params['email']) ? sanitize_email(rawurldecode($params['email'])) : '';
        $ts = isset($params['ts']) ? intval($params['ts']) : 0;
        $sig = isset($params['sig']) ? sanitize_text_field($params['sig']) : '';

        // Basic validation
        if (empty($email) || ! is_email($email) || empty($ts) || empty($sig)) {
            return new WP_REST_Response(array('success' => false, 'message' => __('Invalid request', 'wp-comment-notify')), 400);
        }

        // Rate limiting: limit to 10 attempts per hour per IP+email
        $ip = self::get_request_ip($request);
        $rl_key = 'pcn_unsub_rl_' . md5($ip . '|' . $email);
        $rl = get_transient($rl_key);
        if (! is_array($rl)) { $rl = array('count' => 0, 'first' => time()); }
        $rl['count'] = intval($rl['count']) + 1;
        // window 1 hour
        $window = 3600;
        if ($rl['count'] > 10) {
            return new WP_REST_Response(array('success' => false, 'message' => __('Too many unsubscribe attempts, please try later.', 'wp-comment-notify')), 429);
        }
        set_transient($rl_key, $rl, $window);

        // Verify signature and expiry (7 days)
        $max_age = 7 * 24 * 3600;
        if (! self::verify_sig($email, $ts, $sig)) {
            self::log_unsubscribe_action($email, $ip, false, 'invalid_sig');
            return new WP_REST_Response(array('success' => false, 'message' => __('Signature invalid or tampered.', 'wp-comment-notify')), 403);
        }
        if (time() - $ts > $max_age) {
            self::log_unsubscribe_action($email, $ip, false, 'expired');
            return new WP_REST_Response(array('success' => false, 'message' => __('Link expired.', 'wp-comment-notify')), 410);
        }

        // Already unsubscribed?
        if (self::is_unsubscribed($email)) {
            self::log_unsubscribe_action($email, $ip, false, 'already_unsubscribed');
            return new WP_REST_Response(array('success' => true, 'message' => __('Already unsubscribed.', 'wp-comment-notify')));
        }

        // Record and respond
        self::add_to_blocklist($email, $ts);
        self::log_unsubscribe_action($email, $ip, true, 'ok');
        return new WP_REST_Response(array('success' => true, 'message' => __('You have been unsubscribed.', 'wp-comment-notify')));
    }

    private static function log_unsubscribe_action($email, $ip, $success, $reason = '') {
        $actions = get_option('pcn_unsubscribe_actions', array());
        if (! is_array($actions)) { $actions = array(); }
        $actions[] = array(
            'time' => current_time('mysql'),
            'email' => $email,
            'ip' => $ip,
            'success' => $success ? 1 : 0,
            'reason' => $reason,
        );
        // keep last 500 actions
        if (count($actions) > 500) { $actions = array_slice($actions, -500); }
        update_option('pcn_unsubscribe_actions', $actions, false);
        if (class_exists('PCN_Settings') && method_exists('PCN_Settings', 'debug_log_append')) {
            PCN_Settings::debug_log_append('[unsubscribe] ' . ($success ? 'ok' : 'fail') . ' ' . $email . ' ip=' . $ip . ' reason=' . $reason);
        }
    }

    public static function is_unsubscribed($email) {
        $blocklist = get_option('pcn_unsubscribe_list', array());
        if (! is_array($blocklist)) {
            return false;
        }
        return isset($blocklist[$email]);
    }

    private static function add_to_blocklist($email, $ts = null) {
        $blocklist = get_option('pcn_unsubscribe_list', array());
        if (! is_array($blocklist)) {
            $blocklist = array();
        }
        $blocklist[$email] = $ts ? intval($ts) : time();
        update_option('pcn_unsubscribe_list', $blocklist, false);
    }

    private static function generate_sig($email, $ts) {
        $key = wp_salt('pcn_unsubscribe');
        return hash_hmac('sha256', $email . '|' . intval($ts), $key);
    }

    private static function verify_sig($email, $ts, $sig) {
        $expected = self::generate_sig($email, $ts);
        return hash_equals($expected, $sig);
    }
}

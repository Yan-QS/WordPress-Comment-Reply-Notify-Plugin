<?php
/**
 * 改良版的评论通知实现（供插件使用）
 * 放在插件的 includes 目录中并由主插件文件加载。
 */

if (! defined('ABSPATH')) {
    exit;
}

// 设置 HTML content-type 的回调（具名以便移除）
function pcn_set_html_content_type() {
    return 'text/html';
}

// 安全地清理头部字段，防止 header 注入
function pcn_safe_header($value) {
    return str_replace(array("\r", "\n"), '', $value);
}

// 主处理函数：在评论提交后发送通知
function pk_comment_mail_notify($comment_id) {
    // 若未启用插件，则直接跳过
    if (! get_option('pcn_enabled', 1)) {
        return;
    }
    $comment = get_comment($comment_id);
    if (! $comment) {
        return;
    }

    $admin_email = get_bloginfo('admin_email');
    $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    global $wpdb;
    $comments_waiting = $wpdb->get_var("SELECT count(comment_ID) FROM $wpdb->comments WHERE comment_approved = '0'");

    $parent_id = $comment->comment_parent ? $comment->comment_parent : 0;
    $spam_confirmed = $comment->comment_approved; // 'spam', 0, 1 ...

    // 1) 回复通知：当此评论是对已有评论的回复时，通知父评论作者（若不是管理员自己）
    if ($parent_id && $spam_confirmed !== 'spam') {
        $parent_comment = get_comment($parent_id);
        if ($parent_comment) {
            $parent_author_email = trim($parent_comment->comment_author_email);
            if ($parent_author_email && $parent_author_email !== $admin_email) {
                $to = $parent_author_email;

                // 清理并设置 From
                // 不再在 headers 中手工设置 From，改为由 PHPMailer 的 setFrom 统一控制
                $headers = array();

                // 使用安全过滤后的内容
                $safe_parent_author = esc_html(trim($parent_comment->comment_author));
                $safe_parent_content = wp_kses_post($parent_comment->comment_content);
                $safe_reply_author = esc_html(trim($comment->comment_author));
                $safe_reply_content = wp_kses_post($comment->comment_content);

                $subject = sprintf(__('您在 [%s] 的留言有了新回复！', 'wp-comment-notify'), $blogname);
                $message = pcn_get_template('reply', array(
                    'blogname' => $blogname,
                    'parent_author' => $safe_parent_author,
                    'parent_content' => $safe_parent_content,
                    'reply_author' => $safe_reply_author,
                    'reply_content' => $safe_reply_content,
                    'comment_link' => get_comment_link($parent_id),
                ));

                add_filter('wp_mail_content_type', 'pcn_set_html_content_type');
                $sent = wp_mail($to, $subject, $message, $headers);
                remove_filter('wp_mail_content_type', 'pcn_set_html_content_type');
                if (! $sent) {
                    error_log('pcn: 回复通知邮件发送失败，comment_id=' . $comment_id);
                }
            }
        }
    }

    // 2) 管理员新评论通知（当 parent_id == 0，即顶级评论且非管理员发起）
    if (! $parent_id && (trim($comment->comment_author_email) !== trim($admin_email)) && $spam_confirmed !== 'spam' && $comment->comment_approved != 0) {
        $to = $admin_email;
        // 不再在 headers 中手工设置 From，改为由 PHPMailer 的 setFrom 统一控制
        $headers = array();

        $safe_author = esc_html($comment->comment_author);
        $safe_content = wp_kses_post($comment->comment_content);
        $subject = sprintf(__('在「%s」的文章《%s》有新的评论', 'wp-comment-notify'), $blogname, get_the_title($comment->comment_post_ID));

        $message = pcn_get_template('new_comment', array(
            'author' => $safe_author,
            'content' => $safe_content,
            'post_title' => get_the_title($comment->comment_post_ID),
            'comment_id' => $comment_id,
            'comments_waiting' => intval($comments_waiting),
        ));
        add_filter('wp_mail_content_type', 'pcn_set_html_content_type');
        $sent = wp_mail($to, $subject, $message, $headers);
        remove_filter('wp_mail_content_type', 'pcn_set_html_content_type');
        if (! $sent) {
            error_log('pcn: 管理员新评论通知邮件发送失败，comment_id=' . $comment_id);
        }
    }

    // 3) 需要审核时通知管理员（comment_approved == 0）
    if (! $parent_id && (trim($comment->comment_author_email) !== trim($admin_email)) && $spam_confirmed !== 'spam' && $comment->comment_approved == 0) {
        $to = $admin_email;
        // 不再在 headers 中手工设置 From，改为由 PHPMailer 的 setFrom 统一控制
        $headers = array();

        $safe_author = esc_html($comment->comment_author);
        $safe_content = wp_kses_post($comment->comment_content);
        $subject = sprintf(__('在「%s」的文章《%s》中有新的评论需要审核', 'wp-comment-notify'), $blogname, get_the_title($comment->comment_post_ID));

        $message = pcn_get_template('pending', array(
            'author' => $safe_author,
            'content' => $safe_content,
            'post_title' => get_the_title($comment->comment_post_ID),
            'comment_id' => $comment_id,
            'comments_waiting' => intval($comments_waiting),
        ));

        add_filter('wp_mail_content_type', 'pcn_set_html_content_type');
        $sent = wp_mail($to, $subject, $message, $headers);
        remove_filter('wp_mail_content_type', 'pcn_set_html_content_type');
        if (! $sent) {
            error_log('pcn: 审核通知邮件发送失败，comment_id=' . $comment_id);
        }
    }
}

// 将函数挂载到 comment_post（与原实现保持一致）
add_action('comment_post', 'pk_comment_mail_notify');

/**
 * 从插件目录或数据库加载模板
 */
function pcn_get_template($name, $vars = array()) {
    $tpl_dir = PCN_PLUGIN_DIR . 'templates/';
    $path = $tpl_dir . $name . '.php';
    if (file_exists($path)) {
        extract($vars, EXTR_SKIP);
        ob_start();
        include $path;
        return ob_get_clean();
    }

    // 回退到数据库保存的模板（如果存在）
    $saved = get_option('pcn_templates', array());
    if (! empty($saved[$name])) {
        $tpl = $saved[$name];
        // 使用简单替换来填充变量（不执行 PHP）
        foreach ($vars as $k => $v) {
            $tpl = str_replace('{{' . $k . '}}', $v, $tpl);
        }
        return $tpl;
    }

    return '';
}

/**
 * 在发送邮件前，根据后台设置配置 PHPMailer（启用 SMTP、TLS、证书校验，若配置了 OAuth2 则尝试使用）
 */
function pcn_phpmailer_init($phpmailer) {
    $settings = get_option('pcn_smtp_settings', array());
    if (empty($settings['enable_smtp'])) {
        return;
    }

    // 开启 SMTP
    $phpmailer->isSMTP();
    if (! empty($settings['host'])) {
        $phpmailer->Host = $settings['host'];
    }
    // 主机名（EHLO/HELO）可提升兼容性
    if (function_exists('get_bloginfo')) {
        $siteHost = parse_url(home_url(), PHP_URL_HOST);
        if (! empty($siteHost)) {
            $phpmailer->Hostname = $siteHost;
        }
    }
    if (! empty($settings['port'])) {
        $phpmailer->Port = intval($settings['port']);
    }
    // 当使用 465/SSL 时，关闭 AutoTLS，避免 STARTTLS 干扰
    $phpmailer->SMTPAutoTLS = true;
    if (! empty($settings['encryption'])) {
        $phpmailer->SMTPSecure = $settings['encryption'];
    }
    if ((! empty($settings['port']) && intval($settings['port']) === 465) || (! empty($settings['encryption']) && $settings['encryption'] === 'ssl')) {
        $phpmailer->SMTPAutoTLS = false;
    }
    // 587/TLS 场景下，显式启用 STARTTLS
    if ((! empty($settings['port']) && intval($settings['port']) === 587) || (! empty($settings['encryption']) && $settings['encryption'] === 'tls')) {
        $phpmailer->SMTPSecure = 'tls';
        $phpmailer->SMTPAutoTLS = true;
    }

    // 与常见实现对齐：统一字符集与编码（改为 8bit）
    $phpmailer->CharSet = 'UTF-8';
    $phpmailer->Encoding = '8bit';

    // 避免长连接导致异常，默认不保持连接
    $phpmailer->SMTPKeepAlive = false;

    $phpmailer->SMTPAuth = ! empty($settings['smtp_auth']);
    if ($phpmailer->SMTPAuth && (! empty($settings['username']) || ! empty($settings['password']))) {
        $phpmailer->Username = $settings['username'];
        $phpmailer->Password = $settings['password'];
        // 若选择普通登录，按登录机制设置；AUTO 则让 PHPMailer 自行协商
        if (empty($settings['auth_type']) || $settings['auth_type'] === 'login') {
            $mechanism = ! empty($settings['login_mechanism']) ? strtoupper($settings['login_mechanism']) : 'AUTO';
            if ($mechanism === 'AUTO') {
                $phpmailer->AuthType = '';
            } else if (in_array($mechanism, array('LOGIN', 'PLAIN'), true)) {
                $phpmailer->AuthType = $mechanism;
            } else {
                $phpmailer->AuthType = '';
            }
        }
        // 记录一次轻量诊断（掩码用户名，仅域名）
        $masked = '';
        if (! empty($settings['username'])) {
            $parts = explode('@', $settings['username']);
            if (count($parts) === 2) {
                $masked = '***@' . $parts[1];
            }
        }
        $diag = 'pcn: SMTP auth using ' . ($phpmailer->AuthType ?: 'auto') . ', user=' . $masked . ', host=' . ($settings['host'] ?? '') . ', port=' . ($settings['port'] ?? '') . ', enc=' . ($settings['encryption'] ?? '');
        error_log($diag);
        // 同步到最近调试日志选项，便于在后台查看
        $line = '[diag] ' . str_replace('pcn: ', '', $diag);
        if (function_exists('pcn_debug_log_append')) {
            pcn_debug_log_append($line);
        } else {
            $logs = get_option('pcn_debug_log', array());
            if (! is_array($logs)) { $logs = array(); }
            $logs[] = $line;
            if (count($logs) > 500) { $logs = array_slice($logs, -500); }
            update_option('pcn_debug_log', $logs, false);
        }
    }

    // 统一设置发信地址/名称；可选强制与用户名一致
    try {
        $useUsername = ! empty($settings['force_from_username']) && ! empty($settings['username']);
        $fromEmail = $useUsername ? $settings['username'] : (! empty($settings['from_email']) ? $settings['from_email'] : $phpmailer->From);
        if (empty($fromEmail) && ! empty($settings['username'])) {
            $fromEmail = $settings['username'];
        }
        $fromName = ! empty($settings['from_name']) ? $settings['from_name'] : wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        if (! empty($fromEmail)) {
            $phpmailer->setFrom($fromEmail, $fromName, false);
        }
        // Envelope Sender（Return-Path）也设置为用户名以提升兼容性
        if (! empty($settings['username'])) {
            $phpmailer->Sender = $settings['username'];
        }
    } catch (\Exception $e) {
        error_log('pcn: 设置发信地址失败: ' . $e->getMessage());
    }

    // 强制使用 IPv4 连接（通过 socket 上下文 bindto 设置）
    if (! empty($settings['force_ipv4'])) {
        if (! isset($phpmailer->SMTPOptions['socket'])) {
            $phpmailer->SMTPOptions['socket'] = array();
        }
        $phpmailer->SMTPOptions['socket']['bindto'] = '0.0.0.0:0';
        error_log('pcn: SMTP forced to IPv4 via socket bindto');
    }

    // 强制证书校验（可通过设置添加 cafile）
    $ssl_opts = array(
        'verify_peer' => true,
        'verify_peer_name' => true,
        'allow_self_signed' => false,
    );
    if (! empty($settings['cafile'])) {
        $ssl_opts['cafile'] = $settings['cafile'];
    }
    $phpmailer->SMTPOptions = array('ssl' => $ssl_opts);

    // 如果选择 OAuth2，且库可用，则尝试设置 XOAUTH2
    if (! empty($settings['auth_type']) && $settings['auth_type'] === 'oauth2') {
        if (class_exists('\PHPMailer\\PHPMailer\\OAuth') && class_exists('\League\\OAuth2\\Client\\Provider\\Google')) {
            try {
                $provider = new \League\OAuth2\Client\Provider\Google([
                    'clientId' => $settings['client_id'],
                    'clientSecret' => $settings['client_secret'],
                ]);

                $phpmailer->AuthType = 'XOAUTH2';
                $phpmailer->setOAuth(new \PHPMailer\PHPMailer\OAuth([
                    'provider' => $provider,
                    'clientId' => $settings['client_id'],
                    'clientSecret' => $settings['client_secret'],
                    'refreshToken' => $settings['refresh_token'],
                    'userName' => $phpmailer->From,
                ]));
            } catch (Exception $e) {
                error_log('pcn: OAuth2 初始化失败: ' . $e->getMessage());
            }
        }
    }
}
add_action('phpmailer_init', 'pcn_phpmailer_init', PHP_INT_MAX);

?>

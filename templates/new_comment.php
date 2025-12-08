<?php
/**
 * New comment notification template
 * Available variables: author, content, post_title, comment_id, comments_waiting
 */
?>
<div style="font-family:Arial,Helvetica,sans-serif;color:#333;line-height:1.6;">
    <h3>新评论通知：</h3>
    <p>作者： <strong><?php echo esc_html($author); ?></strong></p>
    <p>文章： <strong><?php echo esc_html($post_title); ?></strong></p>
    <div style="background:#f5f5f5;padding:10px;border-radius:3px;"><?php echo nl2br($content); ?></div>
    <p>管理操作： <a href="<?php echo esc_url(admin_url("comment.php?action=approve&c={$comment_id}#wpbody-content")); ?>">批准</a> | <a href="<?php echo esc_url(admin_url("comment.php?action=trash&c={$comment_id}#wpbody-content")); ?>">移至回收站</a></p>
    <p style="color:#777;font-size:12px;">当前待审核评论数量：<?php echo intval($comments_waiting); ?></p>
</div>

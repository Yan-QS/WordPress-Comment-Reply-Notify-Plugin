<?php
/**
 * Reply notification template
 * Available variables: blogname, parent_author, parent_content, reply_author, reply_content, comment_link
 */
?>
<div style="font-family:Arial,Helvetica,sans-serif;color:#333;line-height:1.6;">
    <h3>您在 <?php echo esc_html($blogname); ?> 的留言有新回复</h3>
    <p><strong><?php echo esc_html($parent_author); ?></strong> 的原评论：</p>
    <div style="background:#f5f5f5;padding:10px;border-radius:3px;"><?php echo nl2br($parent_content); ?></div>
    <p><strong><?php echo esc_html($reply_author); ?></strong> 的回复：</p>
    <div style="background:#f5f5f5;padding:10px;border-radius:3px;"><?php echo nl2br($reply_content); ?></div>
    <p>查看回复： <a href="<?php echo esc_url($comment_link); ?>"><?php echo esc_html($comment_link); ?></a></p>
    <p style="color:#777;font-size:12px;">(此邮件由系统自动发送，请勿直接回复)</p>
</div>

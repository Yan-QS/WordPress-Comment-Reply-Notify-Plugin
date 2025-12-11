<?php
/**
 * Pending review notification template
 * Available variables: author, content, post_title, comment_id, comments_waiting, approve_url, trash_url, spam_url
 */
?>
<div style="background-color: #f0f2f5; padding: 40px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #3c434a;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        <div style="background-color: #d63638; padding: 24px; text-align: center;">
            <h2 style="margin: 0; font-size: 20px; color: #ffffff; font-weight: 600;">评论待审核</h2>
        </div>
        <div style="padding: 32px;">
            <p style="margin-top: 0; font-size: 16px;">你好，管理员！</p>
            <p style="font-size: 15px; line-height: 1.6;">文章 <strong>《{{post_title}}》</strong> 有一条新评论需要审核：</p>
            
            <div style="background-color: #fff8e5; border-left: 4px solid #dba617; padding: 16px; margin: 20px 0; border-radius: 4px;">
                <div style="font-weight: bold; margin-bottom: 8px; color: #2c3338;">{{author}} 说：</div>
                <div style="color: #50575e; line-height: 1.6;">{{content}}</div>
            </div>

            <div style="margin-top: 30px; text-align: center;">
                <a href="{{approve_url}}" style="display: inline-block; background-color: #2271b1; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 4px; font-weight: 500; margin-right: 10px;">批准</a>
                <a href="{{trash_url}}" style="display: inline-block; background-color: #d63638; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 4px; font-weight: 500;">移至回收站</a>
            </div>
        </div>
        <div style="background-color: #f8f9fa; padding: 16px; text-align: center; border-top: 1px solid #e2e4e7;">
            <p style="margin: 0; font-size: 13px; color: #646970;">当前待审核评论数量：<strong>{{comments_waiting}}</strong></p>
        </div>
    </div>
</div>

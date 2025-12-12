<?php
/** Plain reply template (improved) */
?>
<div style="font-family: -apple-system, Arial, 'Segoe UI', Roboto, sans-serif; padding:20px; color:#1f2937; background:#ffffff; border-radius:8px; border:1px solid #eef2f7; max-width:640px;">
  <h3 style="margin:0 0 10px; font-size:16px; color:#0f172a;">您有新回复</h3>
  <p style="margin:0 0 12px; color:#475569;">Hi <strong>{{parent_author}}</strong>, 您的评论收到了新的回复：</p>
  <div style="padding:14px; border-radius:6px; background:#f8fafc; border:1px solid #e6eef6; color:#23303b;">{{reply_content}}</div>
  <p style="margin:14px 0 0;"><a href="{{comment_link}}" style="color:#2563eb; text-decoration:none; font-weight:600;">查看完整回复</a></p>
</div>

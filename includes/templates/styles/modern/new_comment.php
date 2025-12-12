<?php
/** Modern new_comment template */
?>
<div style="font-family: -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; margin:0; padding:24px; background:#f4f7fb; color:#1f2937;">
  <div style="max-width:720px; margin:0 auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 8px 30px rgba(22,28,37,0.06);">
    <div style="background:linear-gradient(90deg,#2b6cb0,#2c5282); padding:22px 24px; color:#fff; text-align:center; font-weight:700; font-size:18px;">新评论通知</div>
    <div style="padding:28px;">
      <p style="margin:0 0 12px; font-size:15px;">文章 <strong>{{post_title}}</strong> 收到一条新评论：</p>
      <div style="margin:14px 0; padding:18px; background:#f8fafc; border-radius:8px; border-left:4px solid rgba(43,108,176,0.12); color:#2d3748;">{{content}}</div>
      <div style="margin-top:20px; text-align:center;">
        <a href="{{approve_url}}" style="display:inline-block; background:#2b6cb0; color:#fff; padding:10px 16px; border-radius:8px; text-decoration:none; font-weight:600; margin-right:8px;">批准</a>
        <a href="{{trash_url}}" style="display:inline-block; background:#e53e3e; color:#fff; padding:10px 16px; border-radius:8px; text-decoration:none; font-weight:600;">移至回收站</a>
      </div>
    </div>
    <div style="background:#fbfdff; padding:12px 18px; text-align:center; color:#6b7280; font-size:13px;">当前待审核评论：<strong>{{comments_waiting}}</strong></div>
  </div>
</div>

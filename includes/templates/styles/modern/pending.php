<?php
/** Modern pending template */
?>
<div style="font-family: -apple-system,Segoe UI,Roboto,Helvetica,Arial;margin:0;padding:24px;background:#f7fafc;color:#1a202c;">
  <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,0.06);">
    <div style="background:#e53e3e;padding:20px;color:#fff;text-align:center;font-weight:600;">评论待审核</div>
    <div style="padding:24px;">
      <p>文章 <strong>{{post_title}}</strong> 有新的评论需要审核：</p>
      <div style="margin:16px 0;padding:16px;background:#fff8e5;border-left:4px solid #d69e2e;">{{content}}</div>
      <p style="text-align:center;margin-top:18px;"><a href="{{approve_url}}" style="background:#2b6cb0;color:#fff;padding:10px 18px;border-radius:4px;text-decoration:none;margin-right:8px;">批准</a><a href="{{trash_url}}" style="background:#e53e3e;color:#fff;padding:10px 18px;border-radius:4px;text-decoration:none;">回收</a></p>

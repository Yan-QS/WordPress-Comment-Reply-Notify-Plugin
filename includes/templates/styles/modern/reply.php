
<?php
/** Modern reply (refined visual style) */
?>
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; padding:28px; background:#ffffff; border-radius:12px; box-shadow:0 6px 18px rgba(15,23,42,0.08); max-width:720px; color:#0f172a;">
  <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
    <div style="width:44px; height:44px; background:linear-gradient(135deg,#60a5fa,#6366f1); border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700;">R</div>
    <div>
      <div style="font-size:15px; font-weight:700;">新的回复</div>
      <div style="font-size:13px; color:#64748b;">来自 <strong>{{parent_author}}</strong> 的评论</div>
    </div>
  </div>

  <div style="padding:18px; background:linear-gradient(180deg,#fbfdff,#ffffff); border-radius:10px; border:1px solid rgba(99,102,241,0.06); color:#0f172a;">{{reply_content}}</div>

  <div style="margin-top:16px;">
    <a href="{{comment_link}}" style="display:inline-block; padding:10px 16px; background:linear-gradient(90deg,#2563eb,#4f46e5); color:#fff; border-radius:10px; text-decoration:none; font-weight:600;">查看回复</a>
  </div>
</div>


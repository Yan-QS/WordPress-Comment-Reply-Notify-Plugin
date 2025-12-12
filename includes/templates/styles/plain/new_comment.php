<?php
/** Plain new_comment template */
?>
<div style="font-family: Arial, sans-serif; padding:18px; color:#222;">
  <h3>新评论通知</h3>
  <p>文章：{{post_title}}</p>
  <div style="padding:12px;border:1px solid #ddd;">{{content}}</div>
  <p>操作： <a href="{{approve_url}}">批准</a> | <a href="{{trash_url}}">回收</a></p>
</div>

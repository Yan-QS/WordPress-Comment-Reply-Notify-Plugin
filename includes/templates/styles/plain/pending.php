<?php
/** Plain pending template */
?>
<div style="font-family: Arial, sans-serif; padding:18px; color:#222;">
  <h3>评论待审核</h3>
  <p>文章：{{post_title}}</p>
  <div style="padding:12px;border:1px solid #ffdca8;background:#fff8e5;">{{content}}</div>
  <p>操作： <a href="{{approve_url}}">批准</a> | <a href="{{trash_url}}">回收</a></p>
</div>

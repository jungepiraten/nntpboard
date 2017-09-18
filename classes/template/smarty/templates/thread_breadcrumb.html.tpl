{include file="board_breadcrumb.html.tpl" board=$board active="false"}<span class="divider">›</span></li>
<li class="active"><a href="viewthread.php?boardid={$board.boardid|escape:url}&amp;threadid={$thread.threadid|encodeMessageID|escape:url}">{$thread.subject|escape:html}</a></li>


{include file=board_breadcrumb.html.tpl board=$board}
<a class="thread" href="viewthread.php?boardid={$board.boardid|escape:url}&amp;threadid={$thread.threadid|escape:url}">{$thread.subject|escape:html}</a>

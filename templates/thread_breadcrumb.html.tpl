{include file=board_breadcrumb.html.tpl board=$board}
 <li class="thread"><a class="thread" href="viewthread.php?boardid={$board->getBoardID()|escape:url}&amp;threadid={$thread->getThreadID()|escape:url}">{$thread->getSubject()|escape:html}</a></li>

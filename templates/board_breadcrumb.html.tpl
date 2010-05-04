{if $board->hasParent()}{include file=board_breadcrumb.html.tpl board=$board->getParent()}{/if}
 <li class="board"><a class="board" href="viewboard.php?id={$board->getBoardID()|escape:url}">{$board->getName()|escape:html}</a></li>

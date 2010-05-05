{if $board.parent != null}{include file=board_breadcrumb.html.tpl board=$board.parent}{/if}
 <li class="board"><a class="board" href="viewboard.php?id={$board.boardid|escape:url}">{$board.name|escape:html}</a></li>

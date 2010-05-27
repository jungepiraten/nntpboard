{if $board.parent != null}{include file=board_breadcrumb.html.tpl board=$board.parent}{/if}
<a class="board" href="viewboard.php?boardid={$board.boardid|escape:url}">{$board.name|escape:html}</a> <span class="arrowright">&raquo;</span> 

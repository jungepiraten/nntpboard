{if $board.parent != null}{include file=board_breadcrumb.html.tpl board=$board.parent} <span class="arrowright">&raquo;</span> {/if}
<a class="board" href="viewboard.php?boardid={$board.boardid|escape:url}">{$board.name|escape:html}</a>

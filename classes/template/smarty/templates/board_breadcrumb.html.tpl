{if $board.parent != null}
{include file=board_breadcrumb.html.tpl board=$board.parent active="false"}<span class="divider">›</span></li>
{/if}
<li {if $active!="false"}class="active"{/if}><a href="viewboard.php?boardid={$board.boardid|escape:url}">{$board.name|escape:html}</a>
{foreach from=$curboard.childs item=board_item name=counter}
	<li><a href="viewboard.php?boardid={$board_item.boardid|escape:url}"><i class="icon-list-alt"></i> {$board_item.name|escape:html}</a></li>
	{if isset($board) && isSubBoard($board, $board_item)}<ul class="nav nav-list">{include file="header_forennavigation.html.tpl" curboard=$board_item}</ul>{/if}
{/foreach}

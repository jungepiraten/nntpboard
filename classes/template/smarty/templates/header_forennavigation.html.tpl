{foreach from=$curboard.childs item=board_item name=counter}
	<li class="{if isset($board) && $board_item.boardid == $board.boardid}active{/if}">
		<a href="viewboard.php?boardid={$board_item.boardid|escape:url}">
			<i class="icon-list-alt {if isset($board) && $board_item.boardid == $board.boardid}icon-white{/if}" {if isset($board_item.childs) && count($board_item.childs) > 0}onClick="$('#board-childnav{$board_item.boardid}').fadeToggle(200);return false;"{/if}></i>
			{$board_item.name|escape:html}
		</a>
		{if isset($board_item.childs) && count($board_item.childs) > 0}
			<ul id="board-childnav{$board_item.boardid}" {if !isset($board) || !isSubBoard($board, $board_item)}style="display:none;"{/if} class="nav nav-list">
				{include file="header_forennavigation.html.tpl" curboard=$board_item}
			</ul>
		{/if}
	</li>
{/foreach}

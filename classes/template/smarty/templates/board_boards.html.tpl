<ul class="subboards">
{foreach from=$subboards item=board}
 <li class="board {if $board.unread}unread{/if}">
  <a class="name" href="viewboard.php?boardid={$board.boardid}" title="{$subboard.desc|escape:html}">{$board.name|escape:html}</a>
  <span class="desc">{$board.desc}</span>
  {if isset($board.threadcount)}
  <span class="threadcount">{$board.threadcount|escape:html}</span>
  {/if}
  {if isset($board.lastpostmessageid)}
  <span class="lastmessage">
   <a class="lastmessage lastpostdate" href="viewthread.php?boardid={$board.boardid|escape:url}&amp;messageid={$board.lastpostmessageid|escape:url}">{$board.lastpostdate|date_format:"%d.%m.%Y %H:%M"}</a>
   von <span class="lastpostauthor">{$board.lastpostauthor|escape:html}</span>:
   <a class="lastthread" href="viewthread.php?boardid={$board.boardid|escape:url}&amp;threadid={$board.lastpostthreadid|escape:url}">{$board.lastpostsubject|escape:html}</a>
  </span>
  {/if}
  {if !empty($board.childs)}{include file=board_boards.html.tpl subboards=$board.childs}{/if}
 </li>
{/foreach}
</ul>

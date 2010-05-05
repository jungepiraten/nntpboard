<ul class="subboards">
{foreach from=$subboards item=board}
 <li class="board {if $group != null && isset($auth) && false}unread{/if}">
  <a class="name" href="viewboard.php?boardid={$board.boardid}" title="{$subboard.desc|escape:html}">{$board.name|escape:html}</a>
  <span class="desc">{$board.desc}</span>
  {if isset($board.threadcount)}
  <span class="threadcount">{$board.threadcount|escape:html}</span>
  {/if}
  {if isset($board.lastpostmessageid)}
  <span class="lastmessage">
   <a class="lastmessage lastpostdate" href="viewthread.php?boardid={$subboard->getBoardID()|escape:url}&amp;messageid={$connection->getLastPostMessageID()|escape:url}">{$connection->getLastPostDate()|date_format:"%d.%m.%Y %H:%M"}</a>
   von <span class="lastpostauthor">{$connection->getLastPostAuthor()|escape:html}</span>:
   <a class="lastthread" href="viewthread.php?boardid={$subboard->getBoardID()|escape:url}&amp;threadid={$connection->getLastPostThreadID()|escape:url}">{$connection->getLastPostSubject()|escape:html}</a>
  </span>
  {/if}
  {if !empty($board.childs)}{include file=board_boards.html.tpl subboards=$board.childs}{/if}
 </li>
{/foreach}
</ul>

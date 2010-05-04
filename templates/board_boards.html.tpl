<ul class="subboards">
{foreach from=$subboards item=subboard}
<!-- TODO: hier die Verbindung herstellen ist doch ekelhaft ... -->
{if $subboard->hasGroup()}
 {assign var=group value=$subboard->getGroup()}
 {assign var=connection value=$group->getConnection($DATADIR, $auth)}{$connection->open()}
{else}
 {assign var=group value=null}
 {assign var=connection value=null}
{/if}
 <li class="board {if $group != null && isset($auth) && $auth->isUnreadGroup($group)}unread{/if}">
  <a class="name" href="viewboard.php?id={$subboard->getBoardID()}" title="{$subboard->getDesc()|escape:html}">{$subboard->getName()|escape:html}</a>
  <span class="desc">{$subboard->getDesc()}</span>
  {if $connection !== null}
  <span class="threadcount">{$connection->getThreadCount()|escape:html}</span>
  <span class="lastmessage">
   <a class="lastmessage lastpostdate" href="viewthread.php?boardid={$subboard->getBoardID()|escape:url}&amp;messageid={$connection->getLastPostMessageID()|escape:url}">{$connection->getLastPostDate()|date_format:"%d.%m.%Y %H:%M"}</a>
   von <span class="lastpostauthor">{$connection->getLastPostAuthor()|escape:html}</span>:
   <a class="lastthread" href="viewthread.php?boardid={$subboard->getBoardID()|escape:url}&amp;threadid={$connection->getLastPostThreadID()|escape:url}">{$connection->getLastPostSubject()|escape:html}</a>
  </span>
  {$connection->close()}
  {/if}
  {if $subboard->hasSubBoards()}{include file=board_boards.html.tpl subboards=$subboard->getSubBoards()}{/if}
 </li>
{/foreach}
</ul>

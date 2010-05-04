{include file=header.html.tpl}
<h1>{$board->getName()|escape:html}</h1>

<ul class="breadcrumb navigation">{include file=board_breadcrumb.html.tpl board=$board}</ul>

<p class="desc">{$board->getDesc()}</p>

{if $board->hasSubBoards()}<div class="subboards">{include file=board_boards.html.tpl subboards=$board->getSubBoards()}</div>{/if}

{if isset($threads)}
<ul class="threads">
{foreach from=$threads item=thread}
 <li class="thread {cycle values="odd,even"}{if isset($auth) && $auth->isUnreadThread($thread)} unread{/if}">
  <a class="subject" href="viewthread.php?boardid={$board->getBoardID()|escape:url}&amp;threadid={$thread->getThreadID()|escape:url}">{$thread->getSubject()}</a>
  <span class="posts">{$thread->getPosts()}</span>
  <span class="thread">
   <span class="date">{$thread->getDate()|date_format:"%d.%m.%Y %H:%M"}</span>
   von <span class="author">{$thread->getAuthor()}</span>
  </span>
  <span class="lastpost">
   <a class="date" href="viewthread.php?boardid={$board->getBoardID()}&amp;messageid={$thread->getLastPostMessageID()}">{$thread->getLastPostDate()|date_format:"%d.%m.%Y %H:%M"}</a>
   von <span class="author">{$thread->getLastpostAuthor()}</span>
  </span>
 </li>
{/foreach}
</ul>
{/if}
{include file=footer.html.tpl}

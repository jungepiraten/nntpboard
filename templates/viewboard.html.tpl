{include file=header.html.tpl}
<h1>{$board->getName()}</h1>

{include file=board_breadcrumb.html.tpl board=$board}

{if $board->hasSubBoards()}
{assign var=subboardsWithGroup value=0}
{foreach from=$board->getSubBoards() item=subboard}
{if !$subboard->hasGroup()}
{include file=board_boards.html.tpl boardid=$subboard->getBoardID() heading=$subboard->getName() subboards=$subboard->getSubBoards()}
{else}
{math equation=a+1 assign=subboardsWithGroup a=$subboardsWithGroup}
{/if}
{/foreach}

{if $subboardsWithGroup > 0}{include file=board_boards.html.tpl subboards=$board->getSubBoards()}{/if}
{/if}

{if $board->hasGroup() && $mayPost}
<a href="post.php?boardid={$board->getBoardID()|escape:url}">Neuer Thread</a>
{/if}

{if isset($threads)}
<table>
<thead>
<tr>
 <th>&nbsp;</th>
 <th>Thema</th>
 <th>Posts</th>
 <th>Geschrieben</th>
 <th>Letzte Antwort</th>
</tr>
</thead>
<tbody>
{foreach from=$threads item=thread}
<tr>
 <td>{if isset($auth) && $auth->isUnreadThread($thread)}O{/if}</td>
 <td><a href="viewthread.php?boardid={$board->getBoardID()|escape:url}&amp;threadid={$thread->getThreadID()|escape:url}">{$thread->getSubject()}</a></td>
 <td>{$thread->getPosts()}</td>
 <td>{$thread->getDate()|date_format:"%d.%m.%Y %H:%M"}<br />von {$thread->getAuthor()}</td>
 <td><a href="viewthread.php?boardid={$board->getBoardID()}&amp;messageid={$thread->getLastPostMessageID()}">{$thread->getLastPostDate()|date_format:"%d.%m.%Y %H:%M"}</a><br />von {$thread->getLastpostAuthor()}</td>
</tr>
{/foreach}
</tbody>
</table>
{/if}
{include file=footer.html.tpl}

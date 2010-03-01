{include file=header.html.tpl}
<h1>{$board->getName()}</h1>

{include file=board_breadcrumb.html.tpl board=$board}

{if $board->hasSubBoards()}
<table>
<thead>
<tr>
 <th>Board</th>
 <th>Themen</th>
 <th>Letzte Antwort</th>
</tr>
</thead>
<tbody>
{foreach from=$board->getSubBoards() item=subboard}
{assign var=group value=$subboard->getGroup()}
{$group->load()}
<tr>
 <td>
  <a href="viewboard.php?id={$subboard->getBoardID()}">{$subboard->getName()}</a><br />
  {$subboard->getDesc()}
 </td>
 <td>{$group->getThreadCount()}</td>
 <td>{$group->getLastPostDate()|date_format:"%d.%m.%Y %H:%M"} von {$group->getLastPostAuthor()}</td>
</tr>
</tr>
{/foreach}
</tbody>
</table>
{/if}

{if isset($threads)}
<table>
<thead>
<tr>
 <th>Thema</th>
 <th>Posts</th>
 <th>Geschrieben</th>
 <th>Letzte Antwort</th>
</tr>
</thead>
<tbody>
{foreach from=$threads item=thread}
<tr>
 <td><a href="viewthread.php?boardid={$board->getBoardID()|escape:url}&amp;threadid={$thread->getThreadID()|escape:url}">{$thread->getSubject()}</a></td>
 <td>{$thread->getPosts()}</td>
 <td>{$thread->getDate()|date_format:"%d.%m.%Y %H:%M"}<br />von {$thread->getAuthor()}</td>
 <td>{$thread->getLastPostDate()|date_format:"%d.%m.%Y %H:%M"}<br />von {$thread->getLastpostAuthor()}</td>
</tr>
{/foreach}
</tbody>
</table>
{/if}
{include file=footer.html.tpl}

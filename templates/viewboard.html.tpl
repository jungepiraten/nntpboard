{include file=header.html.tpl}

{if isset($threads)}
<table>
<thead>
<tr>
 <th>Titel</th>
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

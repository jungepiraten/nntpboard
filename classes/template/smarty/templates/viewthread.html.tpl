{include file=header.html.tpl}
<h1>{$thread.subject}</h1>

{include file=thread_breadcrumb.html.tpl board=$board thread=$thread}

<span class="page">
{if $pages > 1}
Seite {$page+1} von {$pages} &bull; 
{/if}
{section name=page start=0 loop=$pages}
{assign var=p value=$smarty.section.page.index}
{if $page!=$p}<a href="viewthread.php?boardid={$board.boardid}&amp;threadid={$thread.threadid}&amp;page={$p}" class="pagenumber">{else}<span class="selected-page">{/if}{$p+1}{if $page!=$p}</a>{else}</span>{/if}
{/section}
</span>

<table class="mainmessagetable">
{foreach from=$messages item=message name=counter}
{if $smarty.foreach.counter.first}<tr class="messageheadfirst">{else}<tr class="messagehead">{/if}
<td>
 <a name="article{$message.messageid|escape:html}" class="anchor"></a>
 <span class="subject">{$message.subject|escape:html}</span>
 <span class="messageinfo">von</span>
 <span class="author">{$message.author|escape:html}</span>
 <span class="messageinfo">am</span>
 <span class="date">{$message.date|date_format:"%d.%m.%Y %H:%M"}</span>
 {if ($mayPost)}<a href="post.php?boardid={$board.boardid}&amp;reference={$message.messageid}" class="reply">Antworten</a>{/if}
 </td></tr>
 <tr class="message{cycle values="odd,even"}"><td>
 <p class="body">{$message.body}</p>
 {if $message.attachments}
 <dl class="attachmentbox">
 <dt>Dateianhänge</dt>
 {foreach from=$message.attachments key=partid item=part}
  {capture assign=attachmentlink}attachment.php?boardid={$board.boardid}&amp;messageid={$message.messageid}&amp;partid={$partid}{/capture}
  {if $part.isinline && $part.isimage}
  <hr class="attachmentsep"><a href="{$attachmentlink}"><img src="{$attachmentlink}" width="200px" /></a>
  {else}
  <hr class="attachmentsep"><a href="{$attachmentlink}" class="attachment body">{$part.filename}</a>
  {/if}
 {/foreach}
</dl>
{/if}
{/foreach}
</td></tr>
</table>

<span class="page">
{if $pages > 1}
Seite {$page+1} von {$pages} &bull; 
{/if}
{section name=page start=0 loop=$pages}
{assign var=p value=$smarty.section.page.index}
{if $page!=$p}<a href="viewthread.php?boardid={$board.boardid}&amp;threadid={$thread.threadid}&amp;page={$p}" class="pagenumber">{else}<span class="selected-page">{/if}{$p+1}{if $page!=$p}</a>{else}</span>{/if}
{/section}
</span>

{include file=footer.html.tpl}

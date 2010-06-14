{include file=header.html.tpl title=$thread.subject}

{include file=thread_breadcrumb.html.tpl board=$board thread=$thread}

<div class="page">
{if $pages > 1}
Seite {$page+1} von {$pages} &bull; 
{/if}
{section name=page start=0 loop=$pages}
{assign var=p value=$smarty.section.page.index}
{if $page!=$p}<a href="viewthread.php?boardid={$board.boardid}&amp;threadid={$thread.threadid}&amp;page={$p}" class="pagenumber">{else}<span class="selected-page">{/if}{$p+1}{if $page!=$p}</a>{else}</span>{/if}
{/section}
</div>

<table class="mainmessagetable">
{foreach from=$messages item=message name=counter}
<tr class="messagehead {if $smarty.foreach.counter.first}first{/if}">
<td>
 <a name="article{$message.messageid|escape:html}" class="anchor"></a>
 <a class="subject" href="viewthread.php?boardid={$board.boardid}&amp;messageid={$message.messageid|escape:url}">{$message.subject|escape:html}</a>
 <span class="messageinfo">von</span>
 <span class="author">{include file=address.html.tpl address=$message.author}</span>
 <span class="messageinfo">am</span>
 <span class="date">{$message.date|date_format:"%d.%m.%Y %H:%M"}</span>
 {if ($mayPost)}<span class="messagebuttondiv"><a href="post.php?boardid={$board.boardid}&amp;quote={$message.messageid}" class="quote">Zitieren</a> &middot; <a href="post.php?boardid={$board.boardid}&amp;reply={$message.messageid}" class="reply">Antworten</a></span>{/if}
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

<div class="page">
{if $pages > 1}
Seite {$page+1} von {$pages} &bull; 
{/if}
{section name=page start=0 loop=$pages}
{assign var=p value=$smarty.section.page.index}
{if $page!=$p}<a href="viewthread.php?boardid={$board.boardid}&amp;threadid={$thread.threadid}&amp;page={$p}" class="pagenumber">{else}<span class="selected-page">{/if}{$p+1}{if $page!=$p}</a>{else}</span>{/if}
{/section}
</div>

{include file=footer.html.tpl}

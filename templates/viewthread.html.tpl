{include file=header.html.tpl}
<h1>{$thread.subject}</h1>

<ul class="breadcrumb navigation">{include file=thread_breadcrumb.html.tpl board=$board thread=$thread}</ul>

<ul class="page">
{section name=page start=0 loop=$pages}
{assign var=p value=$smarty.section.page.index}
 <li class="page">{if $page!=$p}<a href="viewthread.php?boardid={$board.boardid}&amp;threadid={$thread.threadid}&amp;page={$p}">{/if}{$p+1}{if $page!=$p}</a>{/if}</li>
{/section}
</ul>

{foreach from=$messages item=message}
<div class="message {cycle values="odd,even"}">
 <a name="article{$message.articlenum|escape:html}" class="anchor"></a>
 <span class="author">{$message.author|escape:html}</span>
 <span class="date">{$message.date|date_format:"%d.%m.%Y %H:%M"}</span>
 {if ($mayPost)}<a href="post.php?boardid={$board.boardid}&amp;reference={$message.messageid}" class="reply">Antworten</a>{/if}
 <span class="subject">{$message.subject|escape:html}</span>
 <p class="body">{$message.body}</p>
 {foreach from=$message.attachments key=partid item=part}
  {capture assign=attachmentlink}attachment.php?boardid={$board.boardid}&amp;messageid={$message.messageid}&amp;partid={$partid}{/capture}
  {if $part.isinline && $part.isimage}
  <img src="{$attachmentlink}" width="300px" class="body" />
  {else}
   <a href="{$attachmentlink}" class="attachment body">{$part.filename}</a>
  {/if}
 {/foreach}
</div>
{/foreach}

<ul class="page">
{section name=page start=0 loop=$pages}
{assign var=p value=$smarty.section.page.index}
 <li class="page">{if $page!=$p}<a href="viewthread.php?boardid={$board.boardid}&amp;threadid={$thread.threadid}&amp;page={$p}">{/if}{$p+1}{if $page!=$p}</a>{/if}</li>
{/section}
</ul>
{include file=footer.html.tpl}

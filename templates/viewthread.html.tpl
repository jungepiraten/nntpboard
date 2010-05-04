{include file=header.html.tpl}
<h1>{$thread->getSubject()}</h1>

<ul class="breadcrumb navigation">{include file=thread_breadcrumb.html.tpl board=$board thread=$thread}</ul>

{assign var=boardid value=$board->getBoardID()}
{assign var=group value=$board->getGroup()}

<div class="options">
 {if ($mayPost)}<a href="post.php?boardid={$boardid}&amp;reference={$thread->getThreadID()}" class="reply">Antworten</a>{/if}
</div>

{foreach from=$messages item=message}
{assign var=messageid value=$message->getMessageID()}
<div class="message {cycle values="odd,even"}">
 <a name="article{$message->getArticleNum()|escape:html}" class="anchor"></a>
 <span class="author">{$message->getAuthor()}</span>
 <span class="date">{$message->getDate()|date_format:"%d.%m.%Y %H:%M"}</span>
 <span class="subject">{$message->getSubject()}</span>
 {foreach from=$message->getBodyParts() key=partid item=part}
  <!-- TODO unschoen, bitte mehr PHP hier! -->
  {assign var=attachmentlink value=$DATADIR->getAttachmentWebPath($group,$part)}
  {if     $part->isInline() && $part->isText()}
  <p class="body">{$part->getHTML($CHARSET)}</p>
  {elseif $part->isInline() && $part->isImage()}
  <img src="{$attachmentlink}" width="300px" class="body" />
  {else}
   <a href="{$attachmentlink}" class="attachment body">{$part->getFilename()}</a>
  {/if}
 {/foreach}
</div>
{/foreach}
{include file=footer.html.tpl}

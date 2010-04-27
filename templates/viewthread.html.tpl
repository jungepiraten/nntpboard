{include file=header.html.tpl}
<h1>{$thread->getSubject()}</h1>

{include file=thread_breadcrumb.html.tpl board=$board thread=$thread}

{assign var=boardid value=$board->getBoardID()}
{assign var=group value=$board->getGroup()}

<a href="post.php?boardid={$boardid}&amp;reference={$thread->getThreadID()}">Antworten</a>

<table>
{foreach from=$messages item=message}
<tr class="message">
 <th class="meta"><a name="article{$message->getArticleNum()|escape:html}"></a>{$message->getAuthor()}<br />{$message->getDate()|date_format:"%d.%m.%Y %H:%M"}</th>
 <td class="body">
  {foreach from=$message->getBodyParts() key=partid item=part}
  <div class="bodypart">
  {assign var=messageid value=$message->getMessageID()}
  {assign var=attachmentlink value=$DATADIR->getAttachmentWebPath($group,$part)}
  {if     $part->isInline() && $part->isText()}
   <pre>{$part->getHTML("UTF-8")}</pre>
  {elseif $part->isInline() && $part->isImage()}
   <img src="{$attachmentlink}" width="300px" />
  {else}
   <a href="{$attachmentlink}">{$part->getFilename()}</a>
  {/if}
  </div>
  {/foreach}
 </td>
</tr>
{/foreach}
</table>
{include file=footer.html.tpl}

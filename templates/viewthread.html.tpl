{include file=header.html.tpl}
<h1>{$thread->getSubject()}</h1>

{include file=thread_breadcrumb.html.tpl board=$board thread=$thread}

<table>
{foreach from=$messages item=message}
<tr>
 <th>{$message->getSender()}<br />{$message->getDate()|date_format:"%d.%m.%Y %H:%M"}</th>
 <td>{foreach from=$message->getBodyParts() key=partid item=part}{if $part->isInline()}
  <div class="bodypart">
  {assign var=boardid value=$board->getBoardID()}
  {assign var=messageid value=$message->getMessageID()}
  {assign var=attachmentlink value="attachment.php?boardid=$boardid&amp;messageid=$messageid&amp;partid=$partid"}
  {if $part->isText()}<pre>{$part->getText("UTF-8")}</pre>
  {elseif $part->isImage()}<img src="{$attachmentlink}" width="300px" />
  {else}<a href="{$attachmentlink}">{$part->getFilename()}</a>{/if}{/if}</div>{/foreach}</td>
</tr>
{/foreach}
</table>
{include file=footer.html.tpl}

<table class="mainmessagetable {if $first}first{/if} {cycle values="odd,even"}">
<tr class="messagehead">
<td>
 <a name="article{$message.messageid|escape:html}" class="anchor"></a>
 <a class="subject" href="viewthread.php?boardid={$board.boardid}&amp;messageid={$message.messageid|escape:url}">{$message.subject|escape:html}</a>
 <span class="info">von</span>
 <span class="author">{include file=address.html.tpl address=$message.author}</span>
 <span class="info">am</span>
 <span class="date">{$message.date|date_format:"%d.%m.%Y %H:%M"}</span>
 {if ($mayPost)}<span class="buttondiv"><a href="post.php?boardid={$board.boardid}&amp;quote={$message.messageid}" class="quote">Zitieren</a> &middot; <a href="post.php?boardid={$board.boardid}&amp;reply={$message.messageid}" class="reply">Antworten</a></span>{/if}
 </td>
</tr>
<tr class="message"><td>
 <p class="body">{$message.body}</p>
 {if isset($message.signature)}
 <div class="signature">
  <a href="javascript:toggleSignature('{$id}');"
   id="signaturelink{$id}" class="signaturlink">Signatur zeigen</a>
  <p class="signature" id="signature{$id}" style="display:none;">{$message.signature}</p>
 </div>
 {/if}
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
</td></tr>
</table>

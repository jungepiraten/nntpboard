<table class="mainmessagetable {if $first}first{/if} {cycle values="odd,even"}">
<tr class="messagehead">
<td colspan="2">
 <a id="article{$message.messageid|encodeMessageID|escape:html}" class="anchor"></a>
 <a class="subject" href="viewthread.php?boardid={$board.boardid}&amp;messageid={$message.messageid|encodeMessageID|escape:url}">{$message.subject|escape:html}</a>
 <span class="info">von</span>
 <span class="author">{include file=address.html.tpl address=$message.author}</span>
 <span class="info">am</span>
 <span class="date">{$message.date|date_format:"%d.%m.%Y %H:%M"}</span>
 <span class="buttondiv">
  {if ($mayPost)}<a href="post.php?boardid={$board.boardid|escape:url}&amp;quote={$message.messageid|escape:url}" class="quote">Zitieren</a> &middot; <a href="post.php?boardid={$board.boardid|escape:url}&amp;reply={$message.messageid|escape:url}" class="reply">Antworten</a>{/if}
  {if ($mayAcknowledge)} &middot; <a href="ack.php?boardid={$board.boardid|escape:url}&amp;messageid={$message.messageid|escape:url}" class="ack">+</a> &middot; <a href="ack.php?boardid={$board.boardid|escape:url}&amp;messageid={$message.messageid|escape:url}&amp;wertung=-1" class="nack">-</a>{/if}
  {if ($message.mayCancel)} &middot; <a href="cancel.php?boardid={$board.boardid|escape:url}&amp;messageid={$message.messageid|escape:url}" class="cancel">L&ouml;schen</a>{/if}
 </span>
 </td>
</tr>
<tr class="message">
<td class="authorpic">
 <img src="{$message.author.image}" width="70px" />
</td>
<td class="message">
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
  {capture assign=attachmentlink}attachment.php?boardid={$board.boardid|escape:url}&amp;messageid={$message.messageid|escape:url}&amp;partid={$partid|escape:url}{/capture}
  {if $part.isinline && $part.isimage}
  <hr class="attachmentsep"><a href="{$attachmentlink}"><img src="{$attachmentlink}" width="200px" /></a>
  {else}
  <hr class="attachmentsep"><a href="{$attachmentlink}" class="attachment body">{$part.filename}</a>
  {/if}
 {/foreach}
 {/if}
 {if count($message.acknowledges) >= 1}
 <dl class="acknowledgebox">
 <dt>Zustimmungen</dt>
 {foreach from=$message.acknowledges item=acknowledge name=acks}{if !$smarty.foreach.acks.first} &middot; {/if}{include file=address.html.tpl address=$acknowledge.author} [+{$acknowledge.wertung}]{/foreach}
 </dl>
 {/if}
 {if count($message.nacknowledges) >= 1}
 <dl class="acknowledgebox">
 <dt>Ablehnungen</dt>
 {foreach from=$message.nacknowledges item=acknowledge name=nacks}{if !$smarty.foreach.nacks.first} &middot; {/if}{include file=address.html.tpl address=$acknowledge.author} [{$acknowledge.wertung}]{/foreach}
 </dl>
 {/if}
</td></tr>
</table>

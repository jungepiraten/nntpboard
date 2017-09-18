<article class="message">
<header class="head">
 <a id="article{$message.messageid|encodeMessageID|escape:html}" class="anchor"></a>
 {if ! $message.isRead}<i class="icon-comments"></i>{/if}
 {if isset($message.author.image)}<img src="{$message.author.image|escape:html}" class="author thumbnail" alt="{$message.author.text|escape:html}" />{/if}
 <a class="subject" href="viewthread.php?boardid={$board.boardid}&amp;messageid={$message.messageid|encodeMessageID|escape:url}">{$message.subject|escape:html}</a><br />
 <span class="info">am</span>
 <time class="date" datetime="{$message.date|date_format:"%Y-%m-%dT%H:%M+01:00"}" pubdate>{$message.date|date_format:"%d.%m.%Y %H:%M"}</time>
 <span class="info">von</span>
 <span class="author">{include file="address.html.tpl" address=$message.author}</span>
</header>
<div class="body">
 <p class="body">{$message.body}</p>
 {if isset($message.signature)}
 <div class="signature">
  <a href="javascript:toggleSignature('{$id}');" id="signaturelink{$id}" class="signaturlink">Signatur zeigen</a>
  <div class="signature" id="signature{$id}" style="display:none;">{$message.signature}</div>
 </div>
 {/if}
 {if $message.attachments}
 <dl class="attachmentbox">
 <dt>Dateianhänge</dt>
 {foreach from=$message.attachments key=partid item=part}
  {capture assign=attachmentlink}attachment.php?boardid={$board.boardid|escape:url}&amp;messageid={$message.messageid|encodeMessageID|escape:url}&amp;partid={$partid|escape:url}{/capture}
  {if $part.isinline && $part.isimage}
  <hr class="attachmentsep"><a href="{$attachmentlink}"><img src="{$attachmentlink}" width="200px" alt="{$part.filename|escape:html}" /></a>
  {else}
  <hr class="attachmentsep"><a href="{$attachmentlink}" class="attachment body">{$part.filename|escape:html}</a>
  {/if}
 {/foreach}
 </dl>
 {/if}
 {if count($message.acknowledges) >= 1}
 <dl class="acknowledgebox">
 <dt>Zustimmungen</dt>
 {foreach from=$message.acknowledges item=acknowledge name=acks}{if !$smarty.foreach.acks.first} &middot; {/if}{include file="address.html.tpl" address=$acknowledge.author} [+{$acknowledge.wertung}]{/foreach}
 </dl>
 {/if}
 {if count($message.nacknowledges) >= 1}
 <dl class="acknowledgebox">
 <dt>Ablehnungen</dt>
 {foreach from=$message.nacknowledges item=acknowledge name=nacks}{if !$smarty.foreach.nacks.first} &middot; {/if}{include file="address.html.tpl" address=$acknowledge.author} [{$acknowledge.wertung}]{/foreach}
 </dl>
 {/if}
</div>
{if !isset($hidecontrols)}
<footer class="controls">
<div class="btn-group pull-right">
  {if ($message.mayCancel && !$ISANONYMOUS)}<a href="cancel.php?boardid={$board.boardid|escape:url}&amp;messageid={$message.messageid|encodeMessageID|escape:url}" class="btn btn-danger btn-mini deletePost"><i class="icon-trash icon-white"></i> L&ouml;schen</a>{/if}
  {if ($mayAcknowledge && !$ISANONYMOUS)}<a href="ack.php?boardid={$board.boardid|escape:url}&amp;messageid={$message.messageid|encodeMessageID|escape:url}" class="btn btn-mini"><i class="icon-ok"></i> Zustimmen</a><a href="ack.php?boardid={$board.boardid|escape:url}&amp;messageid={$message.messageid|encodeMessageID|escape:url}&amp;wertung=-1" class="btn btn-mini"><i class="icon-remove"></i> Ablehnen</a>{/if}

  {if ($mayPost)}
	<a href="post.php?boardid={$board.boardid|escape:url}&amp;quote={$message.messageid|encodeMessageID|escape:url}" class="btn btn-mini"><i class="icon-file"></i> Zitieren</a>
	<a href="post.php?boardid={$board.boardid|escape:url}&amp;reply={$message.messageid|encodeMessageID|escape:url}" class="btn btn-inverse btn-mini"><i class="icon-share-alt icon-white"></i> Antworten</a>
  {/if}

</div>
<div style="clear:both;"></div>
</footer>
{/if}
</article>

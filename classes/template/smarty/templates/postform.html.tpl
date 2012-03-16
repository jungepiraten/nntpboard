{include file=header.html.tpl title=$board.name}

<ul class="breadcrumb">
{include file=board_breadcrumb.html.tpl board=$board}
</ul>

{literal}
<script type="text/javascript">
<!--

function toggleSignature(id) {
	if (document.getElementById("signature" + id).style.display == "none") {
		document.getElementById("signaturelink" + id).innerHTML = "Signatur verstecken";
		document.getElementById("signature" + id).style.display = "block";
	} else {
		document.getElementById("signaturelink" + id).innerHTML = "Signatur anzeigen";
		document.getElementById("signature" + id).style.display = "none";
	}
}

function toggleQuote(id) {
	if (document.getElementById("quote" + id).style.display == "none") {
		document.getElementById("quotelink" + id).innerHTML = "Zitat verstecken";
		document.getElementById("quote" + id).style.display = "block";
	} else {
		document.getElementById("quotelink" + id).innerHTML = "Zitat anzeigen";
		document.getElementById("quote" + id).style.display = "none";
	}
}

function addAttachmentField() {
	var input = document.createElement("input");
	input.type = "file";
	input.name = "attachment[]";
	document.getElementById('attachments').appendChild(input);
	var newline = document.createElement("br");
	document.getElementById('attachments').appendChild(newline);
}

//-->
</script>
{/literal}

{if isset($preview)}
{include file=message.html.tpl message=$preview first=1 id="post"}
{/if}

<form action="post.php" method="post" enctype="multipart/form-data" accept-charset="{$CHARSET}" class="form-horizontal">
	<input type="hidden" name="charset" value="{$CHARSET}" />
	<input type="hidden" name="boardid" value="{$board.boardid|escape:html}" />
	<input type="hidden" name="reference" value="{$reference|encodeMessageID|escape:html}" />

	<fieldset>

		<div class="control-group">
<!--
			{if isset($address)}
				<span>{include file=address.html.tpl address=$address}</span>
			{else}
				<label for="user">Benutzer:</label></td>
				<td><input type="text" name="user" id="user" size="30" value="{if isset($smarty.request.user)}{$smarty.request.user|stripslashes|escape:html}{else}{$user|escape:html}{/if}" /></td>
				<td><label for="email" style="float:right">E-Mail:</label></td>
				<td style="width:180px"><input type="text" name="email" id="email" size ="30" style="float:right" value="{if isset($smarty.request.email)}{$smarty.request.email|stripslashes|escape:html}{else}{$email|escape:html}{/if}" /></td>
			{/if}-->

			<label class="control-label" for="subject">Betreff</label>
			<div class="controls">
				<input type="text" class="span7" name="subject" id="subject" value="{if isset($smarty.request.subject)}{$smarty.request.subject|stripslashes|escape:html}{else}{$subject|escape:html}{/if}" />
				<p class="help-block">Bitte gebe hier den Betreff an, unter dem deine Nachricht erscheinen soll. In 90% der Fälle brauchst du dieses Feld nicht bearbeiten.</p>
			</div>

			<label class="control-label" for="body">Text:</label>
			<div class="controls">
				<textarea name="body" id="body" class="span7" cols="80" rows="20">{if isset($smarty.request.body)}{$smarty.request.body|stripslashes|escape:html}{else}{$body|escape:html}{/if}</textarea>
			</div>

<label for="attachments">Anh&auml;nge:<br /> (Max. {$maxuploadsize})</label></td>

    <div id="attachments">
     {foreach from=$attachments key=partid item=attachment}
      <input type="checkbox" name="storedattachment[]" value="{$partid}" checked="checked" />
      <strong><a href="attachment.php?boardid={$board.boardid|escape:url}&amp;partid={$partid|escape:html}">{$attachment.filename}</a></strong><br />
     {/foreach}
     <input type="file" name="attachment[]" /><br />
    </div>

    <a href="#attachments" onclick="addAttachmentField()">Weiteres</a>


			<div class="form-actions">
				<input type="submit" class="btn btn-primary" name="post" value="Schreiben" />
				<input type="submit" class="btn" name="preview" value="Vorschau" />
			</div>
		</div>
	</fieldset>
</form>

{if isset($referencemessages)}
 {foreach from=$referencemessages item=message name=counter}
  {include file=message.html.tpl message=$message first=$smarty.foreach.counter.first id=$smarty.foreach.counter.iteration}
 {/foreach}
{/if}

<br />
{include file=footer.html.tpl}

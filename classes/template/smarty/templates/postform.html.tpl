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
}

//-->
</script>
{/literal}

{if isset($preview)}
<table class="table table-striped table-bordered">
{include file=message.html.tpl message=$preview first=1 id="post"}
</table>
{/if}

<form action="post.php" method="post" enctype="multipart/form-data" accept-charset="{$CHARSET}" class="form-horizontal">
	<input type="hidden" name="charset" value="{$CHARSET}" />
	<input type="hidden" name="boardid" value="{$board.boardid|escape:html}" />
	<input type="hidden" name="reference" value="{$reference|encodeMessageID|escape:html}" />

	<fieldset>

		<div class="control-group">
 			{if isset($address)}
				<label class="control-label" for="user">Benutzer</label>
				<p class="controls">
					<input type="text" name="user" id="user" value="{$address.text|escape:html}" readonly />
				</p>
			{else}
				<label class="control-label" for="user">Benutzer</label>
				<p class="controls">
					<input type="text" name="user" id="user" value="{if isset($smarty.request.user)}{$smarty.request.user|stripslashes|escape:html}{else}{$user|escape:html}{/if}" />
				</p>

				<label class="control-label" for="email">E-Mail</label>
				<p class="controls">
					<input type="text" name="email" id="email" value="{if isset($smarty.request.email)}{$smarty.request.email|stripslashes|escape:html}{else}{$email|escape:html}{/if}" />
				</p>
			{/if}

			<label class="control-label" for="subject">Betreff</label>
			<p class="controls">
				<input type="text" class="span7" name="subject" id="subject" value="{if isset($smarty.request.subject)}{$smarty.request.subject|stripslashes|escape:html}{else}{$subject|escape:html}{/if}" />
			</p>

			<label class="control-label" for="body">Text</label>
			<p class="controls">
				<textarea name="body" id="body" class="span7" cols="80" rows="20">{if isset($smarty.request.body)}{$smarty.request.body|stripslashes|escape:html}{else}{$body|escape:html}{/if}</textarea>
			</p>

			<label class="control-label" for="attachments">Anh&auml;nge:<br /> (Max. {$maxuploadsize})</label>
			<div class="controls">
				{foreach from=$attachments key=partid item=attachment}
					<input type="checkbox" name="storedattachment[]" value="{$partid}" checked="checked" />
					<strong><a href="attachment.php?boardid={$board.boardid|escape:url}&amp;partid={$partid|escape:html}">{$attachment.filename}</a></strong><br />
				{/foreach}

				<p id="attachments">
					<input type="file" name="attachment[]" />
				</p>
				<p><a href="#attachments" onclick="addAttachmentField()">Weitere Datei anhängen</a></p>
			</div>


			<div class="form-actions">
				<button type="submit" class="btn btn-primary" name="post" value="1"><i class="icon-edit icon-white"></i> Schreiben</button>
				<button type="submit" class="btn" name="preview" value="1"><i class="icon-check"></i> Vorschau</button>
			</div>
		</div>
	</fieldset>
</form>

{if isset($referencemessages)}
<table class="table table-striped table-bordered">
 {foreach from=$referencemessages item=message name=counter}
  {include file=message.html.tpl message=$message first=$smarty.foreach.counter.first id=$smarty.foreach.counter.iteration}
 {/foreach}
</table>
{/if}

<br />
{include file=footer.html.tpl}

{include file=header.html.tpl title=$thread.subject}

<ul class="breadcrumb">
	{include file=thread_breadcrumb.html.tpl board=$board thread=$thread}
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

//-->
</script>
{/literal}

{if $pages > 1}
{capture assign="baseurl"}viewboard.php?boardid={$board.boardid}&amp;page={/capture}
{include file="pagination.html.tpl" baseurl=$baseurl page=$page pagecount=$pages}
{/if}

{foreach from=$messages item=message name=counter}
{include file=message.html.tpl message=$message first=$smarty.foreach.counter.first id=$smarty.foreach.counter.iteration}
{/foreach}

{if $pages > 1}
{capture assign="baseurl"}viewboard.php?boardid={$board.boardid}&amp;page={/capture}
{include file="pagination.html.tpl" baseurl=$baseurl page=$page pagecount=$pages}
{/if}

{include file=footer.html.tpl}

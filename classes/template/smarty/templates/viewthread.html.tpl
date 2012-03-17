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
{capture assign="baseurl"}viewthread.php?boardid={$board.boardid}&amp;threadid={$thread.threadid}&amp;page={/capture}
{include file="pagination.html.tpl" baseurl=$baseurl page=$page pagecount=$pages}
{/if}
<table class="table table-striped table-bordered">
{foreach from=$messages item=message name=counter}
{include file=message.html.tpl message=$message first=$smarty.foreach.counter.first id=$smarty.foreach.counter.iteration}
{/foreach}
</table>
{if $pages > 1}
{capture assign="baseurl"}viewthread.php?boardid={$board.boardid}&amp;threadid={$thread.threadid}&amp;page={/capture}
{include file="pagination.html.tpl" baseurl=$baseurl page=$page pagecount=$pages}
{/if}

<div class="modal fade" id="delModal">
	  <div class="modal-header">
	    <a class="close" data-dismiss="modal">×</a>
	    <h3>Achtung:</h3>
	  </div>
	  <div class="modal-body">
	    <p>Soll der Eintrag wirklich gelöscht werden?</p>
	  </div>
	  <div class="modal-footer">
	    <a class="btn btn-danger" id="delButton">Löschen</a>
	    <a class="btn" data-dismiss="modal">Abbrechen</a>
	  </div>
</div>
<script type="text/javascript">
{literal}
	function deletePost(boardid, messageid) {
		event.stopImmediatePropagation();
		$("#delModal").children(".modal-footer").children("#delButton").attr("href", "cancel.php?boardid=" + boardid + "&amp;messageid=" + messageid);
		$("#delModal").modal();
	}
{/literal}
</script>


{include file=footer.html.tpl}

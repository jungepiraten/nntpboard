{include file=header.html.tpl title="Suchen"}

<form action="search.php" method="post" class="form-horizontal">
	<fieldset>
		<div class="control-group">
			<label for="term" class="control-label">Suchen:</label>
			<p class="controls">
				<input type="text" class="term" name="term" value="{if isset($smarty.request.term)}{$smarty.request.term|stripslashes|escape:html}{/if}" />
			</p>
		</div>
		
		<div class="form-actions">
			<button type="submit" class="btn btn-primary">Durchsuchen</button>
		</div>
	</fieldset>
</form>
{include file=footer.html.tpl}

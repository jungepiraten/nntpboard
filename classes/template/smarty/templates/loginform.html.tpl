{include file=header.html.tpl title="Login"}

{if $loginfailed}
<p class="error">Login fehlgeschlagen!</p>
{/if}

<form action="login.php" method="post" class="form-horizontal">
	<fieldset>
		<input type="hidden" name="redirect" value="{$referer|escape:html}" />
		
		<div class="control-group">
			<label for="username" class="control-label">Benutzername:</label>
			<p class="controls">
				<input type="text" class="username" name="username" value="{if isset($smarty.request.username)}{$smarty.request.username|stripslashes|escape:html}{/if}" />
			</p>
		</div>
		
		<div class="control-group">
			<label for="password" class="control-label">Passwort:</label>
			<p class="controls">
				<input type="password" class="password" name="password" id="password"/>
			</p>
		</div>

		<div class="control-group">
			<p class="controls">
				<input type="checkbox" class="permanent" name="permanent" id="permanent"/> Dauerhaft anmelden?
			</p>
		</div>
		
		<div class="form-actions">
			<button type="submit" class="btn btn-primary" name="login" value="1">Anmelden</button>
		</div>
	</fieldset>
</form>
{include file=footer.html.tpl}

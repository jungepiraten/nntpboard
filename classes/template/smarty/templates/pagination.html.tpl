<div class="pagination pagination-left no-top-bottom-margin">
	<ul>
		{if $page > 3}
			{section name=pages loop=$pagecount start=0 max=2}
			<li><a href="{$baseurl}{$smarty.section.pages.index}">{$smarty.section.pages.index+1}</a></li>
			{/section}
			{if $page > 5}
			<li class="disabled"><a>...</a></li>
			{elseif $page == 5}
			<li><a href="{$baseurl}2">3</a></li>
			{/if}
			{section name=pages loop=$pagecount start=$page-1 max=1}
			<li><a href="{$baseurl}{$smarty.section.pages.index}">{$smarty.section.pages.index+1}</a></li>
			{/section}
		{else}
			{section name=pages loop=$pagecount start=0 max=$page}
			<li><a href="{$baseurl}{$smarty.section.pages.index}">{$smarty.section.pages.index+1}</a></li>
			{/section}
		{/if}
		<li class="active"><a href="{$baseurl}{$page}">{$page+1}</a></li>
		{if $pagecount-$page > 3}
			{section name=pages loop=$pagecount start=$page+1 max=1}
			<li><a href="{$baseurl}{$smarty.section.pages.index}">{$smarty.section.pages.index+1}</a></li>
			{/section}
			{if $pagecount-$page > 5}
			<li class="disabled"><a>...</a></li>
			{elseif $pagecount-$page == 5}
			<li><a href="{$baseurl}{$page+2}">{$page+2+1}</a></li>
			{/if}
			{section name=pages loop=$pagecount start=$pagecount-2 max=2}
			<li><a href="{$baseurl}{$smarty.section.pages.index}">{$smarty.section.pages.index+1}</a></li>
			{/section}
		{else}
			{section name=pages loop=$pagecount start=$page+1 max=$pagecount-$page-1}
			<li><a href="{$baseurl}{$smarty.section.pages.index}">{$smarty.section.pages.index+1}</a></li>
			{/section}
		{/if}
	</ul>
</div>

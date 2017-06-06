{extends file="parent:frontend/index/index.tpl"}

	{block name='frontend_index_navigation_categories_top' append} 
			{if $page != 'index' && $page != ''}
     		{if $shopId == 1}
    			{action module=widgets controller=SwagDigitalPublishing bannerId=35}
     		{/if}
     		{if $shopId == 2}
     			{action module=widgets controller=SwagDigitalPublishing bannerId=41}
     		{/if}
     		{if $shopId == 3}
     			{action module=widgets controller=SwagDigitalPublishing bannerId=42}
    		{/if}           		           		
     	{/if}
	{/block}
	
	{block name='frontend_detail_index_name' append}
			{if $shopId == 1}
				{action module=widgets controller=SwagDigitalPublishing bannerId=38}
			{/if}
			{if $shopId == 2}
				{action module=widgets controller=SwagDigitalPublishing bannerId=43}
			{/if}
			{if $shopId == 3}
				{action module=widgets controller=SwagDigitalPublishing bannerId=44}
			{/if} 
	{/block}	
	
	{block name='frontend_checkout_cart_table_header' prepend}
		{if $shopId == 1}
			{action module=widgets controller=SwagDigitalPublishing bannerId=36}
		{/if}
		{if $shopId == 2}
			{action module=widgets controller=SwagDigitalPublishing bannerId=45}
		{/if}
		{if $shopId == 3}
			{action module=widgets controller=SwagDigitalPublishing bannerId=46}
		{/if} 
	{/block}	

{extends file='frontend/index/index.tpl'}

{* Title *}
{block name='frontend_index_header_title'}
    {s name="CartTitle"}{/s} | {{config name=shopName}|escapeHtml}
{/block}

{* Hide breadcrumb *}
{block name='frontend_index_breadcrumb'}{/block}

{* Step Box *}
{block name="frontend_index_content_top"}{/block}

{* Main content *}
{block name='frontend_index_content'}
    <div class="content content--basket content--checkout">            
        {* If articles are in the basket... *}
        {if $sBasket.content}

            {* Add article informations *}
            {block name='frontend_checkout_add_article'}
                <noscript>
                    {include file='frontend/checkout/added.tpl'}
                </noscript>
            {/block}       
            <div class="product-cart-inner clearfix">     
                {* Product table *}
                {block name='frontend_checkout_cart_table'}
                    <div class="product--table{if {config name=BasketShippingInfo}} has--dispatch-info{/if}">
				           		{if $smarty.server.REQUEST_URI|strpos:"/de-de" !== false}
				           			{action module=widgets controller=SwagDigitalPublishing bannerId=36}
				           		{/if}
				           		{if $smarty.server.REQUEST_URI|strpos:"/ch-de" !== false}
				           			{action module=widgets controller=SwagDigitalPublishing bannerId=45}
				           		{/if}
				           		{if $smarty.server.REQUEST_URI|strpos:"/at-de" !== false}
				           			{action module=widgets controller=SwagDigitalPublishing bannerId=46}
				           		{/if} 
                        <h3 class="cart-title">{s name="CheckoutActionsCartTitle" namespace="frontend/checkout/actions"}{/s}</h3>
                        <!--
                        <div class="top-checkout-banner">
                            {action module=widgets controller=SwagDigitalPublishing bannerId=11}
                        </div>
                        -->
                        {* Deliveryfree dispatch notification *}
                        {block name='frontend_checkout_cart_deliveryfree'}
                            {if $sShippingcostsDifference}
                                {$shippingDifferenceContent="<strong>{s name='CartInfoFreeShipping'}{/s}</strong> {s name='CartInfoFreeShippingDifference'}{/s}"}
                                {include file="frontend/_includes/messages.tpl" type="warning" content="{$shippingDifferenceContent}"}
                            {/if}
                        {/block}

                        {* Error messages *}
                        {block name='frontend_checkout_cart_error_messages'}
                            {include file="frontend/checkout/error_messages.tpl"}
                        {/block}
                        {* Product table content *}
                        {block name='frontend_checkout_cart_panel'}
                            <div class="panel">
                                <div class="panel--body">
                                    {* Basket items *}
                                    {foreach $sBasket.content as $sBasketItem}
                                        {block name='frontend_checkout_cart_item'}
                                            {include file='frontend/checkout/cart_item.tpl' isLast=$sBasketItem@last}
                                        {/block}
                                    {/foreach}
                                    <!--
                                    <div class="bottom-checkout-banner">
                                        {action module=widgets controller=SwagDigitalPublishing bannerId=12}
                                    </div>
                                    -->
                                    {* Product table footer *}
                                    {block name='frontend_checkout_cart_cart_footer'}
                                        {include file="frontend/checkout/cart_footer.tpl"}
                                    {/block}
                                </div>
                            </div>
                        {/block}

                        {* Premium products *}
                        {block name='frontend_checkout_cart_premium'}
                            {if $sPremiums}

                                {* Actual listing *}
                                {block name='frontend_checkout_cart_premium_products'}
                                    {include file='frontend/checkout/premiums.tpl'}
                                {/block}
                            {/if}
                        {/block}
                        
                        {block name='frontend_checkout_cart_table_actions_bottom'}
                            <div class="table--actions actions--bottom">
                                {block name="frontend_checkout_actions_confirm_bottom"}
                                    <div class="main--actions">

                                        {* Contiune shopping *}
                                        {if $sBasket.sLastActiveArticle.link}
                                            {block name="frontend_checkout_actions_link_last_bottom"}
                                                <a href="{$sBasket.sLastActiveArticle.link}"
                                                   title="{"{s name='CheckoutActionsLinkLast' namespace="frontend/checkout/actions"}{/s}"|escape}"
                                                   class="btn btn--checkout-continue is--primary continue-shopping--action">
                                                    {s name="CheckoutActionsLinkLast" namespace="frontend/checkout/actions"}{/s}
                                                </a>
                                            {/block}
                                        {/if}

                                        {* Forward to the checkout *}
                                        {if !$sMinimumSurcharge && !($sDispatchNoOrder && !$sDispatches)}
                                            {block name="frontend_checkout_actions_confirm_bottom_checkout"}
                                                <a href="{if {config name=always_select_payment}}{url controller='checkout' action='shippingPayment'}{else}{url controller='checkout' action='confirm'}{/if}"
                                                   title="{"{s name='CheckoutActionsLinkProceedShort' namespace="frontend/checkout/actions"}{/s}"|escape}"
                                                   class="btn btn--checkout-proceed is--primary right">
                                                    {s name="CheckoutActionsLinkProceedShort" namespace="frontend/checkout/actions"}{/s}
                                                </a>
                                            {/block}
                                        {else}
                                            {block name="frontend_checkout_actions_confirm_bottom_checkout"}
                                                <span
                                                   title="{"{s name='CheckoutActionsLinkProceedShort' namespace="frontend/checkout/actions"}{/s}"|escape}"
                                                   class="btn is--disabled btn--checkout-proceed is--primary right">
                                                    {s name="CheckoutActionsLinkProceedShort" namespace="frontend/checkout/actions"}{/s}
                                                </span>
                                            {/block}
                                        {/if}
                                    </div>
                                {/block}
                            </div>
                        {/block}
                    </div>
                    {*$sCharts={$sCharts|@debug_print_var}*}
                    <div class="side-product-table-products">
                        {* Topseller right block *}
                        {block name="frontend_index_left_topseller"}
                            {$counter = 0}
                            {for $counter=0 to $perPage-1}
                                {block name="widgets_listing_top_seller_slider_container_include"}
                                    {include file="frontend/listing/box_article.tpl" sArticle=$sCharts.$counter productBoxLayout="slider"}
                                {/block}
                            {/for}
                        {/block}
                    </div>
                </div>
                <div class="top-seller-cart">
                    {* Topseller *}
                    {block name="frontend_listing_index_topseller"}
                        {if !$hasEmotion && {config name=topSellerActive}}
                            {action module=widgets controller=listing action=top_seller sCategory=$sCategoryContent.id}
                        {/if}
                    {/block}
                </div>
            <!--
                <div class="bottom-checkout-banner-full-width">
                    {action module=widgets controller=SwagDigitalPublishing bannerId=13}
                </div>
            -->
            {/block}

        {else}
            {* Empty basket *}
            {block name='frontend_basket_basket_is_empty'}
                <div class="basket--info-messages">
                    {include file="frontend/_includes/messages.tpl" type="warning" content="{s name='CartInfoEmpty'}{/s}"}
                </div>
            {/block}
        {/if}
    </div>
{/block}

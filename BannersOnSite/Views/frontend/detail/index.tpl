{extends file='parent:frontend/detail/index.tpl'}

{* Custom header *}
{block name='frontend_index_header'}
    {include file="frontend/detail/header.tpl"}
{/block}

{* Modify the breadcrumb *}
{block name='frontend_index_breadcrumb_inner' prepend}
    {block name="frontend_detail_breadcrumb_overview"}
        {if !{config name=disableArticleNavigation}}
            {$breadCrumbBackLink = $sBreadcrumb[count($sBreadcrumb) - 1]['link']}
            <a class="breadcrumb--button breadcrumb--link" href="{if $breadCrumbBackLink}{$breadCrumbBackLink}{else}#{/if}" title="{s name="DetailNavIndex" namespace="frontend/detail/navigation"}{/s}">
                <i class="icon--arrow-left"></i>
                <span class="breadcrumb--title">{s name='DetailNavIndex' namespace="frontend/detail/navigation"}{/s}</span>
            </a>
        {/if}
    {/block}
{/block}

{* Main content *}
{block name='frontend_index_content'}
    <div class="content product--details" itemscope itemtype="http://schema.org/Product"{if !{config name=disableArticleNavigation}} data-product-navigation="{url module="widgets" controller="listing" action="productNavigation"}" data-category-id="{$sArticle.categoryID}" data-main-ordernumber="{$sArticle.mainVariantNumber}"{/if} data-ajax-wishlist="true" data-compare-ajax="true"{if $theme.ajaxVariantSwitch} data-ajax-variants-container="true"{/if}>

        {* The configurator selection is checked at this early point
           to use it in different included files in the detail template. *}
        {block name='frontend_detail_index_configurator_settings'}

            {* Variable for tracking active user variant selection *}
            {$activeConfiguratorSelection = true}

            {if $sArticle.sConfigurator && ($sArticle.sConfiguratorSettings.type == 1 || $sArticle.sConfiguratorSettings.type == 2)}
                {* If user has no selection in this group set it to false *}
                {foreach $sArticle.sConfigurator as $configuratorGroup}
                    {if !$configuratorGroup.selected_value}
                        {$activeConfiguratorSelection = false}
                    {/if}
                {/foreach}
            {/if}
        {/block}
        <div class="product-top-detail-inner clearfix">
        	<div class="manufacturer-logo">        		
        		{* Product - Supplier information *}
                    {block name='frontend_detail_supplier_info'}
                        <div class="product--supplier-logo">
                            <a href="{url controller='listing' action='manufacturer' sSupplier=$sArticle.supplierID}"
                               title="{"{s name="DetailDescriptionLinkInformation" namespace="frontend/detail/description"}{/s}"|escape}"
                               class="product--supplier-link">
                                {if $sArticle.supplierImg}
									<img src="{$sArticle.supplierImg}" alt="{$sArticle.supplierName|escape}">
								{else}
									<span class="supplier-name">{$sArticle.supplierName|escape}</span>
								{/if}
                            </a>
                        </div>
                    {/block}
        	</div>
	        {* Product header *}
	        {block name='frontend_detail_index_header'}
	            <header class="product--header">
	                {block name='frontend_detail_index_header_inner'}
	                    <div class="product--info">
	                        {block name='frontend_detail_index_product_info'}

	                            {* Product name *}
	                            {block name='frontend_detail_index_name'}
	                                <h1 class="product--title" itemprop="name">
	                                    {$sArticle.articleName}
	                                </h1>
	                            {/block}

                              {if $sArticle.attr11 != ''}
									           		{if $smarty.server.REQUEST_URI|strpos:"/de-de" !== false}
									           			{action module=widgets controller=SwagDigitalPublishing bannerId=38}
									           		{/if}
									           		{if $smarty.server.REQUEST_URI|strpos:"/ch-de" !== false}
									           			{action module=widgets controller=SwagDigitalPublishing bannerId=43}
									           		{/if}
									           		{if $smarty.server.REQUEST_URI|strpos:"/at-de" !== false}
									           			{action module=widgets controller=SwagDigitalPublishing bannerId=44}
									           		{/if} 
					 	 	                {/if}
	                           
							{/block}
	                    </div>
	                {/block}
	            </header>
	        {/block}

	        <div class="product--detail-upper block-group">
	            {* Product image *}
	            {block name='frontend_detail_index_image_container'}
	                <div class="product--image-container image-slider{if $sArticle.image && {config name=sUSEZOOMPLUS}} product--image-zoom{/if}"
	                    {if $sArticle.image}
	                     data-image-slider="true"
	                     data-image-gallery="true"
	                     data-maxZoom="{$theme.lightboxZoomFactor}"
	                     data-thumbnails=".image--thumbnails"
	                    {/if}>
	                    {include file="frontend/detail/image.tpl"}
	                    {* Product rating *}
                            {block name="frontend_detail_comments_overview"}
                                {if !{config name=VoteDisable}}
                                    <div class="product--rating-container">
                                        {include file='frontend/_includes/rating.tpl' points=$sArticle.sVoteAverage.average type="aggregated" count=$sArticle.sVoteAverage.count}
                                    </div>
                                {/if}
                            {/block}
	                </div>
	            {/block}

	            {* "Buy now" box container *}
	            {block name='frontend_detail_index_buy_container'}
	                <div class="product--buybox block{if $sArticle.sConfigurator && $sArticle.sConfiguratorSettings.type==2} is--wide{/if}">

	                    {block name="frontend_detail_rich_snippets_brand"}
	                        <meta itemprop="brand" content="{$sArticle.supplierName|escape}"/>
	                    {/block}

	                    {block name="frontend_detail_rich_snippets_weight"}
	                        {if $sArticle.weight}
	                            <meta itemprop="weight" content="{$sArticle.weight} kg"/>
	                        {/if}
	                    {/block}

	                    {block name="frontend_detail_rich_snippets_height"}
	                        {if $sArticle.height}
	                            <meta itemprop="height" content="{$sArticle.height} cm"/>
	                        {/if}
	                    {/block}

	                    {block name="frontend_detail_rich_snippets_width"}
	                        {if $sArticle.width}
	                            <meta itemprop="width" content="{$sArticle.width} cm"/>
	                        {/if}
	                    {/block}

	                    {block name="frontend_detail_rich_snippets_depth"}
	                        {if $sArticle.length}
	                            <meta itemprop="depth" content="{$sArticle.length} cm"/>
	                        {/if}
	                    {/block}

	                    {block name="frontend_detail_rich_snippets_release_date"}
	                        {if $sArticle.sReleasedate}
	                            <meta itemprop="releaseDate" content="{$sArticle.sReleasedate}"/>
	                        {/if}
	                    {/block}

	                    {block name='frontend_detail_buy_laststock'}
	                        {if !$sArticle.isAvailable && ($sArticle.isSelectionSpecified || !$sArticle.sConfigurator)}
	                            {include file="frontend/_includes/messages.tpl" type="error" content="{s name='DetailBuyInfoNotAvailable' namespace='frontend/detail/buy'}{/s}"}
	                        {/if}
	                    {/block}

	                    {* Product eMail notification *}
	                    {block name="frontend_detail_index_notification"}
	                        {if $sArticle.notification && $sArticle.instock <= 0 && $ShowNotification}
	                            {include file="frontend/plugins/notification/index.tpl"}
	                        {/if}
	                    {/block}

	                    {* Product data *}
	                    {block name='frontend_detail_index_buy_container_inner'}
	                        <div itemprop="offers" itemscope itemtype="{if $sArticle.sBlockPrices}http://schema.org/AggregateOffer{else}http://schema.org/Offer{/if}" class="buybox--inner">

	                            {* Configurator drop down menu's *}
	                            {block name="frontend_detail_index_configurator"}
	                                <div class="product--configurator">
	                                    {if $sArticle.sConfigurator}
	                                        {if $sArticle.sConfiguratorSettings.type == 1}
	                                            {include file="frontend/detail/config_step.tpl"}
	                                        {elseif $sArticle.sConfiguratorSettings.type == 2}
	                                            {include file="frontend/detail/config_variant.tpl"}
	                                        {else}
	                                            {include file="frontend/detail/config_upprice.tpl"}
	                                        {/if}
	                                    {/if}
	                                </div>
	                            {/block}

	                            {block name='frontend_detail_index_data'}
	                                {if $sArticle.sBlockPrices}
	                                    {$lowestPrice=false}
	                                    {$highestPrice=false}
	                                    {foreach $sArticle.sBlockPrices as $blockPrice}
	                                        {if $lowestPrice === false || $blockPrice.price < $lowestPrice}
	                                            {$lowestPrice=$blockPrice.price}
	                                        {/if}
	                                        {if $highestPrice === false || $blockPrice.price > $highestPrice}
	                                            {$highestPrice=$blockPrice.price}
	                                        {/if}
	                                    {/foreach}

	                                    <meta itemprop="lowPrice" content="{$lowestPrice}" />
	                                    <meta itemprop="highPrice" content="{$highestPrice}" />
	                                    <meta itemprop="offerCount" content="{$sArticle.sBlockPrices|count}" />
	                                {else}
	                                    <meta itemprop="priceCurrency" content="{$Shop->getCurrency()->getCurrency()}"/>
	                                {/if}
	                                {include file="frontend/detail/data.tpl" sArticle=$sArticle sView=1}
	                            {/block}

	                            {block name='frontend_detail_index_after_data'}{/block}

	                            

	                            {* Product actions *}
	                            {block name="frontend_detail_index_actions"}
	                                <nav class="product--actions">
	                                    {include file="frontend/detail/actions.tpl"}
	                                </nav>
	                            {/block}
	                            {* Include buy button and quantity box *}
	                            {block name="frontend_detail_index_buybox"}
	                                {include file="frontend/detail/buy.tpl"}
	                            {/block}
	                        </div>
	                    {/block}
	                </div>
	            {/block}
	        </div>
	    </div>

        {* Product bundle hook point *}
        {block name="frontend_detail_index_bundle"}{/block}

        {block name="frontend_detail_index_detail"}

            {* Tab navigation *}
            {block name="frontend_detail_index_tabs"}
                {include file="frontend/detail/tabs_description.tpl"}
                {* Similar products slider *}
                {if $sArticle.sSimilarArticles}
                	<div class="similar-product-slider-inner">
	                    {* Similar products *}                    
	                    {block name="frontend_detail_index_tabs_similar"}
	                        <div class="tab--container">
	                        	{block name="frontend_detail_index_recommendation_tabs_entry_similar_products"}
			                        <div class="slider-similar-title">{s name="DetailRecommendationSimilarLabel"}{/s}</div>
			                    {/block}
	                            {block name="frontend_detail_index_tabs_similar_inner"}
	                                <div class="tab--content content--similar">{include file='frontend/detail/tabs/similar.tpl'}</div>
	                            {/block}
	                        </div>
	                    {/block}
	                </div>
	                <div class="clearfix"></div>
                {/if}
                {include file="frontend/detail/tabs_rating.tpl"}
            {/block}
        {/block}
        <div class="clearfix"></div>
        {* Crossselling tab panel *}
        {block name="frontend_detail_index_tabs_cross_selling"}

            {$showAlsoViewed = {config name=similarViewedShow}}
            {$showAlsoBought = {config name=alsoBoughtShow}}

            {*<div class="tab-menu--cross-selling"{if $sArticle.relatedProductStreams} data-scrollable="true"{/if}>*}
            <div class="crossselling-product-slider-inner"{if $sArticle.relatedProductStreams} data-scrollable="true"{/if}>


                {* Tab content container *}
                {block name="frontend_detail_index_outer_tabs"}
                    <div class="tab--container-list">
                        {block name="frontend_detail_index_inner_tabs"}
                            {block name='frontend_detail_index_before_tabs'}{/block}



                            {* Accessory articles *}
                            {block name="frontend_detail_index_tabs_related"}
                                {if $sArticle.sRelatedArticles && !$sArticle.crossbundlelook}
                                    {* Tab navigation - Accessory products *}
                                    {block name="frontend_detail_tabs_entry_related"}
                                            <div class="cosselling-title">
                                                {s namespace="frontend/detail/tabs" name='DetailTabsAccessories'}{/s}                                            
                                            </div>
                                    {/block}
                                    <div class="tab--container">
                                        {block name="frontend_detail_index_tabs_related_inner"}
                                            <div class="tab--content content--related">{include file="frontend/detail/tabs/related.tpl"}</div>
                                        {/block}
                                    </div>
                                {/if}
                            {/block}

                            {* Similar products slider *}
                            {*{if $sArticle.sSimilarArticles}
                                *}{* Similar products *}{*
                                {block name="frontend_detail_index_recommendation_tabs_entry_similar_products"}
                                    <div class="">{s name="DetailRecommendationSimilarLabel"}{/s}</div>
                                {/block}
                                {block name="frontend_detail_index_tabs_similar"}
                                    <div class="tab--container">
                                        {block name="frontend_detail_index_tabs_similar_inner"}
                                            <div class="tab--content content--similar">{include file='frontend/detail/tabs/similar.tpl'}</div>
                                        {/block}
                                    </div>
                                {/block}
                            {/if}*}
                            {if $boughtArticles}
                            <div class="slider-also-buy-inner">
	                            {* "Customers bought also" slider *}
	                            {if $showAlsoBought}
	                                {* Customer also bought *}
	                                {block name="frontend_detail_index_tabs_entry_also_bought"}
	                                    <div class="slider-title-also-buy">{s name="DetailRecommendationAlsoBoughtLabel"}{/s}</div>
	                                {/block}
	                                {block name="frontend_detail_index_tabs_also_bought"}
	                                    <div class="tab--container">
	                                        {block name="frontend_detail_index_tabs_also_bought_inner"}
	                                            <div class="tab--content content--also-bought">{action module=widgets controller=recommendation action=bought articleId=$sArticle.articleID}</div>
	                                        {/block}
	                                    </div>
	                                {/block}
	                            {/if}
	                        </div>
	                        {/if}
	                        {*<div class="slider-also-view-inner">
	                            *}{* "Customers similar viewed" slider *}{*
	                            {if $showAlsoViewed}
	                                *}{* Customer also viewed *}{*
	                                {block name="frontend_detail_index_tabs_entry_also_viewed"}
	                                        <div class="slider-title-also-view">{s name="DetailRecommendationAlsoViewedLabel"}{/s}</div>
	                                {/block}
	                                {block name="frontend_detail_index_tabs_also_viewed"}
	                                    <div class="tab--container">
	                                        {block name="frontend_detail_index_tabs_also_viewed_inner"}
	                                            <div class="tab--content content--also-viewed">{action module=widgets controller=recommendation action=viewed articleId=$sArticle.articleID}</div>
	                                        {/block}
	                                    </div>
	                                {/block}
	                            {/if}
	                        </div>*}

                            {block name='frontend_detail_index_after_tabs'}{/block}
                        {/block}
                    </div>
                {/block}
            </div>
        {/block}
		{*</br>
		$sArticle={$sArticle|@debug_print_var}
		</br>
		$sArticle={$sArticle.sProperties|@debug_print_var}
		</br>
		$sArticle={$sArticle.sProperties.1|@debug_print_var}
		</br>
		$sArticle={$sArticle.sProperties.1.value|@debug_print_var}
		</br>
		$sArticle={$sArticle.sProperties[1].values|@debug_print_var}
		</br>
		$sArticle={$sArticle.sProperties[1].values[1]|@debug_print_var}
		</br>
		$sArticle={$sArticle->Collection|@debug_print_var}*}
    </div>
{/block}

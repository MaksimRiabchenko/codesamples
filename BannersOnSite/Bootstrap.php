<?php

class Shopware_Plugins_Frontend_BannersOnSite_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
	
    public function getLabel()
    {
        return 'Banners on site';
    }

    public function install()
    {
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatchSecure_Frontend',
            'onFrontendPostDispatch'
        );        

	      return true;
    }
    
    public function onFrontendPostDispatch(Enlight_Event_EventArgs $args)
    {
        /** @var \Enlight_Controller_Action $controller */
        $controller = $args->get('subject');
        $view = $controller->View();

        $view->addTemplateDir(
            __DIR__ . '/Views'
        );
        
        $shopId = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext()->getCurrentCustomerGroup()->getId();
        $request = $controller->Request();

        $view->assign('shopId', $shopId);
        $view->assign('page', $request->getParams()['controller']);
    }    
}

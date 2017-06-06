<?php

namespace ShopwarePlugins\CronChangeOrderStatus\Components;

class DocumentCLI extends \Shopware_Components_Document
{
    public static function initDocument($orderID, $documentID, array $config = array())
    {
        if (empty($orderID)) {
            $config["_preview"] = true;
        }

        /** @var $document DocumentCLI */
        $document = \Enlight_Class::Instance('ShopwarePlugins\CronChangeOrderStatus\Components\DocumentCLI');

        $document->setOrder(\Enlight_Class::Instance('Shopware_Models_Document_Order', array($orderID, $config)));

        $document->setConfig($config);

        $document->setDocumentId($documentID);
        if (!empty($orderID)) {
            $document->_subshop = Shopware()->Db()->fetchRow("
                SELECT
                    s.id,
                    m.document_template_id as doc_template_id,
                    m.template_id as template_id,
                    (SELECT CONCAT('templates/', template) FROM s_core_templates WHERE id = m.document_template_id) as doc_template,
                    (SELECT CONCAT('templates/', template) FROM s_core_templates WHERE id = m.template_id) as template,
                    s.id as isocode,
                    s.locale_id as locale
                FROM s_order, s_core_shops s
                LEFT JOIN s_core_shops m
                    ON m.id=s.main_id
                    OR (s.main_id IS NULL AND m.id=s.id)
                WHERE s_order.language = s.id
                AND s_order.id = ?
                ",
                array($orderID)
            );

            if (empty($document->_subshop["doc_template"])) {
                $document->setTemplate($document->_defaultPath);
            }

            if (empty($document->_subshop["id"])) {
                throw new Enlight_Exception("Could not load template path for order $orderID");
            }
        } else {
            $document->_subshop = Shopware()->Db()->fetchRow("
            SELECT
                s.id,
                s.document_template_id as doc_template_id,
                s.template_id,
                (SELECT CONCAT('templates/', template) FROM s_core_templates WHERE id = s.document_template_id) as doc_template,
                (SELECT CONCAT('templates/', template) FROM s_core_templates WHERE id = s.template_id) as template,
                s.id as isocode,
                s.locale_id as locale
            FROM s_core_shops s
            WHERE s.default = 1
            ");

            $document->setTemplate($document->_defaultPath);
            $document->_subshop["doc_template"] = $document->_defaultPath;
        }

        $document->setTranslationComponent();
        $document->initTemplateEngine();

        return $document;
    }

    protected function assignValues4x()
    {
        $id = $this->_documentID;

        $Document = $this->_document->getArrayCopy();
        if (empty($this->_config["date"])) {
            $this->_config["date"] = date("d.m.Y");
        }
        $Document = array_merge(
            $Document,
            array(
                "comment" => $this->_config["docComment"],
                "id" => $id,
                "bid" => $this->_documentBid,
                "date" => $this->_config["date"],
                "deliveryDate" => $this->_config["delivery_date"],
                // The "netto" config flag, if set to true, allows creating
                // netto documents for brutto orders. Setting it to false,
                // does not however create brutto documents for netto orders.
                "netto" => $this->_order->order->taxfree ? true : $this->_config["netto"],
                "nettoPositions" => $this->_order->order->net
            )
        );
        $Document["voucher"] = $this->getVoucher($this->_config["voucher"]);
        $this->_view->assign('Document', $Document);

        // Translate payment and dispatch depending on the order's language
        // and replace the default payment/dispatch text
        $dispatchId = $this->_order->order->dispatchID;
        $paymentId = $this->_order->order->paymentID;
        $translationPayment = $this->readTranslationWithFallback($this->_order->order->language, 'config_payment');
        $translationDispatch = $this->readTranslationWithFallback($this->_order->order->language, 'config_dispatch');

        if (isset($translationPayment[$paymentId])) {
            if (isset($translationPayment[$paymentId]['description'])) {
                $this->_order->payment->description = $translationPayment[$paymentId]['description'];
            }
            if (isset($translationPayment[$paymentId]['additionalDescription'])) {
                $this->_order->payment->additionaldescription = $translationPayment[$paymentId]['additionalDescription'];
            }
        }

        if (isset($translationDispatch[$dispatchId])) {
            if (isset($translationDispatch[$dispatchId]['dispatch_name'])) {
                $this->_order->dispatch->name = $translationDispatch[$dispatchId]['dispatch_name'];
            }
            if (isset($translationDispatch[$dispatchId]['dispatch_description'])) {
                $this->_order->dispatch->description = $translationDispatch[$dispatchId]['dispatch_description'];
            }
        }

        $this->_view->assign('Order', $this->_order->__toArray());
        $this->_view->assign('Containers', $this->_document->containers->getArrayCopy());

        $order = clone $this->_order;

        $positions = $order->positions->getArrayCopy();

        //TODO: needs to fix emergency!!!

//        $articleModule = \Shopware_Components_Modules::Instance('sArticles');
//
//        foreach ($positions as &$position) {
//            //$position['meta'] = $articleModule->sGetPromotionById('fix', 0, (int) $position['articleID']);
//            $articleID = $position['articleID'];
//            if($articleID) {
//                $article = $articleModule->sGetArticleById($articleID);
//                $position['purchaseunit'] = $article['purchaseunit'];
//                $position['sUnit'] = $article['sUnit'];
//            }
//        }

        if ($this->_config["_previewForcePagebreak"]) {
            $positions = array_merge($positions, $positions);
        }

        $positions = array_chunk($positions, $this->_document["pagebreak"], true);
        $this->_view->assign('Pages', $positions);

        $user = array(
            "shipping" => $order->shipping,
            "billing" => $order->billing,
            "additional" => array(
                "countryShipping" => $order->shipping->country,
                "country" => $order->billing->country
            )
        );
        $this->_view->assign('User', $user);
    }
}

<?php

use ShopwarePlugins\CronChangeOrderStatus\Components\DocumentGenerator;

class Shopware_Plugins_Frontend_CronChangeOrderStatus_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    const INPUT_DIR = 'import/xml_product/product_input';
    const OUTPUT_DIR = 'import/xml_product/product_output';
    const ARCHIVE_DIR = 'import/archive_orders';

    const TABLE_CUSTOMER = 's_user_billingaddress';
    const TABLE_PREMIUM_DISPATCH = 's_premium_dispatch';
    const TABLE_ORDER = 's_order';
    const TABLE_DETAILS = 's_order_details';
    const TABLE_ORDER_DOCUMENTS = 's_order_documents';

    const STATUS_CANCELED = -1; //Abgebrochen
    const STATUS_OPEN = 0; //Offen
    const STATUS_IN_PROCESS = 1;    //In Bearbeitung (Wartet)
    const STATUS_READY_FOR_DELIVERY = 5; //Zur Lieferung bereit
    const STATUS_PARTIALLY_DELIVERED = 6; //Teilweise ausgeliefert
    const STATUS_COMPLETELY_DELIVERED = 7; //Komplett ausgeliefert
    const STATUS_STORNO = 37; //Storno S303
    const STATUS_FULL_CANCELATION = 100; //Komplett storniert
    const STATUS_DELIVERY_PROCESSED = 101; //Warenausgang
    const STATUS_RETURN_RECEIVED = 102; //Retoure erhalten
    const STATUS_PARTIALLY_RETURNED = 103; //Teilweise retourniert
    const STATUS_FULL_RETURN = 104; //Komplett retourniert
    const STATUS_CANCELATION_RECEIVED = 105; //Storno gemeldet
    const STATUS_PROCESS_CANCELATION = 106; //Vollstorno
    const STATUS_DELIVERY_PARTLY_PROCESSED = 107; //Teilwarenausgang
    const STATUS_PARTIALLY_CANCELLED = 108; //Teilstorno
    const STATUS_PARTIALLY_INVOICE_CREATED = 109; //Teilrechnung erstellt
    const STATUS_INVOICE_CREATED = 110; //Rechnung erstellt

    const PAYMENT_PARTIALLY_PAID = 11; //Teilweise bezahlt
    const PAYMENT_COMPLETELY_PAID = 12; //Komplett bezahlt
    const PAYMENT_OPEN = 17; //Offen
    const PAYMENT_CANCEL = 200; //Abgebrochen
    const PAYMENT_PARTIAL_REFUND = 201; //Teilweise erstattet
    const PAYMENT_FULL_REFUND = 202; //Komplett erstattet
    const PAYMENT_INVOICE_PAYED = 203; //Rechnung bezahlt

    const GENDER_MAPPING = array('mr' => 'm', 'ms' => 'f');
    const SHOP_ID_MAPPING = array(1 => 3, 9 => 3, 8 => 4, 7 => 2);
    //ch             // at     // de

    private $params;
    private $db;
    private $shopware;
    private $path;

    public function install()
    {
        $this->subscribeEvent(
            'Shopware_CronJob_ChangeOrderStatus_15',
            'onRun15'
        );

        $this->subscribeEvent(
            'Shopware_CronJob_ChangeOrderStatus_5',
            'onRun5'
        );

        return true;
    }

    public function init()
    {
        $this->db = Shopware()->Db();
        $this->shopware = Shopware();

        $this->params = array();

        $this->path = $this->Path() . '../../../../../../';
    }

    public function afterInit()
    {
        $this->get('Loader')->registerNamespace(
            'ShopwarePlugins\\CronChangeOrderStatus',
            $this->Path() . '/'
        );
    }

    public function onRun15()
    {
        //2a
        echo '2a start<br />';
        $data = array();
        $sql = 'SELECT
										 o.ordernumber,
										 o.Id as orderID, 
										 o.ordertime,
										 o.currency,
										 o.cleared,
										 o.dispatchID,
										 o.userID as customerId,
										 o.invoice_amount as invoiceAmount,
										 o.invoice_amount_net as invoiceAmount,
										 o.invoice_shipping as invoiceShipping,
										 o.status as orderStatusId,
										 o.net, 
										 o.language,
										 o.paynext,
										 oba.salutation as billingSalutation,
										 oba.firstname as billingFirstname,
										 oba.lastname as billingLastname,
										 oba.street as billingStreet,	
										 oba.zipcode as billingZipcode,
										 oba.city as billingCity,
										 oba.countryID as obaCID,
										 osa.salutation as shippingSalutation,
										 osa.street as shippingStreet,	
										 osa.zipcode as shippingZipcode,
										 osa.city as shippingCity,
										 osa.countryID as osaCID,
										 oa.attribute1,
										 u.email
							FROM ' . self::TABLE_ORDER . ' o
							INNER JOIn s_order_billingaddress oba ON (oba.orderID = o.Id)
							INNER JOIN s_order_shippingaddress osa ON (osa.orderID = o.Id)
							INNER JOIN s_user u ON (u.id = o.userId)
							INNER JOIN s_order_attributes oa ON (oa.orderID = o.Id)
							WHERE o.status = ' . self::STATUS_OPEN . ' AND o.ordernumber > 0
        		 ';

        $orders = $this->db->fetchAssoc($sql);

        foreach ($orders as $key => $order) {
            if (!isset($order['ordernumber'])) {
                continue;
            }

            $sqlItems = 'SELECT
												 od.articleordernumber,
												 od.price,
												 od.quantity,
												 od.tax_rate as taxRate, 
												 od.status as statusId,
												 od.modus,
												 a.name as articleName									 
									FROM ' . self::TABLE_DETAILS . ' od
									INNER JOIN s_articles a ON (a.id = od.articleID)  
									WHERE od.ordernumber = ' . $order['ordernumber'] . '
		        		 ';

            $items = $this->db->fetchAssoc($sqlItems);

            $date = substr($order['ordertime'], 0, 10);
            $time = substr($order['ordertime'], 11, 8);
            $data['number'] = $order['ordernumber'];
            $number = $order['ordernumber'];
            $id = $order['orderID'];

            $payment_id = $order['cleared'];

            $customerId = $order['customerId'];
            $customer_no = $this->getCustomerNumber($customerId);

            if ((!empty($this->params['payment_state']) &&
                    ($this->params['payment_state'] != $payment_id))
                && $payment_id != self::PAYMENT_STATUS_OPEN_INVOICE
            ) {
                continue;
            }

            if (empty($data[$id]['discount'])) {
                $data[$id]['discount'] = 0;
            }

            $dispatchId = $order['dispatchId'];

            $shipping_charges = $order['invoiceShipping'];
            $price_net = round($order['invoiceAmountNet'], 2);
            $price_gross = $order['invoiceAmount'];

            $tax_amount = $price_gross - $price_net - $shipping_charges;

            $data[$id]['id'] = $id;
            $data[$id]['number'] = $number;

            $data[$id]['type'] = 'order';

            $billing_gender = self::GENDER_MAPPING[$order['billingSalutation']];
            $shipping_gender = self::GENDER_MAPPING[$order['shippingSalutation']];

            $shop_id = self::SHOP_ID_MAPPING[$order['language']];

            $dispatch_arr = $this->getRecord(self::TABLE_PREMIUM_DISPATCH, $dispatchId);

            $carrier = $dispatch_arr['comment'];

            if (empty($carrier)) {
                $carrier = 'dhl';
            }

            $sql = 'SELECT countryiso FROM s_core_countries
									WHERE Id = ' . $order['obaCID'];
            $billingCountryIso = $this->db->fetchOne($sql);

            $data[$id]['head'] = array(
                'date' => $date,
                'time' => $time,
                'currency' => $order['currency'],
                'shipping_charges' => $shipping_charges,
                'total_net' => $price_net,
                'total_gross' => $price_gross,
                'tax' => $tax_amount,
                'carrier' => $carrier,
                'giftwrap' => '0',
                'shop_id' => $shop_id,
                'mail' => $order['email'],
                'lang' => strtolower($billingCountryIso),
                'customer_no' => $customer_no,
                'storno' => $order['attribute1']
            );

            $data[$id]['customer_billing_name'] = array(
                'gender' => $billing_gender,
                'firstname' => $order['billingFirstname'],
                'lastname' => $order['billingLastname'],
            );

            $data[$id]['customer_billing_address'] = array(
                'street' => $order['billingStreet'],
                'streetnr' => '',
                'zip' => $order['billingZipcode'],
                'city' => $order['billingCity'],
                'country' => $billingCountryIso
            );

            $data[$id]['customer_shipping_name'] = array(
                'gender' => $shipping_gender,
                'firstname' => $order['shippingFirstname'],
                'lastname' => $order['shippingLastname'],
            );

            $sql = 'SELECT countryiso FROM s_core_countries
									WHERE Id = ' . $order['osaCID'];
            $shippingCountryIso = $this->db->fetchOne($sql);

            $data[$id]['customer_shipping_address'] = array(
                'street' => $order['shippingStreet'],
                'streetnr' => '',
                'zip' => $order['shippingZipcode'],
                'city' => $order['shippingCity'],
                'country' => $shippingCountryIso,
            );

            $paynext = empty($order['paynext']) ? '14' : $order['paynext'];

            $data[$id]['payment'] = array(
                'type' => $paynext,
                'amount' => $order['invoiceAmount'],
            );

            foreach ($items as $item) {
                $mode = $item['modus'];

                if ($mode == 2 || $mode == 4) {
                    $data[$id]['discount'] += $order['price'];
                    continue;
                }

                $price = $item['price'];
                $tax = $item['taxRate'];

                $price_net = $price / (100 + $tax) * 100;

                $tax_amount = $price - $price_net;
                $tax_amount = round($tax_amount, 2);

                $data[$id]['items'][] = array(
                    'id' => $item['articleordernumber'],
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'total' => $price * $item['quantity'],
                    'discount' => '0',
                    'tax' => $tax_amount,
                    'message' => $item['articleName'],
                    'giftwrap' => '0',
                );
            }

            $data[$id]['pdf'] = '';
        }

        foreach ($data as $key => $xml) {
            if (empty($xml['head']) || empty($xml['id'])) {
                continue;
            }

            $xml_result = $this->makeXmlFromArray($xml);

            $this->setOrderStatus($xml['id'], self::STATUS_READY_FOR_DELIVERY);

            $datetime = date('Y-m-d', time()) . 'T' . date('H-i-s', time());

            $this->saveFile('S201_Bestellung_' .
                $datetime . '-' . $xml['number'] . '_ECOM_to_E2E.xml', $xml_result);
        }
        echo '2a success<br />';
        echo '2a end';
        //2a

        //2b
        $psth = $this->path;

        if (!file_exists($psth . self::INPUT_DIR) || !is_dir($psth . self::INPUT_DIR)) {
            die('>No such in directory: ' . $psth . self::INPUT_DIR);
        }

        if (!file_exists($psth . self::OUTPUT_DIR) || !is_dir($psth . self::OUTPUT_DIR)) {
            die('>No such out directory: ' . $psth . self::OUTPUT_DIR);
        }

        $file_arr = array_diff(scandir($psth . self::INPUT_DIR), array('.', '..'));

        foreach ($file_arr as $file) {
            $pos303 = strpos($file, 'S303');
            if ($pos303 === false) {
                continue;
            }

            $path_to_file = $psth . self::INPUT_DIR . '/' . $file;
            $path_to_archive = $psth . self::ARCHIVE_DIR . '/' . $file;

            rename($path_to_file, $path_to_archive);
        }
        //2b
    }

    public function onRun5()
    {
        //2c
        $sql = 'SELECT
										 o.Id as orderId
							FROM ' . self::TABLE_ORDER . ' o
							WHERE o.status = ' . self::STATUS_CANCELATION_RECEIVED . ' AND o.ordernumber > 0';

        $orders = $this->db->fetchAssoc($sql);
        foreach ($orders as $key => $order) {
            $sql = 'SELECT
												 COUNT(od.Id) as cnt
									FROM ' . self::TABLE_DETAILS . ' od
									WHERE od.orderId = ' . $order['orderId'] . ' AND od.status != ' . self::STATUS_CANCELED . '
		        		 ';
            $cnt = $this->db->fetchOne($sql);

            if ($cnt > 0) {
                $this->setOrderStatus($order['orderId'], self::STATUS_PARTIALLY_CANCELLED);
            } else {
                $this->setOrderStatus($order['orderId'], self::STATUS_PROCESS_CANCELATION);
            }
        }
        //2c

        //2d
        $data = array();
        $sql = 'SELECT
										 o.ordernumber,
										 o.Id as orderID,
										 o.ordertime,
										 o.currency,
										 o.cleared,
										 o.dispatchID,
										 o.userID as customerId,
										 o.invoice_amount as invoiceAmount,
										 o.invoice_amount_net as invoiceAmount,
										 o.invoice_shipping as invoiceShipping,
										 o.status as orderStatusId,
										 o.net,
										 o.language,
										 o.paynext,
										 oba.salutation as billingSalutation,
										 oba.firstname as billingFirstname,
										 oba.lastname as billingLastname,
										 oba.street as billingStreet,
										 oba.zipcode as billingZipcode,
										 oba.city as billingCity,
										 oba.countryID as obaCID,
										 osa.salutation as shippingSalutation,
										 osa.street as shippingStreet,
										 osa.zipcode as shippingZipcode,
										 osa.city as shippingCity,
										 osa.countryID as osaCID,
										 oa.attribute1,
										 u.email
							FROM ' . self::TABLE_ORDER . ' o
							INNER JOIn s_order_billingaddress oba ON (oba.orderID = o.Id)
							INNER JOIN s_order_shippingaddress osa ON (osa.orderID = o.Id)
							INNER JOIN s_user u ON (u.id = o.userId)
							INNER JOIN s_order_attributes oa ON (oa.orderID = o.Id)
							WHERE o.status = ' . self::STATUS_PROCESS_CANCELATION . ' AND o.ordernumber > 0
        		 ';

        $orders = $this->db->fetchAssoc($sql);

        foreach ($orders as $key => $order) {
            if (!isset($order['ordernumber'])) {
                continue;
            }

            $sqlItems = 'SELECT
												 od.articleordernumber,
												 od.price,
												 od.quantity,
												 od.tax_rate as taxRate,
												 od.status as statusId,
												 od.modus,
												 a.name as articleName
									FROM ' . self::TABLE_DETAILS . ' od
									INNER JOIN s_articles a ON (a.id = od.articleID)
									WHERE od.ordernumber = ' . $order['ordernumber'] . '
		        		 ';

            $items = $this->db->fetchAssoc($sqlItems);

            $date = substr($order['ordertime'], 0, 10);
            $time = substr($order['ordertime'], 11, 8);
            $data['number'] = $order['ordernumber'];
            $number = $order['ordernumber'];
            $id = $order['orderID'];

            $payment_id = $order['cleared'];

            $customerId = $order['customerId'];
            $customer_no = $this->getCustomerNumber($customerId);

            if ((!empty($this->params['payment_state']) &&
                    ($this->params['payment_state'] != $payment_id))
                && $payment_id != self::PAYMENT_STATUS_OPEN_INVOICE
            ) {
                continue;
            }

            if (empty($data[$id]['discount'])) {
                $data[$id]['discount'] = 0;
            }

            $dispatchId = $order['dispatchId'];

            $shipping_charges = $order['invoiceShipping'];
            $price_net = round($order['invoiceAmountNet'], 2);
            $price_gross = $order['invoiceAmount'];

            $tax_amount = $price_gross - $price_net - $shipping_charges;

            $data[$id]['id'] = $id;
            $data[$id]['number'] = $number;

            $data[$id]['type'] = 'order';

            $billing_gender = self::GENDER_MAPPING[$order['billingSalutation']];
            $shipping_gender = self::GENDER_MAPPING[$order['shippingSalutation']];

            $shop_id = self::SHOP_ID_MAPPING[$order['language']];

            $dispatch_arr = $this->getRecord(self::TABLE_PREMIUM_DISPATCH,
                $dispatchId);

            $carrier = $dispatch_arr['comment'];

            if (empty($carrier)) {
                $carrier = 'dhl';
            }

            $sql = 'SELECT countryiso FROM s_core_countries
									WHERE Id = ' . $order['obaCID'];
            $billingCountryIso = $this->db->fetchOne($sql);

            $data[$id]['head'] = array(
                'date' => $date,
                'time' => $time,
                'currency' => $order['currency'],
                'shipping_charges' => $shipping_charges,
                'total_net' => $price_net,
                'total_gross' => $price_gross,
                'tax' => $tax_amount,
                'carrier' => $carrier,
                'giftwrap' => '0',
                'shop_id' => $shop_id,
                'mail' => $order['email'],
                'lang' => strtolower($billingCountryIso),
                'customer_no' => $customer_no,
                'storno' => $order['attribute1']
            );

            $data[$id]['customer_billing_name'] = array(
                'gender' => $billing_gender,
                'firstname' => $order['billingFirstname'],
                'lastname' => $order['billingLastname'],
            );

            $data[$id]['customer_billing_address'] = array(
                'street' => $order['billingStreet'],
                'streetnr' => '',
                'zip' => $order['billingZipcode'],
                'city' => $order['billingCity'],
                'country' => $billingCountryIso
            );

            $data[$id]['customer_shipping_name'] = array(
                'gender' => $shipping_gender,
                'firstname' => $order['shippingFirstname'],
                'lastname' => $order['shippingLastname'],
            );

            $sql = 'SELECT countryiso FROM s_core_countries
									WHERE Id = ' . $order['osaCID'];
            $shippingCountryIso = $this->db->fetchOne($sql);

            $data[$id]['customer_shipping_address'] = array(
                'street' => $order['shippingStreet'],
                'streetnr' => '',
                'zip' => $order['shippingZipcode'],
                'city' => $order['shippingCity'],
                'country' => $shippingCountryIso,
            );

            $paynext = empty($order['paynext']) ? '14' : $order['paynext'];

            $data[$id]['payment'] = array(
                'type' => $paynext,
                'amount' => $order['invoiceAmount'],
            );

            foreach ($items as $item) {
                $mode = $item['modus'];

                if ($mode == 2 || $mode == 4) {
                    $data[$id]['discount'] += $order['price'];
                    continue;
                }

                $price = $item['price'];
                $tax = $item['taxRate'];

                $price_net = $price / (100 + $tax) * 100;

                $tax_amount = $price - $price_net;
                $tax_amount = round($tax_amount, 2);

                $data[$id]['items'][] = array(
                    'id' => $item['articleordernumber'],
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'total' => $price * $item['quantity'],
                    'discount' => '0',
                    'tax' => $tax_amount,
                    'message' => $item['articleName'],
                    'giftwrap' => '0',
                );
            }

            $data[$id]['pdf'] = '';
        }

        foreach ($data as $key => $xml) {
            if (empty($xml['head']) || empty($xml['id'])) {
                continue;
            }

            $xml_result = $this->makeXmlFromArray($xml);

            $this->setOrderStatus($xml['id'], self::STATUS_FULL_CANCELATION);

            $this->setPaymentStatus($xml['id'], self::PAYMENT_CANCEL);

            $datetime = date('Y-m-d', time()) . 'T' . date('H-i-s', time());

            $this->saveFile('S201_Bestellung_' .
                $datetime . '-' . $xml['number'] . '_ECOM_to_E2E.xml', $xml_result);
        }
        //2d

        //2e
        $psth = $this->path;

        if (!file_exists($psth . self::INPUT_DIR) || !is_dir($psth . self::INPUT_DIR)) {
            die('>No such in directory: ' . $psth . self::INPUT_DIR);
        }

        if (!file_exists($psth . self::OUTPUT_DIR) || !is_dir($psth . self::OUTPUT_DIR)) {
            die('>No such out directory: ' . $psth . self::OUTPUT_DIR);
        }

        $file_arr = array_diff(scandir($psth . self::INPUT_DIR), array('.', '..'));

        foreach ($file_arr as $file) {
            $pos305 = strpos($file, 'S305');
            if ($pos305 === false) {
                continue;
            }

            $path_to_file = $psth . self::INPUT_DIR . '/' . $file;
            $path_to_archive = $psth . self::ARCHIVE_DIR . '/' . $file;

            $xml_arr = $this->xmlFileToArray2e($path_to_file, $path_to_archive);
            if (count($xml_arr) < 1) {
                continue;
            }
        }
        //2e

        //2f
        $sql = 'SELECT
										 o.Id as orderId,
										 o.cleared,
										 o.status
							FROM ' . self::TABLE_ORDER . ' o
							WHERE (o.status = ' . self::STATUS_DELIVERY_PROCESSED . ' OR 
										 o.status = ' . self::STATUS_DELIVERY_PARTLY_PROCESSED . ') AND 
										o.ordernumber > 0';

        $orders = $this->db->fetchAssoc($sql);

        $generator = new DocumentGenerator($this->shopware->Models(), array(
            'rwmode' => 1
        ));

        foreach ($orders as $key => $order) {
            $orderModel = $this->shopware->Models()->find('Shopware\Models\Order\Order', $order['orderId']);

            $generator->generateDocument($orderModel);

            if ($order['cleared'] == self::PAYMENT_COMPLETELY_PAID) {
                if ($order['status'] == self::STATUS_DELIVERY_PARTLY_PROCESSED) {
                    $this->setPaymentStatus($order['orderId'], self::PAYMENT_PARTIALLY_PAID);
                }
                if ($order['status'] == self::STATUS_DELIVERY_PROCESSED) {
                    $this->setPaymentStatus($order['orderId'], self::PAYMENT_COMPLETELY_PAID);
                }
            }

            if ($order['cleared'] == self::PAYMENT_OPEN ||
                $order['cleared'] == self::PAYMENT_PARTIALLY_PAID
            ) {
                if ($order['status'] == self::STATUS_DELIVERY_PARTLY_PROCESSED) {
                    $this->setOrderStatus($order['orderId'], self::STATUS_PARTIALLY_INVOICE_CREATED);
                }
                if ($order['status'] == self::STATUS_DELIVERY_PROCESSED) {
                    $this->setOrderStatus($order['orderId'], self::STATUS_INVOICE_CREATED);
                }
            }
        }
        //2f

        //2g
        $data = array();
        $sql = 'SELECT
										 o.status as status,
										 o.ordernumber,
										 o.Id as orderID,
										 o.ordertime,
										 o.currency,
										 o.cleared,
										 o.dispatchID,
										 o.userID as customerId,
										 o.invoice_amount as invoiceAmount,
										 o.invoice_amount_net as invoiceAmount,
										 o.invoice_shipping as invoiceShipping,
										 o.status as orderStatusId,
										 o.net,
										 o.language,
										 o.paynext,
										 oba.salutation as billingSalutation,
										 oba.firstname as billingFirstname,
										 oba.lastname as billingLastname,
										 oba.street as billingStreet,
										 oba.zipcode as billingZipcode,
										 oba.city as billingCity,
										 oba.countryID as obaCID,
										 osa.salutation as shippingSalutation,
										 osa.street as shippingStreet,
										 osa.zipcode as shippingZipcode,
										 osa.city as shippingCity,
										 osa.countryID as osaCID,
										 oa.attribute1,
										 u.email
							FROM ' . self::TABLE_ORDER . ' o
							INNER JOIn s_order_billingaddress oba ON (oba.orderID = o.Id)
							INNER JOIN s_order_shippingaddress osa ON (osa.orderID = o.Id)
							INNER JOIN s_user u ON (u.id = o.userId)
							INNER JOIN s_order_attributes oa ON (oa.orderID = o.Id)
							WHERE (o.status = ' . self::STATUS_PARTIALLY_INVOICE_CREATED . ' OR o.status = ' . self::STATUS_INVOICE_CREATED . ') AND o.ordernumber > 0
        		 ';

        $orders = $this->db->fetchAssoc($sql);

        foreach ($orders as $key => $order) {
            if (!isset($order['ordernumber'])) {
                continue;
            }

            $sqlItems = 'SELECT
												 od.articleordernumber,
												 od.price,
												 od.quantity,
												 od.tax_rate as taxRate,
												 od.status as statusId,
												 od.modus,
												 a.name as articleName
									FROM ' . self::TABLE_DETAILS . ' od
									INNER JOIN s_articles a ON (a.id = od.articleID)
									WHERE od.ordernumber = ' . $order['ordernumber'] . '
		        		 ';

            $items = $this->db->fetchAssoc($sqlItems);

            $date = substr($order['ordertime'], 0, 10);
            $time = substr($order['ordertime'], 11, 8);
            $data['number'] = $order['ordernumber'];
            $number = $order['ordernumber'];
            $id = $order['orderID'];
            $data[$id]['status'] = $order['status'];

            $payment_id = $order['cleared'];

            $customerId = $order['customerId'];
            $customer_no = $this->getCustomerNumber($customerId);

            if ((!empty($this->params['payment_state']) &&
                    ($this->params['payment_state'] != $payment_id))
                && $payment_id != self::PAYMENT_STATUS_OPEN_INVOICE
            ) {
                continue;
            }

            if (empty($data[$id]['discount'])) {
                $data[$id]['discount'] = 0;
            }

            $dispatchId = $order['dispatchId'];

            $shipping_charges = $order['invoiceShipping'];
            $price_net = round($order['invoiceAmountNet'], 2);
            $price_gross = $order['invoiceAmount'];

            $tax_amount = $price_gross - $price_net - $shipping_charges;

            $data[$id]['id'] = $id;
            $data[$id]['number'] = $number;

            $data[$id]['type'] = 'order';

            $billing_gender = self::GENDER_MAPPING[$order['billingSalutation']];
            $shipping_gender = self::GENDER_MAPPING[$order['shippingSalutation']];

            $shop_id = self::SHOP_ID_MAPPING[$order['language']];

            $dispatch_arr = $this->getRecord(self::TABLE_PREMIUM_DISPATCH,
                $dispatchId);

            $carrier = $dispatch_arr['comment'];

            if (empty($carrier)) {
                $carrier = 'dhl';
            }

            $sql = 'SELECT countryiso FROM s_core_countries
									WHERE Id = ' . $order['obaCID'];
            $billingCountryIso = $this->db->fetchOne($sql);

            $data[$id]['head'] = array(
                'date' => $date,
                'time' => $time,
                'currency' => $order['currency'],
                'shipping_charges' => $shipping_charges,
                'total_net' => $price_net,
                'total_gross' => $price_gross,
                'tax' => $tax_amount,
                'carrier' => $carrier,
                'giftwrap' => '0',
                'shop_id' => $shop_id,
                'mail' => $order['email'],
                'lang' => strtolower($billingCountryIso),
                'customer_no' => $customer_no,
                'storno' => $order['attribute1']
            );

            $data[$id]['customer_billing_name'] = array(
                'gender' => $billing_gender,
                'firstname' => $order['billingFirstname'],
                'lastname' => $order['billingLastname'],
            );

            $data[$id]['customer_billing_address'] = array(
                'street' => $order['billingStreet'],
                'streetnr' => '',
                'zip' => $order['billingZipcode'],
                'city' => $order['billingCity'],
                'country' => $billingCountryIso
            );

            $data[$id]['customer_shipping_name'] = array(
                'gender' => $shipping_gender,
                'firstname' => $order['shippingFirstname'],
                'lastname' => $order['shippingLastname'],
            );

            $sql = 'SELECT countryiso FROM s_core_countries
									WHERE Id = ' . $order['osaCID'];
            $shippingCountryIso = $this->db->fetchOne($sql);

            $data[$id]['customer_shipping_address'] = array(
                'street' => $order['shippingStreet'],
                'streetnr' => '',
                'zip' => $order['shippingZipcode'],
                'city' => $order['shippingCity'],
                'country' => $shippingCountryIso,
            );

            $paynext = empty($order['paynext']) ? '14' : $order['paynext'];

            $data[$id]['payment'] = array(
                'type' => $paynext,
                'amount' => $order['invoiceAmount'],
            );

            foreach ($items as $item) {
                $mode = $item['modus'];

                if ($mode == 2 || $mode == 4) {
                    $data[$id]['discount'] += $order['price'];
                    continue;
                }

                $price = $item['price'];
                $tax = $item['taxRate'];

                $price_net = $price / (100 + $tax) * 100;

                $tax_amount = $price - $price_net;
                $tax_amount = round($tax_amount, 2);

                $data[$id]['items'][] = array(
                    'id' => $item['articleordernumber'],
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'total' => $price * $item['quantity'],
                    'discount' => '0',
                    'tax' => $tax_amount,
                    'message' => $item['articleName'],
                    'giftwrap' => '0',
                );
            }

            $data[$id]['pdf'] = $this->getInvoicePdf($id);
        }

        foreach ($data as $key => $xml) {
            if (empty($xml['head']) || empty($xml['id'])) {
                continue;
            }

            $xml_result = $this->makeXmlFromArray($xml);
            $xml_result_s203 = $this->makeXmlFromArray($xml, true);

            if ($xml['status'] == self::STATUS_INVOICE_CREATED) {
                $this->setOrderStatus($xml['id'], self::STATUS_COMPLETELY_DELIVERED);
            }

            if ($xml['status'] == self::STATUS_PARTIALLY_INVOICE_CREATED) {
                $this->setOrderStatus($xml['id'], self::STATUS_PARTIALLY_DELIVERED);
            }

            $datetime = date('Y-m-d', time()) . 'T' . date('H-i-s', time());

            $this->saveFile('S201_Bestellung_' .
                $datetime . '-' . $xml['number'] . '_ECOM_to_E2E.xml', $xml_result);

            $this->saveFile('S203_Bestellung_' .
                $datetime . '-' . $xml['number'] . '_ECOM_to_E2E.xml', $xml_result_s203);
        }
        //2g
    }

    private function makeXmlFromArray($data_arr, $s203 = false)
    {
        $xml_result = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><mn:entity_container></mn:entity_container>',
            LIBXML_NOERROR, false, 'mn', true);
        $xml_result->addAttribute('xmlns:xmlns:mn', 'http://twt.de/main');
        $xml_result->addAttribute('xmlns:xmlns:od', 'http://twt.de/order');
        $xml_result->addAttribute('xmlns:xmlns:cu', 'http://twt.de/customer');
        $xml_result->addAttribute('xmlns:xmlns:cmn', 'http://twt.de/common');
        $xml_result->addAttribute('xmlns:xmlns:pd', 'http://twt.de/product');
        $xml_result->addAttribute('xmlns:xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml_result->addAttribute('xsi:xsi:schemaLocation', 'http://twt.de/main main.xsd');

        $entity_header = $xml_result->addChild("mn:mn:entity_header");

        $entity_header->addChild('mn:mn:date', $data_arr['head']['date']);
        $entity_header->addChild('mn:mn:time', $data_arr['head']['time']);

        $order = $xml_result->addChild('mn:mn:order', null);
        $order->addAttribute('id', $data_arr['number']);

        $type = 'order';

        if (!empty($data_arr['head']['storno']) &&
            $data_arr['head']['storno'] == 1
        ) {
            $type = 'change';
        }

        $order->addChild('od:od:type', $type);

        $head = $order->addChild('od:od:head');

        $head->addChild('od:od:date', $data_arr['head']['date']);
        $head->addChild('od:od:currency', $data_arr['head']['currency']);
        $head->addChild('od:od:shipping_charges', $data_arr['head']['shipping_charges']);
        $typenet = $head->addChild('od:od:total', $data_arr['head']['total_net']);
        $typenet->addAttribute('type', 'net');
        $typegross = $head->addChild('od:od:total', $data_arr['head']['total_gross']);
        $typegross->addAttribute('type', 'gross');


        $discount = $head->addChild('od:od:discount', round(abs($data_arr['discount']), 2));
        $discount->addAttribute('type', 'abs');
        $head->addChild('od:od:tax', $data_arr['head']['tax']);
        $head->addChild('od:od:carrier', $data_arr['head']['carrier']);
        $head->addChild('od:od:giftwrap', $data_arr['head']['giftwrap']);

        $customer = $order->addChild('od:od:customer');
        $webshop = $customer->addChild('cu:cu:webshop');

        $webshop->addChild('cu:cu:id', $data_arr['head']['customer_no']);
        $webshop->addChild('cu:cu:shopid', $data_arr['head']['shop_id']);
        $data = $customer->addChild('cu:cu:data');

        $name = $data->addChild('cu:cu:name');

        $name->addChild('cu:cu:gender', $data_arr['customer_billing_name']['gender']);
        $name->addChild('cu:cu:firstname', $data_arr['customer_billing_name']['firstname']);
        $name->addChild('cu:cu:lastname', $data_arr['customer_billing_name']['lastname']);

        $data->addChild('cu:cu:mail', $data_arr['head']['mail']);

        $address = $data->addChild('cu:cu:address');

        foreach ($data_arr['customer_billing_address'] as $key => $val) {
            $address->addChild('cu:cu:' . $key, $val);
        }

        $data->addChild('cu:cu:lang', $data_arr['head']['lang']);

        $payment = $order->addChild('od:od:payment');

        foreach ($data_arr['payment'] as $key => $val) {
            $payment->addChild('od:od:' . $key, $val);
        }

        // shipping

        if ($data_arr['customer_billing_address']['street'] !=
            $data_arr['customer_shipping_address']['street'] ||
            $data_arr['customer_billing_address']['city'] !=
            $data_arr['customer_shipping_address']['city']
        ) {
            $shipping_address = $order->addChild('od:od:address');

            $shipping_name = $shipping_address->addChild('cu:cu:name');

            $shipping_name->addChild('cu:cu:gender', $data_arr['customer_shipping_name']['gender']);
            $shipping_name->addChild('cu:cu:firstname', $data_arr['customer_shipping_name']['firstname']);
            $shipping_name->addChild('cu:cu:lastname', $data_arr['customer_shipping_name']['lastname']);

            $shipping_address2 = $shipping_address->addChild('cu:cu:address');

            foreach ($data_arr['customer_shipping_address'] as $key => $val) {
                $shipping_address2->addChild('cu:cu:' . $key, $val);
            }
        }

        foreach ($data_arr['items'] as $key => $item_arr) {
            $item = $order->addChild('od:od:item');
            $item->addAttribute('number', $key + 1);

            foreach ($item_arr as $sunkey => $val) {
                $subitem = $item->addChild('od:od:' . $sunkey, $val);

                if (in_array($sunkey, ['discount', 'tax'])) {
                    $subitem->addAttribute('type', 'abs');
                }
            }
        }

        $invoice = $order->addChild('od:od:invoice', ($s203 ? $data_arr['pdf'] : ''));

        if ($s203) {
            $invoice->addAttribute('mime-type', 'application/pdf');
            $query = 'SELECT n.number FROM s_order_number n WHERE n.desc = "Rechnungen"';
            $s203_id = $this->db->fetchOne($query);
            $invoice->addAttribute('id', $s203_id);
        }

        $message = $order->addChild('od:od:message');
        $message->addAttribute('key', 'overall-message');

        $dom = new DOMDocument("1.0");
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml_result->asXML());

        return $dom->saveXML();
    }

    private function xmlFileToArray2e($path_to_file, $path_to_archive)
    {
        $filename = $path_to_file;
        $feed = file_get_contents($filename, true);
        $xml = new SimpleXmlElement($feed);
        $mn = $xml->children('mn', true);

        foreach ($mn->order_event as $order) {
            $order_no = $order->children('od', true)->id->__toString();
            if (!isset($order_no)) {
                continue;
            }

            $sql = 'SELECT o.id, o.status FROM s_order o
								WHERE o.ordernumber = ' . $order_no;
            $order_sql = $this->db->fetchAll($sql);

            foreach ($order_sql as $order) {
                if ($order['status'] == self::STATUS_PARTIALLY_CANCELLED) {
                    $this->setOrderStatus($order['id'], self::STATUS_DELIVERY_PARTLY_PROCESSED);
                    rename($path_to_file, $path_to_archive);
                }

                if ($order['status'] == self::STATUS_READY_FOR_DELIVERY) {
                    $this->setOrderStatus($order['id'], self::STATUS_DELIVERY_PROCESSED);
                    rename($path_to_file, $path_to_archive);
                }
            }
        }

        return $data;
    }

    private function getRecord($table, $id)
    {
        $query = 'SELECT * FROM ' . $table .
            ' WHERE id ="' . $id . '"';

        return $this->db->fetchRow($query);
    }

    private function getCustomerNumber($customer_id)
    {
        $query = 'SELECT customernumber FROM ' . self::TABLE_CUSTOMER .
            ' WHERE userID="' . $customer_id . '"';

        return $this->db->fetchOne($query);
    }

    private function setOrderStatus($order_id, $status_id)
    {
        $this->shopware->Models()->clear();

        $orderModel = $this->shopware->Models()->find('Shopware\Models\Order\Order', $order_id);
        $statusModel = $this->shopware->Models()->find('Shopware\Models\Order\Status', $status_id);
        $orderModel->setOrderStatus($statusModel);

        $this->shopware->Models()->flush();
    }

    private function setPaymentStatus($order_id, $status_id)
    {
        $this->shopware->Models()->clear();

        $orderModel = $this->shopware->Models()->find('Shopware\Models\Order\Order', $order_id);
        $statusModel = $this->shopware->Models()->find('Shopware\Models\Order\Status', $status_id);
        $orderModel->setPaymentStatus($statusModel);

        $this->shopware->Models()->flush();
    }

    private function saveFile($filename, $data)
    {
        $file_path = $this->path . self::OUTPUT_DIR . '/' . $filename;
        $result = file_put_contents($file_path, $data);

        if ($result !== false) {
            echo '>File has been saved: ' . $filename;
        } else {
            echo '>File not saved';
        }
        return;
    }

    private function getInvoicePdf($order_id)
    {
        $query = 'SELECT hash FROM ' . self::TABLE_ORDER_DOCUMENTS .
            ' WHERE orderID=' . $order_id . ' AND type=1';

        $hash = $this->db->fetchOne($query);

        if ($hash == false) {
            return '';
        }

        $name = $hash . '.pdf';
        $file = $this->shopware->DocPath('files/documents') . $name;

        if (!file_exists($file)) {
            return '';
        }

        $content = file_get_contents($file);

        return base64_encode($content);
    }
}
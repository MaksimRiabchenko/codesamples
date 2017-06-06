<?php

namespace ShopwarePlugins\CronChangeOrderStatus\Components;

use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Components\Model\ModelManager;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Models\Order\Order;

class DocumentGenerator
{
    private $em;
    private $options;
    private $output;

    /**
     * DocumentGenerator constructor.
     * @param ModelManager $em
     * @param array $options
     * @param OutputInterface|null $output
     */
    public function __construct(ModelManager $em, $options = array(), OutputInterface $output = null)
    {
        $this->em = $em;
        $this->options = $options;
        $this->output = $output;
    }

    /**
     * @return ModelManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * @return null|OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Process orders
     * @param ArrayCollection $orderNumbers
     */
    public function generateDocuments(ArrayCollection $orderNumbers)
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
        $builder->select(array(
            'orders',
            'customer',
            'payment',
            'billing',
            'billingCountry',
            'billingState',
            'shop',
            'dispatch',
            'paymentStatus',
            'orderStatus',
            'billingAttribute',
            'attribute'
        ));

        $builder->from('Shopware\Models\Order\Order', 'orders');
        $builder->leftJoin('orders.payment', 'payment')
            ->leftJoin('orders.paymentStatus', 'paymentStatus')
            ->leftJoin('orders.orderStatus', 'orderStatus')
            ->leftJoin('orders.billing', 'billing')
            ->leftJoin('orders.customer', 'customer')
            ->leftJoin('billing.country', 'billingCountry')
            ->leftJoin('billing.state', 'billingState')
            ->leftJoin('orders.shop', 'shop')
            ->leftJoin('orders.dispatch', 'dispatch')
            ->leftJoin('billing.attribute', 'billingAttribute')
            ->leftJoin('orders.attribute', 'attribute');
        $builder->where($builder->expr()->in('orders.number', $orderNumbers->getValues()));

        $query = $builder->getQuery();

        $orders = $query->getResult();

        foreach ($orders as $order) {
            $this->generateDocument($order);
        }
    }

    public function generateDocument(Order $order)
    {
        $options = $this->getOptions();

        //var_dump($order);die();

        $documentType = isset($options['docType']) ? $options['docType'] : 1;
        $documentMode = isset($options['rwmode']) ? (!($options['rwmode'])) : 1;

        $this->createOrderDocuments($documentType, $documentMode, $order);
    }

    /**
     * Internal helper function which checks if the batch process needs a document creation.
     * @param $documentType
     * @param $documentMode
     * @param \Shopware\Models\Order\Order $order
     */
    private function createOrderDocuments($documentType, $documentMode, $order)
    {
        if (!empty($documentType)) {
            $documents = $order->getDocuments();

            //create only not existing documents
            if ($documentMode == 1) {
                $alreadyCreated = false;
                foreach ($documents as $document) {

                    if ($document->getTypeId() == $documentType) {
                        $alreadyCreated = true;
                        break;
                    }
                }
                if ($alreadyCreated === false) {
                    $this->createDocument($order, $documentType);
                }
            } else {
                $this->createDocument($order, $documentType);
            }
        }
    }

    /**
     * Internal helper function which is used from the batch function and the createDocumentAction.
     * The batch function fired from the batch window to create multiple documents for many orders.
     * The createDocumentAction fired from the detail page when the user clicks the "create Document button"
     * @param Order $order
     * @param $documentType
     * @return bool
     */
    private function createDocument(Order $order, $documentType)
    {
        $renderer = "pdf"; // html / pdf

        $deliveryDate = new \DateTime();
        $deliveryDate = $deliveryDate->format('d.m.Y');

        $displayDate = new \DateTime();
        $displayDate = $displayDate->format('d.m.Y');

        $document = DocumentCLI::initDocument(
            $order->getId(),
            $documentType,
            array(
                'netto' => (bool)$order->getTaxFree(),
                'bid' => null,
                'voucher' => null,
                'date' => $displayDate,
                'delivery_date' => $deliveryDate,
                'shippingCostsAsPosition' => (int)$documentType !== 2,
                '_renderer' => $renderer,
                '_preview' => false,
                '_previewForcePagebreak' => null,
                '_previewSample' => null,
                'docComment' => null,
                'forceTaxCheck' => false
            )
        );

        $document->render();

        if ($output = $this->getOutput()) {
            $output->writeln('<info>' . sprintf("Processing order number %s", $order->getNumber()) . '</info>');
        }

        return true;
    }
}
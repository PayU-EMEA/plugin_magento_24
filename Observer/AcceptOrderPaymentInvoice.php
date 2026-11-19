<?php

namespace PayU\PaymentGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Framework\Phrase;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\ResourceModel\Order\Invoice as InvoiceResource;
use Magento\SalesSequence\Model\Manager as SequenceManager;

/**
 * Class DataAssignObserver
 * @package PayU\PaymentGateway\Observer
 */
class AcceptOrderPaymentInvoice implements ObserverInterface
{
    /**
     * @var InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var InvoiceResource
     */
    private $invoiceResource;

    /**
     * @var SequenceManager
     */
    private $sequenceManager;

    /**
     * AcceptOrderPaymentInvoice constructor.
     *
     * @param InvoiceSender $invoiceSender
     * @param InvoiceResource $invoiceResource
     * @param SequenceManager $sequenceManager
     */
    public function __construct(
        InvoiceSender $invoiceSender,
        InvoiceResource $invoiceResource,
        SequenceManager $sequenceManager
    ) {
        $this->invoiceSender = $invoiceSender;
        $this->invoiceResource = $invoiceResource;
        $this->sequenceManager = $sequenceManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $observer->getData('payment');
        /** @var Order $order */
        $order = $observer->getData('order');
        $invoice = $payment->getCreatedInvoice();
        if ($invoice instanceof Invoice) {
            $this->assignIncrementId($invoice);
            $this->addInvoiceComment($invoice);
            $order->addStatusHistoryComment($this->getInvoiceComment($invoice))->setIsCustomerNotified(
                true
            );
        }
    }

    /**
     * Reserve the invoice number, the invoice itself is saved together with the order
     *
     * @param Invoice $invoice
     *
     * @return void
     */
    private function assignIncrementId(Invoice $invoice)
    {
        if ($invoice->getEntityId() == null && $invoice->getIncrementId() == null) {
            $store = $invoice->getStore();
            $storeId = $store->getId();
            if ($storeId === null) {
                $storeId = $store->getGroup()->getDefaultStoreId();
            }
            $invoice->setIncrementId(
                $this->sequenceManager->getSequence($invoice->getEntityType(), $storeId)->getNextValue()
            );
        }
    }

    /**
     * Add invoice comment and send email to customer once the order is saved
     *
     * @param Invoice $invoice
     *
     * @return void
     */
    private function addInvoiceComment(Invoice $invoice)
    {
        $invoice->addComment($this->getInvoiceComment($invoice), true, true);
        $invoice->setEmailSent(true);
        $this->invoiceResource->addCommitCallback(function () use ($invoice) {
            // stays registered when the notification fails before the order save opens a transaction
            if ($invoice->getId() !== null) {
                $this->invoiceSender->send($invoice);
            }
        });
    }

    /**
     * Get translate for invoice comment
     *
     * @param Invoice $invoice
     *
     * @return Phrase
     */
    private function getInvoiceComment(Invoice $invoice)
    {
        return __('Notified customer about invoice %1.', $invoice->getIncrementId());
    }
}

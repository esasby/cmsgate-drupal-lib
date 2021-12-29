<?php

namespace esas\cmsgate\wrappers;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\PaymentStorageInterface;
use esas\cmsgate\CmsConnectorDrupal;
use esas\cmsgate\OrderStatus;
use esas\cmsgate\Registry;
use Throwable;

class OrderWrapperDrupal extends OrderSafeWrapper
{
    /**
     * @var \Drupal\commerce_order\Entity\OrderInterface
     */
    protected $order;
    protected $address;
    protected $products;

    /** @var PaymentInterface $lastPayment */
    protected $lastPayment = null;

    /**
     * @param $order
     */
    public function __construct(OrderInterface $order)
    {
        parent::__construct();
        $this->order = $order;
        $this->address = $this->order->getBillingProfile()->get('address')->first();

        // now we are trying to detect last payment linked to this order
        /** @var PaymentStorageInterface $paymentStorage */
        $paymentStorage = \Drupal::entityTypeManager()->getStorage('commerce_payment');
        $payments = $paymentStorage->loadMultipleByOrder($this->order);
        foreach ($payments as $payment) {
            if ($payment->getPaymentGatewayId() == Registry::getRegistry()->getModuleDescriptor()->getModuleMachineName()) //todo check
            {
                if ($this->lastPayment == null || $this->lastPayment->getOriginalId() < $payment->getOriginalId()) // payments has no created_at field
                    $this->lastPayment = $payment;
            }
        }
    }

    /**
     * Уникальный номер заказ в рамках CMS
     * @return string
     */
    public function getOrderIdUnsafe()
    {
        return $this->order->getOriginalId();
    }

    public function getOrderNumberUnsafe()
    {
        return $this->order->getOrderNumber();
    }

    /**
     * Полное имя покупателя
     * @return string
     */
    public function getFullNameUnsafe()
    {
        return $this->address->getGivenName() . " " . $this->address->getFamilyName();
    }

    /**
     * Мобильный номер покупателя для sms-оповещения
     * (если включено администратором)
     * @return string
     */
    public function getMobilePhoneUnsafe()
    {
        $phoneFieldMachineName = CmsConnectorDrupal::getInstance()->getTelephoneFieldName();
        if ($phoneFieldMachineName != null && $phoneFieldMachineName !== '')
            return $this->order->getBillingProfile()->$phoneFieldMachineName->first()->value;
    }

    /**
     * Email покупателя для email-оповещения
     * (если включено администратором)
     * @return string
     */
    public function getEmailUnsafe()
    {
        return $this->order->getCustomer()->getEmail();
    }

    /**
     * Физический адрес покупателя
     * @return string
     */
    public function getAddressUnsafe()
    {
        if ($this->address != null)
            return $this->address->getAddressLine1() . ", " . $this->address->getLocality() . ", " . $this->address->getCountryCode();
        else
            return "";
    }

    /**
     * Общая сумма товаров в заказе
     * @return string
     */
    public function getAmountUnsafe()
    {
        return $this->order->getTotalPrice()->getNumber(); //$payment->getAmount()->getNumber();
    }


    public function getShippingAmountUnsafe()
    {
        return 0; //todo
    }

    /**
     * Валюта заказа (буквенный код)
     * @return string
     */
    public function getCurrencyUnsafe()
    {
        return $this->order->getTotalPrice()->getCurrencyCode();
    }

    /**
     * Массив товаров в заказе
     * @return \esas\cmsgate\wrappers\OrderProductWrapperDrupal[]
     */
    public function getProductsUnsafe()
    {
        if ($this->products != null)
            return $this->products;
        foreach ($this->order->getItems() as $basketItem)
            $this->products[] = new OrderProductWrapperDrupal($basketItem);
        return $this->products;
    }

    /**
     * Текущий статус заказа в CMS
     * @return mixed
     */
    public function getStatusUnsafe()
    {
        return new OrderStatus(
            $this->order->getState()->getName(),
            $this->lastPayment->getState()->getName());
    }

    /**
     * Обновляет статус заказа в БД
     * @param OrderStatus $newOrderStatus
     * @return mixed
     */
    public function updateStatus($newOrderStatus)
    {
        $this->lastPayment->setState($newOrderStatus->getPaymentStatus());
        $this->lastPayment->save();
        $this->order->getState()->applyTransitionById($newOrderStatus->getOrderStatus());
    }

    /**
     * Идентификатор клиента
     * @return string
     */
    public function getClientIdUnsafe()
    {
        return $this->order->getCustomerId();
    }

    /**
     * BillId (идентификатор хуткигрош) успешно выставленного счета
     * @return mixed
     */
    public function getExtIdUnsafe()
    {
        return $this->lastPayment != null ? $this->lastPayment->getRemoteId() : null;
    }

    /**
     * Сохраняет привязку внешнего идентификтора к заказу
     * @param $extId
     */
    public function saveExtId($extId)
    {
        $this->lastPayment->setRemoteId($extId);
        $this->lastPayment->save();
    }
}
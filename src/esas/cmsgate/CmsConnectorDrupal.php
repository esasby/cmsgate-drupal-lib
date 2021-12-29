<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 13.04.2020
 * Time: 12:23
 */

namespace esas\cmsgate;


use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\PaymentStorageInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Entity\EntityFieldManager;
use esas\cmsgate\descriptors\CmsConnectorDescriptor;
use esas\cmsgate\descriptors\VendorDescriptor;
use esas\cmsgate\descriptors\VersionDescriptor;
use esas\cmsgate\lang\LocaleLoaderDrupal;
use esas\cmsgate\wrappers\OrderWrapper;
use esas\cmsgate\wrappers\OrderWrapperDrupal;

class CmsConnectorDrupal extends CmsConnector
{
    /**
     * Для удобства работы в IDE и подсветки синтаксиса.
     * @return $this
     */
    public static function getInstance()
    {
        return Registry::getRegistry()->getCmsConnector();
    }


    public function createCommonConfigForm($managedFields)
    {
        return null; //not implemented
    }

    public function createSystemSettingsWrapper()
    {
        return null; // not implemented
    }

    /**
     * По локальному id заказа возвращает wrapper
     * @param $orderId
     * @return OrderWrapper
     */
    public function createOrderWrapperByOrderId($orderId)
    {
        /** @var \Drupal\commerce_order\Entity\Order $drupalOrder */
        $drupalOrder = \Drupal\commerce_order\Entity\Order::load($orderId);
        return new OrderWrapperDrupal($drupalOrder);
    }

    public function createOrderWrapperForCurrentUser()
    {
        /* @var CurrentStoreInterface $store */
        $store = \Drupal::service('commerce_store.current_store');
        /* @var CartProviderInterface $cpi */
        $cpi = \Drupal::service('commerce_cart.cart_provider');
        $cart = $cpi->getCart('default', $store->getStore());
        return new OrderWrapperDrupal($cart);
    }

    public function createOrderWrapperByOrderNumber($orderNumber)
    {
        /** @var OrderInterface[] $orders */
        $orders = \Drupal::entityTypeManager()->getStorage('commerce_order')->loadByProperties(['order_number' => $orderNumber]);
        if ($orders != null && count($orders) == 1) {
            return new OrderWrapperDrupal($orders[0]);
        }
        return null;
    }

    public function createOrderWrapperByExtId($extId)
    {
        /** @var PaymentStorageInterface $paymentStorage */
        $paymentStorage = \Drupal::entityTypeManager()->getStorage('commerce_payment');
        /** @var PaymentInterface[] $payments */
        $payments = $paymentStorage->loadByRemoteId($extId);
        if ($payments != null && count($payments) == 1) {
            return new OrderWrapperDrupal($payments[0]->getOrder());
        }
        return null;
    }

    public function createConfigStorage()
    {
        return new ConfigStorageDrupal();
    }

    public function createLocaleLoader()
    {
        return new LocaleLoaderDrupal();
    }

    public function createCmsConnectorDescriptor()
    {
        return new CmsConnectorDescriptor(
            "cmsgate-drupal-lib",
            new VersionDescriptor(
                "v1.15.0",
                "2021-12-29"
            ),
            "Cmsgate Drupal connector",
            "https://bitbucket.esas.by/projects/CG/repos/cmsgate-drupal-lib/browse",
            VendorDescriptor::esas(),
            "drupal"
        );
    }

    /**
     * @return \Drupal\Core\Field\FieldDefinitionInterface[]
     */
    public function getTelephoneFields() {
        /** @var EntityFieldManager $efm */
        $efm = \Drupal::service('entity_field.manager');
        $fields = $efm->getFieldDefinitions('user', 'user');
        $ret = array();
        foreach ($fields as $field) {
            if ($field->getType() == 'telephone')
                $ret[] = $field;
        }
        return $ret;
    }

    public function getTelephoneFieldName() {
        $fields = $this->getTelephoneFields();
        switch (count($fields)) {
            case 0:
                return '';
            case 1:
                return $fields[0]->getName();
            default:
                return Registry::getRegistry()->getConfigWrapper()->get(ConfigFieldsDrupal::phoneFieldName());
        }
    }

    public function getConstantConfigValue($key) {
        switch ($key) {
            case ConfigFields::orderPaymentStatusPending():
                return "cmsgate_pending";
            case ConfigFields::orderPaymentStatusPayed():
                return "cmsgate_payed";
            case ConfigFields::orderPaymentStatusFailed():
                return "cmsgate_failed";
            case ConfigFields::orderPaymentStatusCanceled():
                return "cmsgate_canceled";
            default:
                return null;
        }
    }
}
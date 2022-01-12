<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 13.04.2020
 * Time: 12:23
 */

namespace esas\cmsgate;


use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\PaymentStorageInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
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
        /** @var EntityTypeManager $entityTypeManager */
        $entityTypeManager = \Drupal::getContainer()->get('entity_type.manager');
        $orderStorage = $entityTypeManager->getStorage('commerce_order');

        $query = $orderStorage->getQuery()
            ->condition('uid', \Drupal::currentUser()->id())
            ->sort('order_id', 'DESC')
            ->range(0,1)
            ->accessCheck(FALSE);
        $orderIds = $query->execute();
        return $this->createOrderWrapperByOrderId(reset($orderIds));

//        /* @var CurrentStoreInterface $store */
//        $store = \Drupal::service('commerce_store.current_store');
//        /* @var CartProviderInterface $cpi */
//        $cpi = \Drupal::service('commerce_cart.cart_provider');
//        $cart = $cpi->getCart('default', $store->getStore());
//        return new OrderWrapperDrupal($cart);
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
                "v1.15.2",
                "2022-01-12"
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
    public function getTelephoneFields()
    {
        /** @var EntityFieldManager $efm */
        $efm = \Drupal::service('entity_field.manager');
        $fields = $efm->getFieldDefinitions('profile', 'customer');
        $ret = array();
        foreach ($fields as $field) {
            if ($field->getType() == 'telephone')
                $ret[] = $field;
        }
        return $ret;
    }

    public function getTelephoneFieldName()
    {
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

    /**
     * @return \Drupal\state_machine\Plugin\Workflow\WorkflowInterface[]
     * @throws \Drupal\Core\TypedData\Exception\MissingDataException
     */
    public function getWorkflows()
    {
        // Only the StateItem knows which workflow it's using. This requires us
        // to create an entity for each bundle in order to get the state field.
        $entity_type_id = 'commerce_order';
        $entityTypeManager = \Drupal::getContainer()->get('entity_type.manager');
        $entityFieldManager = \Drupal::getContainer()->get('entity_field.manager');
        $entity_type = $entityTypeManager->getDefinition($entity_type_id);
        $field_name = 'state';

        $storage = $entityTypeManager->getStorage($entity_type->id());
        $map = $entityFieldManager->getFieldMap();
        $bundles = $map[$entity_type->id()][$field_name]['bundles'];
        $workflows = [];
        foreach ($bundles as $bundle) {
            $values = [];
            if ($bundle_key = $entity_type->getKey('bundle')) {
                $values[$bundle_key] = $bundle;
            }
            /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
            $entity = $storage->create($values);
            if ($entity->hasField($field_name)) {
                $workflow = $entity->get($field_name)->first()->getWorkflow();
                $workflows[$workflow->getId()] = $workflow;
            }
        }
        return $workflows;
    }

    /**
     * @return \Drupal\commerce_order\Entity\OrderInterface
     */
    public function getDrupalOrderFromSession() {
        if ($GLOBALS["request"]->attributes->get("commerce_order") != null)
            return $GLOBALS["request"]->attributes->get("commerce_order");
        return null;
    }
}
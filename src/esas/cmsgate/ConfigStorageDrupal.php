<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 15.07.2019
 * Time: 13:14
 */

namespace esas\cmsgate;

use Drupal\commerce_payment\Entity\PaymentGateway;
use esas\cmsgate\utils\StringUtils;
use Exception;

class ConfigStorageDrupal extends ConfigStorageCms
{
    private $plugin;
    private $settings;

    public function __construct()
    {
        parent::__construct(); // $GLOBALS["request"]->attributes->parameters["commerce_order"]->values["payment_gateway"]

        $gateways = \Drupal::entityTypeManager()
            ->getStorage('commerce_payment_gateway')->loadByProperties([
                'plugin' => Registry::getRegistry()->getModuleDescriptor()->getModuleMachineName(),
            ]);
        if (count($gateways) > 0) {
            foreach ($gateways as $gateway) {
                if ($this->isManagedGateway($gateway)) {
                    $this->plugin = $gateway;
                    break;
                }
            }
        }
        if ($this->plugin != null)
            $this->settings = $this->plugin->getPluginConfiguration();
    }

    /**
     * @param PaymentGateway $gateway
     * @return bool|null
     */
    private function isManagedGateway($gateway)
    {
        $id = '';
        if ($_POST["form_id"] == 'commerce_payment_gateway_add_form') { //добавление нового платежного шлюза
            $id = $_POST["id"];
        } elseif (str_contains($_SERVER[REQUEST_URI], 'commerce/config/payment-gateways/manage')) { //редактирование платежного шлюза
            $id = StringUtils::substrBetween($_SERVER[REQUEST_URI], '/manage/', '?');
        } else {
            $sessionOrder = CmsConnectorDrupal::getInstance()->getDrupalOrderFromSession();
            if ($sessionOrder != null)
                $id = $sessionOrder->get('payment_gateway')->first()->entity->id();
        }
        return $id === $gateway->id();
    }

    public function getPaymentGatewayId()
    {
        return $this->plugin->id();
    }

    /**
     * @param $key
     * @return string
     * @throws Exception
     */
    public function getConfig($key)
    {
        if (array_key_exists($key, $this->settings))
            return $this->settings[$key];
        else
            return "";
    }

    /**
     * @param $cmsConfigValue
     * @return bool
     * @throws Exception
     */
    public function convertToBoolean($cmsConfigValue)
    {
        return strtolower($cmsConfigValue) == '1';
    }

    /**
     * Сохранение значения свойства в харнилища настроек конкретной CMS.
     *
     * @param string $key
     * @throws Exception
     */
    public function saveConfig($key, $value)
    {
        // not implemented
    }

    public function getConstantConfigValue($key)
    {
        switch ($key) {
            case ConfigFields::orderPaymentStatusPending():
                return "cmsgate_pending";
            case ConfigFields::orderPaymentStatusPayed():
                return "cmsgate_payed";
            case ConfigFields::orderPaymentStatusFailed():
                return "cmsgate_failed";
            case ConfigFields::orderPaymentStatusCanceled():
                return "cmsgate_canceled";
            case ConfigFields::sandbox():
                return $this->settings != null && $this->settings['mode'] === 'test';
            default:
                return null;
        }
    }
}
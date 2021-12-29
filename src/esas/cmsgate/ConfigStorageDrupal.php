<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 15.07.2019
 * Time: 13:14
 */

namespace esas\cmsgate;

use Exception;

class ConfigStorageDrupal extends ConfigStorageCms
{
    private $settings;

    public function __construct()
    {
        parent::__construct();
        $gateway = \Drupal::entityTypeManager()
            ->getStorage('commerce_payment_gateway')->loadByProperties([
                'plugin' => Registry::getRegistry()->getModuleDescriptor()->getModuleMachineName(),
            ]);
        $plugin = reset($gateway);
        $this->settings = $plugin->getPluginConfiguration();
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
        return strtolower($cmsConfigValue) == 'yes'; // not reachable
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
}
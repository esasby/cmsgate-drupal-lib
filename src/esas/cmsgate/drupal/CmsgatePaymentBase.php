<?php

namespace esas\cmsgate\drupal;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\ManualPaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use esas\cmsgate\Registry;
use Exception;

abstract class CmsgatePaymentBase extends PaymentGatewayBase implements ManualPaymentGatewayInterface
{
//    /**
//     * {@inheritdoc}
//     */
//    public function defaultConfiguration()
//    {
//        $defaults = array(
//            self::CONFIG_HG_PAYMENT_METHOD_DESCRIPTION => t('Adding bills to ERIP via Hutkigrosh gateway'),
//            self::CONFIG_HG_COMPLETE_TEXT => array(
//                'value' => t('Your bill was successfully added to ERIP.'))
//        );
//
//        return $defaults + parent::defaultConfiguration();
//    }

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);
        $form = array_merge($form, Registry::getRegistry()->getConfigForm()->generate());
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateConfigurationForm($form, $form_state);
        // можно добавить проверку логина и пароля (с помощью api.login)
        return Registry::getRegistry()->getConfigForm()->isValid();
    }

    /**
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitConfigurationForm($form, $form_state);
        if (!$form_state->getErrors()) {
            $fields = Registry::getRegistry()->getConfigForm()->getManagedFields()->getFieldsToRender();
            $values = $form_state->getValue($form['#parents']);
            foreach ($values as $key => $value) {
                if (array_key_exists($key, $fields))
                $this->configuration[$key] = $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildPaymentInstructions(PaymentInterface $payment)
    {
        return ''; //not implemented
    }

    /**
     * {@inheritdoc}
     */
    public function receivePayment(PaymentInterface $payment, Price $amount = NULL)
    {
        // TODO: Implement receivePayment() method.
    }

    /**
     * {@inheritdoc}
     */
    public function refundPayment(PaymentInterface $payment, Price $amount = NULL)
    {
        // TODO: Implement refundPayment() method.
    }

    /**
     * {@inheritdoc}
     */
    public function voidPayment(PaymentInterface $payment)
    {
        // TODO: Implement voidPayment() method.
    }
}

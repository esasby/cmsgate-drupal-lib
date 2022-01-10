<?php

/**
 * Created by PhpStorm.
 * User: nikit
 * Date: 30.09.2018
 * Time: 15:19
 */

namespace esas\cmsgate\view\admin;

use esas\cmsgate\CmsConnectorDrupal;
use esas\cmsgate\ConfigFieldsDrupal;
use esas\cmsgate\Registry;
use esas\cmsgate\view\admin\fields\ConfigField;
use esas\cmsgate\view\admin\fields\ConfigFieldCheckbox;
use esas\cmsgate\view\admin\fields\ConfigFieldList;
use esas\cmsgate\view\admin\fields\ConfigFieldTextarea;
use esas\cmsgate\view\admin\fields\ListOption;

class ConfigFormDrupal extends ConfigFormArray
{
    private $orderStatuses;

    /**
     * ConfigFieldsRenderWoo constructor.
     */
    public function __construct($formKey, $managedFields)
    {
        parent::__construct($formKey, $managedFields);

//        /** @var WorkflowManagerInterface $workflowManager */
//        $workflowManager = \Drupal::getContainer()->get('plugin.manager.workflow');
//        /** @var WorkflowInterface[] $workflows */
//        $workflows = $workflowManager->getGroupedLabels('commerce_order');
//        foreach ($workflows as $workflow) {
//            foreach ($workflow->getTypePlugin()->getTransitions() as $transition) {
//                $this->orderStatuses[$transition->id()] = new ListOption($transition->id(), $transition->label());
//            }
//        }

        $workflows = CmsConnectorDrupal::getInstance()->getWorkflows();
        foreach ($workflows as $workflow) {
//            foreach ($workflow->getTransitions() as $key => $transition) {
//                $this->orderStatuses[$key] = new ListOption($key, $transition->getLabel());
//            }
            foreach ($workflow->getStates() as $state_id => $state) {
                $this->orderStatuses[$state_id] = new ListOption($state_id, $state->getLabel());
            }
        }
    }

    /**
     * Надо вызывать отдельно от конструктора, т.к. если для модуля будет несколько групп настроек в разных ConfigForm
     * возникает задвоение
     * @return $this
     */
    public function addPhoneFieldNameIfPresent()
    {
        $options = array();
        foreach (CmsConnectorDrupal::getInstance()->getTelephoneFields() as $field) {
            $options[] = new ListOption($field->getName(), $field->getLabel());
        }
        if (count($options) > 1) {
            $this->managedFields->addField(new ConfigFieldList(
                ConfigFieldsDrupal::phoneFieldName(),
                Registry::getRegistry()->getTranslator()->translate(AdminViewFieldsDrupal::MODULE_PHONE_FIELD_LABEL),
                Registry::getRegistry()->getTranslator()->translate(AdminViewFieldsDrupal::MODULE_PHONE_FIELD_DESCRIPTION),
                true, $options));
        }
        return $this;
    }

    public function generateFieldArray(ConfigField $configField, $addDefault = true)
    {
        $ret = array(
            '#title' => $configField->getName(),
            '#description' => $configField->getDescription());
//        if ($addDefault && $configField->hasDefault()) {
//            $ret['#default_value'] = $configField->getDefault();
//        }
        $ret['#default_value'] = $configField->getValue(true);
        if ($configField->isRequired())
            $ret['#required'] = true;
        return $ret;
    }


    public function generateTextField(ConfigField $configField)
    {
        $ret = $this->generateFieldArray($configField);
        $ret['#type'] = 'textfield';
        return $ret;
    }

    public function generateTextAreaField(ConfigFieldTextarea $configField)
    {
        $ret = $this->generateFieldArray($configField);
        $ret['#type'] = 'text_format';
        return $ret;
    }


    public function generateCheckboxField(ConfigFieldCheckbox $configField)
    {
        $ret = $this->generateFieldArray($configField);
        $ret['#type'] = 'checkbox';
        return $ret;
    }

    public function generateListField(ConfigFieldList $configField)
    {
        $ret = $this->generateFieldArray($configField);
        $ret['#type'] = 'select';
        $options = array();
        foreach ($configField->getOptions() as $option)
            $options[$option->getValue()] = $option->getName();
        $ret['#options'] = $options;
        return $ret;
    }

    /**
     * @return ListOption[]
     */
    public function createStatusListOptions()
    {
        return $this->orderStatuses;
    }

}
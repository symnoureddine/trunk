<?php

/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients\Constants;

use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;

/**
 * Description
 */
class CStateInterval extends CInterval
{
    //db field
    /** @var int */
    public $state;

    /**
     * @inheritdoc
     */
    public function getSpec()
    {
        $spec        = parent::getSpec();
        $spec->table = "state_interval";
        $spec->key   = "value_id";

        return $spec;
    }

    /**
     * @inheritdoc
     */
    public function getProps()
    {
        $props               = parent::getProps();
        $props["patient_id"] .= " back|constants_state_interval";
        $props["releve_id"]  .= " back|contants_interval_state";
        $props["state"]      = "num notNull";
        $props["min_value"]  = "dateTime notNull";
        $props["max_value"]  = "dateTime notNull";

        return $props;
    }

    /**
     * @inheritdoc
     */
    public function storeValues(array $values): CAbstractConstant
    {
        if (($state = CMbArray::get($values, "value")) === null) {
            throw new CConstantException(CConstantException::VALUE_NOT_FOUND);
        }
        $this->state = $state;

        return parent::storeValues($values);
    }

    /**
     * @inheritdoc
     */
    public function updateFormFields()
    {
        parent::updateFormFields();
        $this->_input_field = "hidden";
    }

    /**
     * @inheritdoc
     */
    public function getValue()
    {
        $values          = parent::getValue();
        $values["value"] = $this->state;

        return $values;
    }

    /**
     * @inheritdoc
     */
    public function check()
    {
        if ($this->_forced_store) {
            return parent::check();
        }

        if (!$this->_ref_spec) {
            $this->getRefSpec();
        }

        $list = explode("|", $this->_ref_spec->list);
        if (!CMbArray::in($this->state, $list)) {
            return "||" . CConstantException::INVALID_VALUE_NOT_AUTHORIZED;
        }

        $min      = $this->_ref_spec->min_value;
        $max      = $this->_ref_spec->max_value;
        $duration = CMbDT::minutesRelative($this->min_value, $this->max_value) / 60; // en seconde
        if ($min !== null) {
            if ($duration < $min) {
                return "||" . CConstantException::INVALID_VALUE_UNDER_MINIMUM;
            }
        }

        if ($max !== null) {
            if ($duration > $max) {
                return "||" . CConstantException::INVALID_VALUE_UPPER_MAXIMUM;
            }
        }

        return parent::check();
    }

    /**
     * @inheritdoc
     */
    public function matchingValues(array $values): bool
    {
        $state = CMbArray::get($values, "value");

        return intval($state) === intval($this->state) && parent::matchingValues($values);
    }

    /**
     * @inheritdoc
     */
    protected function updateValue(): void
    {
        if ($this->_id && $this->_ref_spec) {
            $state             = CAppUI::tr("CStateInterval-state") . ":"
                . CAppUI::tr("CStateInterval.state." . $this->_ref_spec->code . "." . $this->state);
            $this->_view_value = $state . " " . $this->min_value . " || " . $this->max_value;
        }
        parent::updateValue();
    }

    /**
     * @inheritdoc
     */
    protected function findAlert(CConstantAlert $alert): array
    {
        $data_alert = [];
        for ($i = 1; $i <= CConstantAlert::$NB_ALERTS; $i++) {
            $seuil_bas  = "seuil_bas_$i";
            $seuil_haut = "seuil_haut_$i";
            $duration   = CMbDT::minutesRelative($this->min_value, $this->max_value) * 60; // en secondes
            if ($duration > $alert->{$seuil_haut} && $alert->{$seuil_haut} !== null) {
                $data_alert ["seuil"] = "haut";
                $data_alert ["level"] = "$i";
            } elseif ($duration < $alert->{$seuil_bas} && $alert->{$seuil_bas} !== null) {
                $data_alert ["seuil"] = "bas";
                $data_alert ["level"] = "$i";
            }
        }

        return $data_alert;
    }
}

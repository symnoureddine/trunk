<?php
/**
 * @package Mediboard\MonitoringPatient
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\MonitoringPatient;

use Ox\Core\CMbFieldSpec;
use Ox\Core\CMbObject;
use Ox\Mediboard\Files\CFile;

/**
 * Observation value, based on the HL7 OBX specification
 * http://www.interfaceware.com/hl7-standard/hl7-segment-OBX.html
 */
class CObservationResult extends CMbObject
{
    public $observation_result_id;

    public $observation_result_set_id;
    public $value_type_id; // OBX.3
    public $unit_id;       // OBX.6
    public $value;         // OBX.2
    public $method;        // OBX.17
    public $status;        // OBX.11

    public $file_id;
    public $label_id;

    /** @var CMbObject */
    public $_ref_context;

    /** @var CObservationValueType */
    public $_ref_value_type;

    /** @var CObservationValueUnit */
    public $_ref_value_unit;

    /** @var CObservationResultSet */
    public $_ref_result_set;

    /** @var CFile */
    public $_ref_file;

    /** @var CSupervisionGraphAxisValueLabel */
    public $_ref_label;

    /**
     * @inheritdoc
     */
    function getSpec()
    {
        $spec           = parent::getSpec();
        $spec->table    = "observation_result";
        $spec->key      = "observation_result_id";
        $spec->loggable = false;

        return $spec;
    }

    /**
     * @inheritdoc
     */
    function getProps()
    {
        $props                              = parent::getProps();
        $props["observation_result_set_id"] = "ref notNull class|CObservationResultSet back|observation_results";
        $props["value_type_id"]             = "ref notNull class|CObservationValueType back|observation_results";
        $props["unit_id"]                   = "ref class|CObservationValueUnit back|observation_results";
        $props["value"]                     = "str notNull helped|value_type_id|unit_id";
        $props["method"]                    = "str";
        $props["status"]                    = "enum list|C|D|F|I|N|O|P|R|S|U|W|X default|F";
        $props["file_id"]                   = "ref class|CFile back|observation_results";
        $props["label_id"]                  = "ref class|CSupervisionGraphAxisValueLabel back|observation_results";

        return $props;
    }

    /**
     * Load result set
     *
     * @param bool $cache Use object cache
     *
     * @return CObservationResultSet
     */
    function loadRefResultSet($cache = true)
    {
        return $this->_ref_result_set = $this->loadFwdRef("observation_result_set_id", $cache);
    }

    /**
     * Load value unit
     *
     * @return CObservationValueUnit
     */
    function loadRefValueUnit()
    {
        return $this->_ref_value_unit = CObservationValueUnit::get($this->unit_id);
    }

    /**
     * Load file
     *
     * @return CFile
     */
    function loadRefFile()
    {
        return $this->_ref_file = $this->loadFwdRef("file_id");
    }

    /**
     * Load label object
     *
     * @return CSupervisionGraphAxisValueLabel
     */
    function loadRefLabel()
    {
        return $this->_ref_label = $this->loadFwdRef("label_id");
    }

    /**
     * @inheritdoc
     */
    function updateFormFields()
    {
        parent::updateFormFields();

        $this->_view = $this->value . " " . CObservationValueUnit::get($this->unit_id)->_view;
    }

    /**
     * @inheritdoc
     */
    function updatePlainFields()
    {
        parent::updatePlainFields();

        if ($this->value !== null) {
            $value_type = $this->loadRefValueType();
            if ($value_type->datatype === "NM") {
                $this->value = CMbFieldSpec::checkNumeric($this->value, false);
            }
        }
    }

    /**
     * Load value type
     *
     * @param bool $cache Use object cache
     *
     * @return CObservationValueType
     */
    function loadRefValueType($cache = true)
    {
        return $this->_ref_value_type = $this->loadFwdRef("value_type_id", $cache);
    }
}

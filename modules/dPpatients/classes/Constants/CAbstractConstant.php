<?php

/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients\Constants;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CStoredObject;
use Ox\Mediboard\Patients\CPatient;

/**
 * Description
 * ABSTRACT CLASS
 */
class CAbstractConstant extends CStoredObject
{
    /** @var string[] Children strorable */
    public const CONSTANT_CLASS = [
        CValueInt::class,
        CValueFloat::class,
        CValueText::class,
        CValueEnum::class,
        CDateTimeInterval::class,
        CValueInterval::class,
        CStateInterval::class,
    ];

    /** @var int Primary key */
    public $value_id;

    // DB Fields
    /** @var int */
    public $releve_id;
    /** @var int */
    public $spec_id;
    /** @var int */
    public $patient_id;
    /** @var string */
    public $datetime;
    /** @var string */
    public $update;
    /** @var int */
    public $active;
    /** @var string */
    public $created_datetime;

    //form field
    /** @var string */
    public $_category;
    /** @var string */
    public $_category_spec;
    /** @var string */
    public $_input_field;
    /** @var string */
    public $_view_alert;
    /** @var string */
    public $_time;
    /** @var string */
    public $_view_value;
    /** @var int */
    public $_forced_store = 0;
    /** @var int */
    public $_show_alert = 1;

    // refs
    /** @var CConstantReleve */
    public $_ref_releve;
    /** @var CPatient */
    public $_ref_patient;
    /** @var CConstantSpec */
    public $_ref_spec;
    /** @var CReleveComment */
    public $_ref_comment;

    /**
     * Calculate value
     *
     * @param CAbstractConstant[] $constants values for expression
     * @param string              $formula   expression to evaluate
     *
     * @return array
     * @throws CConstantException
     */
    public static function calculateValue(array $constants, string $formula): array
    {
        throw new CConstantException(CConstantException::FUNCTION_NOT_IMPLEMENTED, __METHOD__);
    }

    /**
     * @inheritdoc
     */
    public function getSpec()
    {
        $spec           = parent::getSpec();
        $spec->key      = "value_id";
        $spec->loggable = false;

        return $spec;
    }

    /**
     * @inheritdoc
     */
    public function getProps()
    {
        $props               = parent::getProps();
        $props["releve_id"]  = "ref notNull class|CConstantReleve";
        $props["spec_id"]    = "num notNull";
        $props["patient_id"] = "ref class|CPatient notNull";
        // real constant datetime
        $props["created_datetime"] = "dateTime notNull";
        // releve datetime ==> evite une jointure
        $props["datetime"]       = "dateTime notNull";
        $props["update"]         = "dateTime";
        $props["active"]         = "bool notNull";
        $props["_category"]      = "enum list|physio|biolo|activity|all";
        $props["_category_spec"] = "enum list|physio|biolo|activity";
        $props["_time"]          = "time";
        $props["_view_value"]    = "str";

        return $props;
    }

    /**
     * @inheritdoc
     */
    public function updateFormFields()
    {
        parent::updateFormFields();
        $this->getRefSpec();
        $this->updateValue();
        $this->_time = CMbDT::time(null, $this->datetime);
    }

    /**
     * Charge le relevé d'une constante
     *
     * @return CConstantReleve|CStoredObject
     * @throws Exception
     */
    public function loadRefReleve(): CConstantReleve
    {
        return $this->_ref_releve = $this->loadFwdRef("releve_id", true);
    }

    /**
     * Load patient
     *
     * @return CPatient|CStoredObject
     * @throws Exception
     */
    public function loadRefPatient(): CPatient
    {
        return $this->_ref_patient = $this->loadFwdRef("patient_id", true);
    }

    /**
     * Load constantSpec
     *
     * @return CConstantSpec|CStoredObject
     */
    public function getRefSpec(): CConstantSpec
    {
        return $this->_ref_spec = CConstantSpec::getSpecById($this->spec_id);
    }

    /**
     * @inheritdoc
     */
    public function store()
    {
        !$this->_id ? $this->active = 1 : $this->update = CMbDT::dateTime();
        if (!$this->created_datetime) {
            $this->created_datetime = CMbDT::dateTime();
        }

        return parent::store();
    }

    /**
     * To know if constant comes from base or xml
     *
     * @return bool true if constant comes from base
     */
    public function isConstantBase(): bool
    {
        if (!$this->_ref_spec) {
            $this->getRefSpec();
        }

        return $this->_ref_spec->_is_constant_base;
    }

    /**
     * To know if constant is constant calculated
     *
     * @return bool if constant is calculated true
     */
    public function isCalculatedConstant(): bool
    {
        if (!$this->_ref_spec) {
            $this->getRefSpec();
        }

        return $this->_ref_spec->isCalculatedConstant();
    }

    /**
     * Check and set update of constant
     *
     * @param array         $values        value or values
     * @param CConstantSpec $constant_spec constant spec
     * @param string        $action_type   action about releve
     *
     * @return CAbstractConstant|null
     * @throws CConstantException
     */
    public function checkChangeValue(
        array $values,
        string $constant_spec,
        string $action_type = "other"
    ): ?CAbstractConstant {
        $datetime = CMbArray::get($values, "datetime", CMbDT::dateTime());

        // Création d'une nouvelle constante
        if ($action_type == "saved" || !$this->loadMatchingObject()) {
            $this->datetime = $datetime;

            return $this->storeValues($values);
        }

        // Valeur de la constant identique
        if ($this->matchingValues($values)) {
            throw new CConstantException(CConstantException::IDENTICAL_CONSTANT);
        }

        // si la constante est modifiable, on la modifie
        if ($constant_spec->alterable) {
            $this->update = CMbDT::dateTime();
            $this->storeValues($values);
            CConstantReleve::$report->addUpdatedObject($this);

            return $this;
        }
        // Pour une nouvelle valeur on met en inactive l'ancienne valeur, et on enregistre la nouvelle
        $constant_value = clone $this;
        $this->storeInactive(false);

        $constant_value->_id              = null;
        $constant_value->created_datetime = null;
        $constant_value->storeValues($values);
        CConstantReleve::$report->addUpdatedObject($this);
        CConstantReleve::$report->addStoredObject($constant_value);

        return $constant_value;
    }

    /**
     * Add comment
     *
     * @param string  $message comment
     * @param bool $force   force store of comment and erase old comment
     *
     * @return bool
     * @throws CConstantException
     */
    public function addComment(string $message, bool $force = false): ?CReleveComment
    {
        if (!$message) {
            return null;
        }

        if (!$force && $this->loadComment()->_id) {
            return null;
        }

        $comment              = new CReleveComment();
        $comment->comment     = $message;
        $comment->releve_id   = $this->releve_id;
        $comment->value_id    = $this->value_id;
        $comment->value_class = $this->_class;
        if ($msg = $comment->store()) {
            throw new CConstantException(CConstantException::INVALID_STORE_COMMENT, $msg);
        }

        return $comment;
    }

    /**
     * Load comment attached to this constant
     *
     * @return CReleveComment
     * @throws \Exception
     */
    public function loadComment(): CReleveComment
    {
        $comment              = new CReleveComment();
        $comment->releve_id   = $this->releve_id;
        $comment->value_id    = $this->value_id;
        $comment->value_class = $this->_class;
        $comment->loadMatchingObject();

        return $this->_ref_comment = $comment;
    }

    /**
     * Set releve inactive
     *
     * @param bool $checkReleve check validity of releve if true
     *
     * @return void
     * @throws CConstantException
     */
    public function storeInactive(bool $checkReleve = true): void
    {
        $this->active = 0;
        $this->forceStoreOn();
        if ($msg = $this->store()) {
            $this->treatErrorStore($msg);
        }
        $this->forceStoreOff();

        if ($checkReleve) {
            $this->loadRefReleve()->checkActive();
        }
    }

    /**
     * Generate alert html to display
     *
     * @return array
     * @throws CConstantException
     */
    public function generateViewAlert(): array
    {
        if (!$this->_ref_spec->_ref_alert) {
            $this->_ref_spec->loadRefAlert();
        }
        if ($this->isConstantBase() && !$this->_ref_spec->_ref_alert) {
            return $this->_view_alert = [];
        }

        if ($this->_ref_spec->_ref_alert && $this->_ref_spec->_ref_alert->_nb_level_alerts > 0) {
            $alert = $this->findAlert($this->_ref_spec->_ref_alert);
        } elseif ($this->_ref_spec->_alert && $this->_ref_spec->_alert->_nb_level_alerts > 0) {
            $alert = $this->findAlert($this->_ref_spec->_alert);
        } else {
            return [];
        }

        if (!$seuil = CMbArray::get($alert, "seuil")) {
            return [];
        }

        $level   = CMbArray::get($alert, "level");
        $comment = "comment_" . $seuil . "_" . $level;
        ($this->_ref_spec->_ref_alert && $this->_ref_spec->_ref_alert->_nb_level_alerts > 0) ?
            $comment_alert = $this->_ref_spec->_ref_alert->{$comment} :
            $comment_alert = $this->_ref_spec->_alert->{$comment};

        return $this->_view_alert = ["level" => $level, "title" => $comment_alert];
    }

    /**
     * Get identifiant with code-id
     *
     * @return String
     */
    public function getCodeId(): string
    {
        return $this->getRefSpec()->code . "-" . $this->_id;
    }

    /**
     * Get name of constant translate
     *
     * @return string if not spec, return unknown constant
     */
    public function getViewName(): string
    {
        if (!$this->_ref_spec) {
            return CAppUI::tr("CConstantSpec.name.unknown");
        }

        return $this->_ref_spec->getViewName();
    }

    /**
     * Get name of constant
     *
     * @return string if not spec, return unknown constant
     */
    public function getName(): string
    {
        if (!$this->_ref_spec) {
            return CAppUI::tr("CConstantSpec.name.unknown");
        }

        return $this->_ref_spec->name;
    }

    /**
     * Format view unit
     *
     * @return string
     */
    public function getViewUnit(): string
    {
        if (!$this->_ref_spec) {
            return CAppUI::tr("CConstantSpec.unit.unknown");
        }
        $unit = explode("|", $this->_ref_spec->unit);

        return CMbArray::get($unit, "0");
    }

    /**
     * Set value of input
     *
     * @param array $values value or values to save
     *
     * @return CAbstractConstant
     * @throws CConstantException
     */
    public function storeValues(array $values): self
    {
        throw new CConstantException(CConstantException::FUNCTION_NOT_IMPLEMENTED, __METHOD__);
    }

    /**
     * Get value of constant
     *
     * @return mixed
     * @throws CConstantException
     */
    public function getValue()
    {
        throw new CConstantException(CConstantException::FUNCTION_NOT_IMPLEMENTED, __METHOD__);
    }

    /**
     * Compare this with array
     *
     * @param array $values value
     *
     * @return boolean
     * @throws CConstantException
     */
    public function matchingValues(array $values): bool
    {
        throw new CConstantException(CConstantException::FUNCTION_NOT_IMPLEMENTED, __METHOD__);
    }

    /**
     * Update formfield _view_value
     *
     * @return void
     */
    protected function updateValue(): void
    {
        if (!$this->_ref_spec) {
            $this->_view_value .= " " . CAppUI::tr("CConstantSpec-msg-perhaps delete");
        }
    }

    /**
     * Treat error in store
     *
     * @param String $msg msg_error
     *
     * @return void
     * @throws CConstantException
     */
    protected function treatErrorStore(?string $msg): void
    {
        $code_exception = CMbArray::get(explode("||", $msg), 1);
        switch ($code_exception) {
            case CConstantException::INVALID_VALUE_NOT_AUTHORIZED:
                throw new CConstantException(CConstantException::INVALID_VALUE_NOT_AUTHORIZED);
            case CConstantException::INVALID_VALUE_UNDER_MINIMUM:
                throw new CConstantException(CConstantException::INVALID_VALUE_UNDER_MINIMUM);
            case CConstantException::INVALID_VALUE_UPPER_MAXIMUM:
                throw new CConstantException(CConstantException::INVALID_VALUE_UPPER_MAXIMUM);
            default:
                throw new CConstantException(CConstantException::INVALID_STORE_CONSTANT, $msg);
        }
    }

    /**
     * Choose message Alert and level
     *
     * @param CConstantAlert $alert Alert to display
     *
     * @return array
     * @throws CConstantException
     */
    protected function findAlert(CConstantAlert $alert): array
    {
        throw new CConstantException(CConstantException::FUNCTION_NOT_IMPLEMENTED, __METHOD__);
    }

    /**
     * Set force store to on
     *
     * @return void
     */
    private function forceStoreOn(): void
    {
        $this->_forced_store = 1;
    }

    /**
     * Set force store to off
     *
     * @return void
     */
    private function forceStoreOff(): void
    {
        $this->_forced_store = 0;
    }
}

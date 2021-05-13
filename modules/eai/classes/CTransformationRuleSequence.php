<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Eai;

use Ox\Core\CMbObject;
use Ox\Core\CStoredObject;
use Ox\Interop\Hl7\CHEvent;
use Ox\Interop\Hl7\CHL7v2Exception;
use Ox\Interop\Hl7\CHL7v2Message;
use Ox\Interop\Hl7\Events\CHL7Event;
use ReflectionClass;

/**
 * Description
 */
class CTransformationRuleSequence extends CMbObject
{
    /** @var integer Primary key */
    public $transformation_rule_sequence_id;

    // DB fields
    public $name;
    public $description;
    public $standard;
    public $domain;
    public $profil;
    public $message_type;
    public $message_example;
    public $transaction;
    public $version;
    public $extension;
    public $source;
    public $transformation_ruleset_id;

    /** @var CTransformationRuleSet */
    public $_ref_transformation_ruleset;

    // Form fields
    public $_ref_transformation_rules;
    /** @var CHL7v2Message|string */
    public $_message;

    /**
     * @inheritdoc
     */
    public function getSpec()
    {
        $spec        = parent::getSpec();
        $spec->table = "transformation_rule_sequence";
        $spec->key   = "transformation_rule_sequence_id";

        return $spec;
    }

    /**
     * @inheritdoc
     */
    public function getProps()
    {
        $props = parent::getProps();

        $props["name"]            = "str notNull";
        $props["description"]     = "str";
        $props["standard"]        = "str";
        $props["domain"]          = "str";
        $props["profil"]          = "str";
        $props["message_type"]    = "str";
        $props["message_example"] = "text notNull";
        $props["transaction"]     = "str";
        $props["version"]         = "str";
        $props["extension"]       = "str";
        $props["source"]          = "str";

        $props["transformation_ruleset_id"] = "ref class|CTransformationRuleSet autocomplete|text back|transformation_rule_sequences";

        return $props;
    }

    /**
     * Load rules sequences
     *
     * @param array $where
     *
     * @return CTransformationRuleSequence[]|CStoredObject[]
     * @throws \Exception
     */
    public function loadRefsTransformationRules($where = [])
    {
        return $this->_ref_transformation_rules = $this->loadBackRefs(
            "transformation_rules", "rank ASC", null, null, null, null, 'transformation_rules', $where);
    }

    /**
     * @throws CHL7v2Exception
     *
     * @return void
     */
    public function getMessage(): void
    {
        // On parse que si c'est du HL7
        if (preg_match('#MSH#', $this->message_example)) {
            $hl7_message = new CHL7v2Message();
            $hl7_message->parse($this->message_example);
            $this->_message = $hl7_message;
        } else {
            $this->_message = $this->message_example;
        }
    }

    /**
     * Check if sequence can be apply for event
     *
     * @param CHEvent $event
     *
     * @return bool
     */
    public function checkAvailability(CHL7Event $event): bool
    {
        // Message ?
        if ($this->message_type) {
            return $this->compareMessageType($event);
        }

        // Transaction ?
        if ($this->transaction && ($this->transaction == $event->transaction)) {
            return true;
        }

        // Profil ?
        if ($this->profil && ($this->profil == $event->profil)) {
            return true;
        }

        // TODO : Vérifier Domaine et Standard quand on fera les transformations pour les autres normes que HL7

        return false;
    }

    /**
     * Check if sequence can be apply for event
     *
     * @param CHEvent $event
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function compareMessageType(CHL7Event $event): bool
    {
        $reflect = new ReflectionClass($event);
        $short_name = $reflect->getShortName();

        if ($short_name && $this->message_type) {
            return true;
        }

        return false;
    }
}

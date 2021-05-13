<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Eai;

/**
 * Class CTransformationRule
 * EAI transformation rule
 */

use Exception;
use Ox\Core\CMbArray;
use Ox\Core\CMbObject;
use Ox\Interop\Hl7\CHL7v2Message;

class CTransformationRule extends CMbObject
{
    // DB Table key
    public $transformation_rule_id;

    // DB fields
    public $name;
    public $extension;
    public $xpath_source;
    public $xpath_target;
    public $action_type;
    public $value;
    public $active;
    public $rank;
    public $transformation_rule_sequence_id;
    public $params;

    /** @var CTransformationRuleSequence */
    public $_ref_transformation_rule_sequence;

    /**
     * @see parent::getSpec()
     */
    public function getSpec()
    {
        $spec = parent::getSpec();

        $spec->table = 'transformation_rule';
        $spec->key   = 'transformation_rule_id';

        return $spec;
    }

    /**
     * @see parent::getProps()
     */
    public function getProps()
    {
        $props = parent::getProps();

        $props["name"]         = "str notNull";
        $props["xpath_source"] = "str notNull";
        $props["xpath_target"] = "str";
        $props["action_type"]  = "enum list|insert|delete|map|trim|sub|pad|upper|lower|copy|concat";
        $props["active"]       = "bool default|0";
        $props["rank"]         = "num min|1 show|0";
        $props["params"]       = "text";

        $props["transformation_rule_sequence_id"] = "ref class|CTransformationRuleSequence autocomplete|text back|transformation_rules";

        return $props;
    }

    /**
     * Load rule_sequence
     *
     * @return CTransformationRuleSequence
     * @throws Exception
     */
    public function loadRefCTransformationRuleSequence()
    {
        return $this->_ref_transformation_rule_sequence = $this->loadFwdRef("transformation_rule_sequence_id", true);
    }

    /**
     * @see parent::store
     */
    public function store()
    {
        if (!$this->_id) {
            $transf_rule                                  = new CTransformationRule();
            $transf_rule->transformation_rule_sequence_id = $this->transformation_rule_sequence_id;

            $this->rank = $transf_rule->countMatchingList() + 1;
        }

        return parent::store();
    }

    /**
     * Apply rule of the content
     *
     * @param string $content
     *
     * @return CHL7v2Message
     * @throws \Ox\Interop\Hl7\CHL7v2Exception
     */
    public function apply(string $content): CHL7v2Message
    {
        $hl7_message = new CHL7v2Message();
        $hl7_message->parse($content);

        $action = $this->action_type . 'Transformation';
        $hl7_message = $this->$action($hl7_message, $this->xpath_source, $this->xpath_target);

        return $hl7_message;
    }

    /**
     * @param CHL7v2Message $hl7_message
     * @param string $xpath_source
     * @param string $xpath_target
     *
     * @return CHL7v2Message
     */
    public function insertTransformation(
        CHL7v2Message $hl7_message,
        ?string $xpath_source,
        ?string $xpath_target
    ): CHL7v2Message {

        $xml  = $hl7_message->toXML(null, true);
        $hl7_message->parse($xml->toER7($hl7_message));

        $xpath_sources = explode('|', $xpath_source);
        foreach ($xpath_sources as $_xpath_source) {
            $insert_node = $xml->queryNode($_xpath_source);
            // Noeud vide => on l'ajoute en récupérant le parent
            if (!$insert_node) {
                $xpath_parent = explode('/', $_xpath_source);
                if (!CMbArray::get($xpath_parent, 0)) {
                    continue;
                }

                $parent_node = $xml->queryNode(CMbArray::get($xpath_parent, 0));

                // Ajout du noeud fils
                $new_node = $xml->createElement(
                    trim(CMbArray::get($xpath_parent, 1)),
                    str_replace('"', '', $this->params)
                );
                $parent_node->appendChild($new_node);
            } else {
                // Noeud déjà existant => on change juste la valeur
                $insert_node->nodeValue = str_replace('"', '', $this->params);
            }
        }

        $hl7_message_new = new CHL7v2Message();
        $hl7_message_new->parse($xml->toER7($hl7_message));

        return $hl7_message_new;
    }

    /**
     * @param CHL7v2Message $hl7_message
     * @param string $xpath_source
     * @param string $xpath_target
     *
     * @return CHL7v2Message
     */
    public function deleteTransformation(
        CHL7v2Message $hl7_message,
        ?string $xpath_source,
        ?string $xpath_target
    ): CHL7v2Message {

        $xml  = $hl7_message->toXML(null, true);
        $xpath_sources = explode('|', $xpath_source);
        foreach ($xpath_sources as $_xpath_source) {
            // Suppression d'un champ
            if (preg_match('#/#', $xpath_source)) {
                $delete_node = $xml->queryNode($_xpath_source);
                if (!$delete_node) {
                    continue;
                }
                $delete_node->nodeValue = '';
            } else {
                // Suppression d'un segemnt
                $delete_node = $xml->queryNode($_xpath_source);
                $delete_node->parentNode->removeChild($delete_node);
            }
        }

        $hl7_message_new = new CHL7v2Message();
        $hl7_message_new->parse($xml->toER7($hl7_message));

        return $hl7_message_new;
    }
}

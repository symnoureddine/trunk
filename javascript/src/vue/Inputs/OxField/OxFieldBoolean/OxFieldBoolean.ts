/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component } from "vue-property-decorator"
import OxFieldCore from "@system/OxFieldCore"

/**
 * Composant de champ booléen
 */
@Component
export default class OxFieldBoolean extends OxFieldCore {
    /**
     * Remontée de l'évenement de changement de valeur
     * @param {Event} event - Evenement déclencheur
     */
    private click () {
        this.change(this.mutatedValue)
    }
}

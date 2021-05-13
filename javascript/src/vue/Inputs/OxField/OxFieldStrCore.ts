/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component } from "vue-property-decorator"
import OxFieldCore from "@system/OxFieldCore"
import OxIconCore from "@system/OxIconCore"

/**
 * OxFieldStrCore
 *
 * Composant-modèle pour les composants de champ texte
 */
@Component
export default class OxFieldStrCore extends OxFieldCore {
    /**
     * Nom d'icone à afficher
     */
    protected get iconName () {
        return OxIconCore.get(this.icon)
    }
}

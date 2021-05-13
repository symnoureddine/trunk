/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxFieldStrCore from "@system/OxFieldStrCore"

/**
 * OxFieldText
 *
 * Composant de champ de texte
 */
@Component
export default class OxFieldText extends OxFieldStrCore {
    @Prop({ default: 5 })
    private rows!: number

    /**
     * Composant monté
     */
    protected mounted (): void {
        this.updateMutatedValue()
    }
}

/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxFieldStrCore from "@system/OxFieldStrCore"

/**
 * Composant de champ de texte
 */
@Component
export default class OxFieldString extends OxFieldStrCore {
    @Prop({ default: false })
    private number!: boolean

    /**
     * Composant mont�
     */
    protected mounted (): void {
        this.updateMutatedValue()
    }

    /**
     * Remont�e de l'�venement click
     */
    private click (): void {
        this.$emit("click")
    }
}

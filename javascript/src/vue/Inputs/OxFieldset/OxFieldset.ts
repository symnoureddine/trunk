/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxVue from "@system/OxVue"
import OxIcon from "@system/OxIcon"

/**
 * OxField
 *
 * Composant de Field (label + Container)
 */
@Component({ components: { OxIcon } })
export default class OxFieldset extends OxVue {
    // Label du field
    @Prop({ default: "" })
    private label!: string
    @Prop({ default: "" })
    private icon!: string
}

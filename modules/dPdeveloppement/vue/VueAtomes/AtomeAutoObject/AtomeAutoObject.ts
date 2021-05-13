/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxVue from "@system/OxVue"

/**
 * AtomeAutoObject
 */
@Component
export default class AtomeAutoObject extends OxVue {
    @Prop()
    private item!: {
        id: number
        mainText: string
        subText: string
        icon: string
    }
}

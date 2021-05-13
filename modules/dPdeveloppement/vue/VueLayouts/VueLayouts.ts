/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component } from "vue-property-decorator"
import OxVue from "@system/OxVue"
import OxLayoutAsideRight from "@system/OxLayoutAsideRight"
import OxLayoutMain from "@system/OxLayoutMain"
import OxCard from "@system/OxCard"
import OxCardAction from "@system/OxCardAction"
import OxCardHeader from "@system/OxCardHeader"

/**
 * VueLayouts
 */
@Component({
    components: {
        OxLayoutAsideRight,
        OxLayoutMain,
        OxCard,
        OxCardHeader,
        OxCardAction
    }
})
export default class VueLayouts extends OxVue {
    private currentLayout = "OxLayoutMain"

    private layoutsList = [
        "OxLayoutMain",
        "OxLayoutAsideRight",
        "OxCard"
    ]

    private setCurrentLayout (layout: string): void {
        this.currentLayout = layout
    }
}

/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxVue from "@system/OxVue"
import OxVicon from "@system/OxVicon"

/**
 * ObjectCardEdit
 */
@Component({ components: { OxVicon } })
export default class ObjectCardEdit extends OxVue {
    @Prop({ default: "" })
    private icon?: string

    @Prop({ default: "" })
    private title?: string
}

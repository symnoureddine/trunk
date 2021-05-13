/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxVue from "@system/OxVue"
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library } from "@fortawesome/fontawesome-svg-core"
import {
    faAngleDown,
    faCaretDown, faCaretUp,
    faEdit,
    faEllipsisV,
    faFile,
    faFilter,
    faPrint, faProcedures, faRedo,
    faSort,
    faSortDown,
    faSortUp, faSpinner, faTimes
} from "@fortawesome/free-solid-svg-icons"
import {
    faCircle
} from "@fortawesome/free-regular-svg-icons"

library.add(
    faFilter,
    faSort,
    faSortUp,
    faSortDown,
    faCaretDown,
    faCaretUp,
    faFile,
    faPrint,
    faEdit,
    faEllipsisV,
    faAngleDown,
    faSpinner,
    faTimes,
    faProcedures,
    faRedo
)
library.add(
    faCircle
)

/**
 * OxIcon
 *
 * Composant d'icone
 */
@Component({ components: { FontAwesomeIcon } })
export default class OxIcon extends OxVue {
    // Identifiant de l'icône
    @Prop({ default: "" })
    private icon!: string

    // Taille de l'icône
    @Prop({ default: 24 })
    private size!: number

    /**
     * Style additionnel à l'icone
     */
    private get iconStyle () {
        return {
            fontSize: this.size + "px"
        }
    }
}

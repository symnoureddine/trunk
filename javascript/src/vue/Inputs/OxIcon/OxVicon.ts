/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxVue from "@system/OxVue"
import OxIconCore from "@system/OxIconCore"

/**
 * OxVicon
 *
 * Composant d'icone
 */
@Component
export default class OxVicon extends OxVue {
    // Identifiant de l'ic�ne
    @Prop({ default: "" })
    private icon!: string

    @Prop({ default: false })
    private dark!: boolean

    @Prop({ default: false })
    private right!: boolean

    @Prop({ default: false })
    private left!: boolean

    @Prop({ default: "" })
    private className!: string

    @Prop({ default: undefined })
    private size!: number | undefined

    @Prop({ default: undefined })
    private color!: string | undefined

    /**
     * R�cup�ration de la source d'icone
     */
    private get iconSvg () {
        return OxIconCore.get(this.icon)
    }
}

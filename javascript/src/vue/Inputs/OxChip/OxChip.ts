/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxVue from "@system/OxVue"
import OxThemeCore from "@system/OxThemeCore"

/**
 * Composant de chip
 */
@Component
export default class OxChip extends OxVue {
    @Prop({ default: true })
    private chipActive!: boolean

    @Prop({ default: false })
    private close!: boolean

    @Prop({ default: false })
    private small!: boolean

    @Prop({ default: "enabled" })
    private state!: "activated" | "enabled" | "error" | "success"

    /**
     * Couleur du texte de la chip
     *
     * @return {string}
     */
    private get textColor (): string {
        switch (this.state) {
        case "activated":
            return OxThemeCore.primary
        case "error":
            return OxThemeCore.errorText
        case "success":
            return OxThemeCore.successText
        default:
            return ""
        }
    }

    /**
     * Couleur de fond de la chip
     *
     * @return {string}
     */
    private get backgroundColor (): string {
        switch (this.state) {
        case "activated":
            return OxThemeCore.primary + "1F"
        case "error":
            return OxThemeCore.errorText + "29"
        case "success":
            return OxThemeCore.successText + "29"
        default:
            return ""
        }
    }
}

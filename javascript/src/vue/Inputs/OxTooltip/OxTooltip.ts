/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxVue from "@system/OxVue"

/**
 * OxTooltip
 *
 */
@Component
export default class OxTooltip extends OxVue {
    @Prop({ default: "bottom" })
    private position!: "bottom" | "right" | "top" | "left"

    @Prop({ default: undefined })
    private delay!: number

    @Prop({ default: false })
    private disabled!: boolean

    /**
     * Tooltip à afficher en bas
     *
     * @return {boolean}
     */
    private get bottom (): boolean {
        return this.position === "bottom"
    }

    /**
     * Tooltip à afficher à droite
     *
     * @return {boolean}
     */
    private get right (): boolean {
        return this.position === "right"
    }

    /**
     * Tooltip à afficher en haut
     *
     * @return {boolean}
     */
    private get top (): boolean {
        return this.position === "top"
    }

    /**
     * Tooltip à afficher à gauche
     *
     * @return {boolean}
     */
    private get left (): boolean {
        return this.position === "left"
    }
}

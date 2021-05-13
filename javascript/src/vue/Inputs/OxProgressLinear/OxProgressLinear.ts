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
 * OxProgressLinear
 *
 * Composant de progress bar linéaire
 */
@Component
export default class OxProgressLinear extends OxVue {
    @Prop()
    private backgroundColor?: string

    @Prop()
    private bufferValue?: number | string

    @Prop({ default: OxThemeCore.primary })
    private color!: string

    @Prop()
    private height?: string | number

    @Prop()
    private rounded?: boolean

    @Prop()
    private value?: number | string
}

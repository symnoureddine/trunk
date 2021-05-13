/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxVue from "@system/OxVue"

/**
 * ActeCcamAutocomplete
 */
@Component
export default class ActeCcamAutocomplete extends OxVue {
    @Prop()
    private item!: {
        code: string
        libelle_long: string
    }
}

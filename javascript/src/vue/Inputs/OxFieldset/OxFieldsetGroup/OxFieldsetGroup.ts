/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxVue from "@system/OxVue"

/**
 * OxFieldGroup
 *
 * Composant wrapper de Fields
 */
@Component
export default class OxFieldset extends OxVue {
    // Label du groupe de fields
    @Prop({ default: "" })
    private label!: string
}

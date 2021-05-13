/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import {Component, Prop} from "vue-property-decorator"
import INVue from "@components/INVue"
import INValueString from "@components/INValueString"

/**
 * Wrapper des champs de saisie de texte de l'Install
 */
@Component({components: {INValueString}})
export default class INValueDatetime extends INVue {
  @Prop({default: ""})
  private field!: string
}

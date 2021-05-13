/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import {Component, Prop} from "vue-property-decorator"
import INVue from "@components/INVue"
/**
 * Wrapper des champs de saisie de texte de l'Install
 */
@Component
export default class INTabs extends INVue {
  @Prop({default: []})
  private tabs!: object[]
  @Prop({default: ""})
  private currentTab!: string

  private tabClick(tab: string): void {
    this.$emit("selecttab", tab)
  }
}

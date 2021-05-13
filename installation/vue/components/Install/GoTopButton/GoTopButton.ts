/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component } from "vue-property-decorator"
import INVue from "@components/INVue"
import INIcon from "@components/INIcon"

/**
 * Gestion de la page d'informations de l'Install
 */
@Component({components: { INIcon }})
export default class GoTopButton extends INVue {
  private click(clickEvent): void {
    this.$emit('click', clickEvent)
  }
}

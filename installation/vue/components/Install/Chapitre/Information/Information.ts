/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component } from "vue-property-decorator"
import Chapitre from "@components/Chapitre"
import INLoading from "@components/INLoading"
import InformationProvider from "@providers/InformationProvider"

/**
 * Gestion de la page d'informations de l'Install
 */
@Component({components: {INLoading}})
export default class Information extends Chapitre {
  private loaded: boolean = false

  private informationDOM: string = ""

  public async load(): Promise<void> {
    this.loaded = false
    let informationDOM = await new InformationProvider().getDOM()
    this.informationDOM = informationDOM.substr(0, informationDOM.indexOf("<style"))
      + informationDOM.substr(informationDOM.indexOf("</style>") + 8)
    this.loaded = true
  }
}

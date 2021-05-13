/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import INProvider from "@providers/INProvider"
import axios from "axios"

/**
 * Provider principal de l'Install
 */
export default class PrerequisProvider extends INProvider {
  constructor () {
    super()
    this.url = "infos"
  }

  public async getDOM(): Promise<string> {
    try {
      let response = await axios.get(this.url, INProvider.getHeader())
      return response.data
    }
    catch(error) {
      throw new Error(error)
    }
  }
}
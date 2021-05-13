/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import INProvider from "@providers/INProvider"
import INVue from "@components/INVue"

/**
 * Provider principal de l'Install
 */
export default class ErrorsBufferProvider extends INProvider {
  constructor () {
    super()
    this.url = "errors/bufferStatistics"
  }
  protected translateData(data: any): object {
    let attributes = data.attributes;
    return {
      path: attributes.path,
      lastUpdate: INVue.dateToString(new Date(attributes.last_update)),
      size: attributes.size * 1,
      filesCount: attributes.files_count
    }
  }
}
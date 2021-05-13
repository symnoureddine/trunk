/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import INProvider from "@providers/INProvider"

export default class ConnexionProvider extends INProvider {
  constructor () {
    super()
    this.url = "authentication"
  }

  protected function postTraitment (data): void {

  }
}
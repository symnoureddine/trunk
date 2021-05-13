/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

// import INProvider from '@providers/INProvider'
import INProvider from "@providers/INProvider";
/**
 * Provider principal de l'Install
 */
export default class LibrairiesProvider extends INProvider {
  constructor () {
    super()
    this.url = "libraries"
  }
  protected translateData(data: any): object{
    let attributes = data.attributes
    return {
      check: attributes.check,
      countAll: attributes.count_all,
      countInstalled: attributes.count_install,
      countOld: attributes.count_old,
      libraries: attributes.libraries.map(lib => {
        return {
          name: lib.name,
          description: lib.description,
          url: lib.url,
          licenseName: lib.license.name,
          licenseLink: lib.license.link,
          distribution: lib.distribution,
          isInstalled: lib.is_installed,
          isUptodate: lib.is_uptodate
        }
      })
    }
  }
}
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import OxEventApi from "@system/OxEventApi"

/**
 * OxEventCore
 *
 * @todo : Supprimer cette classe et ses liens (api & store)
 *
 * Gestion des évènements
 */
export default class OxEventCore {
    /**
     * Initialisation des évènements du document
     */
    public initDocumentListeners (): void {
        document.addEventListener(
            "click",
            () => {
                this.documentClick()
            }
        )
        document.addEventListener(
            "scroll",
            () => {
                this.documentScroll()
            }
        )
    }

    /**
     * Execution des fonctions click du document
     */
    public documentClick (): void {
        this.launchFunctions(OxEventApi.clickEvents())
    }

    public documentScroll (): void {
        this.launchFunctions(OxEventApi.scrollEvents())
    }

    private launchFunctions (funcs: Function[] | boolean): void {
        if (!funcs) {
            return
        }
        (funcs as Function[]).forEach(
            (_function) => {
                if (typeof (_function) !== "function") {
                    return
                }
                _function()
            }
        )
    }
}

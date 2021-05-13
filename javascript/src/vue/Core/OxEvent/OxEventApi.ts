/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import OxStoreCore from "@system/OxStoreCore"

/**
 * OxEventApi
 *
 * Gestion des évènements stockés
 */
export default class OxEventApi {
    /**
     * Ajout d'une fonction au click du document
     *
     * @param Function func
     * @param boolean  onscroll Ajoute l'événement aux fonctions de scroll du document
     */
    public static addDocumentClick (func: Function, onscroll = false, singleUse = false): string {
        const identifier = "_" + Math.ceil(Math.random() * Math.pow(10, 5))
        if (singleUse) {
            const _func = func
            func = () => {
                _func()
                OxEventApi.removeDocumentClick(identifier)
            }
        }
        OxStoreCore.commit("setDocumentClick", { id: identifier, func: func })
        if (onscroll) {
            OxStoreCore.commit("setDocumentScroll", { id: identifier, func: func })
        }
        return identifier
    }

    /**
     * Retrait d'une fonction au click du document
     *
     * @param number identifier
     * @param boolean  onscroll Retire l'événement des fonctions de scroll du document
     */
    public static removeDocumentClick (identifier: string, onscroll = false): void {
        OxStoreCore.commit("removeDocumentClick", identifier)
        if (onscroll) {
            OxStoreCore.commit("removeDocumentScroll", identifier)
        }
    }

    /**
     * Récupération de la liste des fonctions au click du document
     */
    public static clickEvents (): Function[] | boolean {
        return OxStoreCore.getters.getDocumentClickEvents
    }

    /**
     * Récupération de la liste des fonctions au scroll du document
     */
    public static scrollEvents (): Function[] | boolean {
        return OxStoreCore.getters.getDocumentScrollEvents
    }
}

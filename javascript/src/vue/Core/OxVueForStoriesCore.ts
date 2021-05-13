/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Vue } from "vue-property-decorator"
import OxParametersApi from "@system/OxParametersApi"
import OxVueForTestsCore from "@system/OxVueForTestsCore"
import { AlertOpt } from "./OxAlert/OxAlertModel"

/**
 * Substitue d'OxVue pour les tests
 *
 */
@Component
export default class OxVueForStoriesCore extends OxVueForTestsCore {
    private alertApi = null

    /**
     * Traduction d'une chaine de caractère
     *
     * @param {string} key - Clef de traduction
     * @param {boolean} plural - Utilisation du pluriel
     *
     * @return {string}
     */
    protected tr (key: string, plural = false): string {
        return OxVueForStoriesCore.str(key, plural)
    }

    /**
     * Traduction d'une chaine de caractère
     *
     * @param {string} key - Clef de traduction
     * @param {boolean} plural - Utilisation du pluriel
     *
     * @return {string}
     */
    public static str (key: string, plural = false): string {
        return OxParametersApi.tr(key + (plural ? "|pl" : ""))
    }

    /**
     * Affichage d'une alerte
     * @param {string} label - Label à afficher
     * @param {Function|AlertOpt} okOptions - Options relatives au bouton "ok"
     * @param {Function|AlertOpt} nokOptions - Options relatives au bouton "non ok"
     */
    protected alert (label: string, okOptions?: Function|AlertOpt, nokOptions?: Function|AlertOpt): void {
        return
    }
}

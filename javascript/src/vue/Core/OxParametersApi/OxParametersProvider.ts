/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import OxProviderCore from "@system/OxProviderCore"
import { ApiTranslatedResponse, ApiConfigurationData } from "@system/ApiResponseModel"

/**
 * OxParametersProvider
 *
 * Provider de données pour le gestionnaire de paramètres OxParametersApi, gestionnaire de spec pour OXSpecsApi
 */
export default class OxParametersProvider extends OxProviderCore {
    /**
     * Chargement d'une configuration
     * @param {string} configuration - Clef de configuration
     *
     * @return {Promise<string|boolean>}
     */
    public static async loadConfiguration (configuration: string): Promise<string | boolean> {
        const dataConfig = (await (new OxProviderCore())
            .getApi("mediboard/configuration", { configuration: configuration }) as ApiTranslatedResponse)
        return (dataConfig.data as ApiConfigurationData).configuration
    }

    /**
     * Chargement d'une configuration d'établissement
     * @param {string} groupConfiguration - Clef de configuration
     *
     * @return {Promise<string|boolean>}
     */
    // public static async loadGroupConfiguration (groupConfiguration: string): Promise<string | boolean> {
    //     return true
    // }

    /**
     * Chargement de spécifications de champs
     * @param {string} specsUrl - Url de la ressource de specifications
     *
     * @return {Promise<Array<any>>}
     */
    /* eslint-disable  @typescript-eslint/no-explicit-any */
    public async loadSpecs (specsUrl: string): Promise<any[]> {
        return (await super.getApi(specsUrl) as { data: object[] }).data
    }
}

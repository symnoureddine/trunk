/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */
import OxProviderCore from "@system/OxProviderCore"
import { Patient } from "@oxCabinet/TammDheModel"

export default class PatientProvider extends OxProviderCore {
    /**
     * Chargement des informations d'un patient
     * @param {string} patientId - Identifiant du patient
     * @param {fieldsets} fieldsets - Liste des catégories de champs à récupérer
     *
     * @return {Promise<Patient>}
     */
    public async loadPatient (patientId: string, fieldsets?: string): Promise<Patient> {
        return (await this.getApi(
            "dossierpatient/patients/" + patientId,
            {
                fieldsets: fieldsets || ""
            }
        )).data as unknown as Patient
    }
}

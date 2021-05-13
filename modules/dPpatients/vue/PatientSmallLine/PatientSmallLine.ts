/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import OxVue from "@system/OxVue"
import { Component, Prop } from "vue-property-decorator"
import { Patient } from "@oxCabinet/TammDheModel"
import OxVicon from "@system/OxVicon"
import PatientAvatar from "@dPpatients/PatientAvatar"
import OxDate from "@system/OxDate"

/**
 * PatientSmallLine
 */
@Component({ components: { OxVicon, PatientAvatar, OxDate } })
export default class PatientSmallLine extends OxVue {
    @Prop()
    private patient!: Patient

    /**
     * Sélection de la ligne de patient
     * @param {string} patientId - Identifiant du patient
     */
    private clickPatient (patientId: string) {
        this.$emit("patientclick", patientId)
    }
}

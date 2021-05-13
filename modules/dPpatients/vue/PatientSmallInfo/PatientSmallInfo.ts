/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import OxVue from "@system/OxVue"
import { Component, Prop } from "vue-property-decorator"
import PatientAvatar from "@dPpatients/PatientAvatar"
import OxVicon from "@system/OxVicon"
import OxDate from "@system/OxDate"
import { Patient } from "@oxCabinet/TammDheModel"
import OxThemeCore from "@system/OxThemeCore"

/**
 * PatientSmallInfo
 */
@Component({ components: { PatientAvatar, OxVicon, OxDate } })
export default class PatientSmallInfo extends OxVue {
    @Prop()
    private patient!: Patient

    @Prop({ default: true })
    private showLink!: boolean

    private get initials (): string {
        if (!this.patient) {
            return ""
        }
        return (this.patient.nom ? this.patient.nom[0] : "") + (this.patient.prenom ? this.patient.prenom[0] : "")
    }

    private get fullName (): string {
        if (!this.patient || !this.patient.nom || !this.patient.prenom) {
            return ""
        }
        return this.patient.nom + " " + this.patient.prenom
    }

    private get patientSexeIcon (): string {
        if (!this.patient) {
            return "help"
        }
        return this.patient.sexe === "m" ? "male" : "female"
    }

    public get patientSexeColor (): string | undefined {
        if (!this.patient) {
            return OxThemeCore.grey
        }
        return this.patient?.sexe === "m" ? OxThemeCore.blueLight : OxThemeCore.pinkLight
    }
}

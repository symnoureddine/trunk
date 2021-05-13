/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import OxVue from "@system/OxVue"
import { Component, Prop } from "vue-property-decorator"
import { Patient } from "@oxCabinet/TammDheModel"
import PatientAvatar from "@dPpatients/PatientAvatar"
import OxChip from "@system/OxChip"

/**
 * PatientSmallText
 */
@Component({ components: { PatientAvatar, OxChip } })
export default class PatientSmallText extends OxVue {
    @Prop()
    private patient!: Patient

    @Prop({ default: 26 })
    private size!: number

    private get isBMR (): boolean {
        return this.patient._bmr_bhre_status !== undefined &&
            this.patient._bmr_bhre_status !== null &&
            "BMR+" in this.patient._bmr_bhre_status
    }

    private get isBHReC (): boolean {
        return this.patient._bmr_bhre_status !== undefined &&
            this.patient._bmr_bhre_status !== null &&
            "BHReC" in this.patient._bmr_bhre_status
    }

    private get isBHReP (): boolean {
        return this.patient._bmr_bhre_status !== undefined &&
            this.patient._bmr_bhre_status !== null &&
            "BHReP" in this.patient._bmr_bhre_status
    }

    private get isBHReR (): boolean {
        return this.patient._bmr_bhre_status !== undefined &&
            this.patient._bmr_bhre_status !== null &&
            "BHReR" in this.patient._bmr_bhre_status
    }
}

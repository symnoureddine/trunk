/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop, Watch } from "vue-property-decorator"
import OxVue from "@system/OxVue"
import OxVicon from "@system/OxVicon"
import OxVueApi from "@system/OxVueApi"
import OxThemeCore from "@system/OxThemeCore"

/**
 * PatientAvatar
 */
@Component({ components: { OxVicon } })
export default class PatientAvatar extends OxVue {
    @Prop({ default: "m" })
    private patientSexe!: "m" | "f"

    @Prop()
    private profilId!: number

    @Prop({ default: 38 })
    private size!: number

    private imgSrc = ""

    private get iconSize (): number {
        return Math.floor(this.size / 1.5 - 1)
    }

    public get patientSexeColor (): string | undefined {
        return this.patientSexe === "m" ? OxThemeCore.blueLight : OxThemeCore.pinkLight
    }

    /**
     * Initialisation de l'url de l'avatar du patient
     * @Watch profilId
     */
    @Watch("profilId")
    private async updateImgSrc () {
        this.imgSrc = (await OxVueApi.getRootUrl()) +
            "?m=files&raw=thumbnail&document_guid=CFile-" + this.profilId + "&profile=medium&crop=1"
    }

    /**
     * Montage du component
     */
    private mounted () {
        this.updateImgSrc()
    }
}

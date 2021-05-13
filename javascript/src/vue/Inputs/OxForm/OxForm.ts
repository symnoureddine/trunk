/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxVue from "@system/OxVue"

/**
 * OxForm
 *
 * Composant de Formulaire
 */
@Component
export default class OxForm extends OxVue {
    @Prop({ default: false })
    private disabled!: boolean

    @Prop({ default: false })
    private lazyValidation!: boolean

    @Prop({ default: false })
    private readonly!: boolean

    @Prop({ default: false })
    private value!: boolean

    /**
     * Validation manuelle du formulaire
     *
     * @return {boolean} La validité du formulaire
     */
    public validate (): boolean {
        /* eslint-disable  @typescript-eslint/no-explicit-any */
        const inputs = (this.$refs.form as Vue & { inputs: Array<any> }).inputs
        let firstInvalidInput

        inputs.forEach((input) => {
            if (firstInvalidInput === undefined && input && !input.valid) {
                firstInvalidInput = input
                firstInvalidInput.$el.scrollIntoView({ behavior: "smooth", block: "center", inline: "center" })
            }
        })

        return (this.$refs.form as Vue & { validate: () => boolean }).validate()
    }
}

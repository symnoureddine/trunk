/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxVue from "@system/OxVue"
import OxButton from "@system/OxButton"

/**
 * OxButton
 *
 * Composant de bouton
 */
@Component({ components: { OxButton } })
export default class OxDropDownButton extends OxVue {
    // Label du bouton
    @Prop({ default: "" })
    private label!: string

    // Style principal du bouton
    @Prop({ default: "secondary" })
    private buttonStyle!: "primary" | "secondary" | "tertiary"

    // Icone associé au bouton
    @Prop({ default: "opt" })
    private icon!: string

    // Titre du bouton (hover)
    @Prop({ default: "" })
    private title!: string

    @Prop({ default: "" })
    private customClass!: string

    @Prop({
        default: () => {
            return []
        }
    })

    private buttons!: {
        label?: string
        icon: string
    }[]

    // Dernière position de la souris lors des interractation avec le DDB
    private mouseX = 0
    private mouseY = 0

    /**
     * Récupération des classes appliquées au container OxButton
     *
     * @return {Array<string | object>}
     */
    private get buttonClasses (): (object | string)[] {
        return [
            "me-" + this.buttonStyle,
            this.icon,
            this.customClass,
            {
                notext: this.label === ""
            }
        ]
    }

    /**
     * Style dynamique appliqué au contenu du dropdown button
     */
    private get contentStyle () {
        let ddWidth = 200
        if (this.$refs.ddContent) {
            ddWidth = (this.$refs.ddContent as HTMLDivElement).offsetWidth
        }
        return {
            position: "fixed",
            left: Math.min(this.mouseX, window.innerWidth - ddWidth) + "px",
            top: this.mouseY + "px"
        }
    }

    /**
     * Affichage du contenu du dropdownbutton
     *
     * @param {Event} mouseEvent - Evenement
     */
    private toggleDropDown (mouseEvent: MouseEvent): void {
        ((this.$refs.mainButton as OxButton).$refs.button as HTMLButtonElement).classList.add("toggled")

        this.mouseX = mouseEvent.x
        this.mouseY = mouseEvent.y
    }
}

/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxVue from "@system/OxVue"
import OxVicon from "@system/OxVicon"

/**
 * OxButton
 *
 * Composant de bouton
 */
@Component({ components: { OxVicon } })
export default class OxButton extends OxVue {
    // Label du bouton
    @Prop({ default: "" })
    private label!: string

    // Style principal du bouton
    @Prop({ default: "secondary" })
    private buttonStyle!: "primary" | "secondary" | "tertiary" | "tertiary-dark"

    // Icone associé au bouton
    @Prop({ default: "" })
    private icon!: string

    // Titre du bouton (hover)
    @Prop({ default: "" })
    private title!: string

    @Prop({ default: "" })
    private customClass!: string

    @Prop({ default: "left" })
    private iconSide!: "right" | "left"

    // Réduction de la taille spécifiquement pour les boutons primary icon-only
    @Prop({ default: false })
    private smallPrimaryIconOnly!: boolean

    // Affichage du bouton en version small
    @Prop({ default: false })
    private small!: boolean

    @Prop({ default: undefined })
    private dark!: boolean

    @Prop({ default: undefined })
    private depressed!: boolean

    @Prop({ default: undefined })
    private iconButton!: boolean

    @Prop({ default: false })
    private disabled!: boolean

    @Prop({ default: false })
    private loading!: boolean

    public static SMALL_ICON_SIZE = 18
    public static DEFAULT_ICON_SIZE = 24
    public static SMALL_BUTTON_SIZE = 24

    /**
     * Couleur du bouton
     */
    private get buttonColor () {
        if (this.buttonStyle === "tertiary-dark") {
            return null
        }
        return this.buttonStyle === "tertiary" ? "secondary" : "primary"
    }

    /**
     * Largeur du bouton
     */
    private get buttonSize (): number | undefined {
        return this.smallPrimaryIconOnly ? OxButton.SMALL_BUTTON_SIZE : undefined
    }

    /**
     * Taille de l'icône du bouton
     */
    private get iconSize (): number {
        return this.smallPrimaryIconOnly ? OxButton.SMALL_ICON_SIZE : OxButton.DEFAULT_ICON_SIZE
    }

    /**
     * Remontée de l'événement click
     *
     * @param {Event} event - Evenement du click
     */
    private click (event: MouseEvent): void {
        if (this.loading) {
            return
        }
        this.$emit("click", event)
    }
}

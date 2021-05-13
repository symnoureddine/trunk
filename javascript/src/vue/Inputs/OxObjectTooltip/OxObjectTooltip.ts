/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxVue from "@system/OxVue"

/**
 * OxObjectTooltip
 *
 * Composant/Base de composant proposant le ToolTip d'objets
 */
@Component
export default class OxObjectTooltip extends OxVue {
    // Identifiant de l'objet tooltipable
    @Prop({ default: "" })
    private tooltipId!: string

    // Classe de l'objet tooltipable
    @Prop({ default: "" })
    private tooltipClass!: string

    protected _tooltipId = ""
    protected _tooltipClass = ""

    protected mounted (): void {
        this.initTooltip()
        let _el = (this.$el as HTMLDivElement)
        _el.addEventListener("mouseover", this.tooltip)
        if (_el.className.indexOf("OXToolTip") === -1) {
            _el.className += " OXToolTip"
        }
    }

    /**
     * Préparation à la mise à jour
     */
    protected beforeUpdate (): void {
        let _el = (this.$el as HTMLDivElement)
        //@ts-ignore
        if (!_el.oTooltip) {
            return
        }
        this.initTooltip()
        //@ts-ignore
        _el.oTooltip = null
        _el.removeAttribute("id")
    }

    /**
     * Initialisation des variables guid nécessaire à l'affichage de la Tooltip
     */
    protected initTooltip (): void {
        this._tooltipId = this.tooltipId
        this._tooltipClass = this.tooltipClass
    }

    /**
     * Affichage de la tooltip d'objet en fonction des paramètres d'objet enregistrés
     */
    private tooltip (): void {
        //@ts-ignore
        if (!window.ObjectTooltip) {
            return
        }
        //@ts-ignore
        window.ObjectTooltip.createEx(this.$el, (this._tooltipClass + "-" + this._tooltipId))
    }
}

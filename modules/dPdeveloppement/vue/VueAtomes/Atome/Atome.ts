/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxButton from "@system/OxButton"
import OxDate from "@system/OxDate"
import OxVue from "@system/OxVue"
import OxDropDownButton from "@system/OxDropDownButton"
import OxSpecField from "@system/OxSpecField"
import OxFieldAuto from "@system/OxFieldAuto"
import OxBeautify from "@system/OxBeautify"
import AtomeAutoObject from "@dPdeveloppement/AtomeAutoObject"
import OxFieldString from "@system/OxFieldString"
import OxFieldList from "@system/OxFieldList"
import { OxiDateProgressive } from "oxify"

/**
 * Atome
 *
 * Atome de composant comportant une gestion de propriétés
 */
@Component({
    components: {
        OxButton,
        OxDate,
        OxDropDownButton,
        OxSpecField,
        OxFieldAuto,
        AtomeAutoObject,
        OxBeautify,
        OxFieldList,
        OxFieldString,
        OxiDateProgressive
    }
})
export default class Atome extends OxVue {
    @Prop()
    private props!: object

    @Prop({ default: "" })
    private component!: string

    private mutatingProps: object = {}

    private event = {
        name: "",
        value: ""
    }

    /**
     * Liste des propriétés du composant
     *
     * @return {object}
     */
    private get propsList (): object {
        return Object.keys(this.props).map(
            (propName) => {
                return {
                    label: propName,
                    params: this.props[propName]
                }
            }
        )
    }

    /**
     * Changement de propriété
     * @param propName {string} - Nom de la propriété modifiée
     * @param propValue {string} - Valeur de la propriété
     */
    public propsOnChange (propName: string, propValue: string): void {
        this.mutatingProps[propName] = propValue
    }

    /**
     * Application des nouvelles propriétés
     */
    private applyProps (): void {
        const props = this.props
        Object.keys(this.mutatingProps).forEach(
            (_propName) => {
                props[_propName].value = this.mutatingProps[_propName]
            }
        )

        this.$emit("change", props)
    }

    /**
     * Récupération des événements du composant
     * @param eventName {string} - Nom du composant
     * @param eventObject {any} - Objet accompagnant l'événement
     */
    private catchEvent (eventName: string, eventObject: string | boolean | number | object): void {
        this.event.name = eventName
        this.event.value = eventObject.toString()
    }
}

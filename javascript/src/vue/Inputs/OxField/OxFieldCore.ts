/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop, Watch } from "vue-property-decorator"
import OxVue from "@system/OxVue"
import { OxFieldListElement } from "@system/OxFieldModel"
import OxThemeCore from "@system/OxThemeCore"

/**
 * OxField
 *
 * Composant-modèle pour les composants de champ
 */
@Component
export default class OxFieldCore extends OxVue {
    // Label du champ
    @Prop({ default: "" })
    protected label!: string

    @Prop({ default: "" })
    protected title!: string

    @Prop({ default: "" })
    protected value!: string | boolean

    @Prop({ default: false })
    protected disabled!: boolean

    @Prop({ default: false })
    protected notNull!: boolean

    @Prop({
        default: () => {
            return []
        }
    })
    protected list!: OxFieldListElement[] | string[]

    @Prop({ default: "" })
    protected message!: string

    @Prop({ default: "" })
    protected icon!: string

    @Prop({ default: false })
    protected onPrimary!: boolean

    @Prop({ default: false })
    protected showLoading!: boolean

    @Prop({
        default: () => {
            return []
        }
    })

    protected rules!: Array<Function>

    private fieldId!: string

    protected mutatedValue: string | boolean = ""

    /**
     * Synchronisation de la valeur du champ
     */
    @Watch("value")
    protected updateMutatedValue (): void {
        this.mutatedValue = this.value ? this.value.toString() : ""
    }

    /**
     * Classes de base du conteneur initial
     *
     * @return {Object}
     */
    protected get fieldClasses (): object {
        return {
            labelled: this.label !== "",
            "not-null": this.notNull
        }
    }

    /**
     * Liste d'éléments sélectionnable
     *
     * @return {Array<OxFieldListElement>}
     */
    public get viewList (): OxFieldListElement[] {
        return (this.list as OxFieldListElement[]).map(
            (_el: OxFieldListElement | string) => {
                return typeof (_el) === "string" ? { name: _el, value: _el } : _el
            }
        )
    }

    /**
     * Couleur de champ
     *
     * @return {string|undefined}
     */
    protected get fieldColor (): string | undefined {
        return this.onPrimary ? OxThemeCore.whiteMediumEmphasis : undefined
    }

    /**
     * Couleur de fond du champ
     *
     * @return {string|undefined}
     */
    protected get fieldBG () {
        return this.onPrimary ? OxThemeCore.primary400 : undefined
    }

    /**
     * Message d'information courant du champ
     *
     * @return {string}
     */
    protected get hint (): string {
        return this.message.toString()
    }

    /**
     * Label du champ
     *
     * @return {string}
     */
    protected get labelComputed (): string {
        return this.label + (this.notNull && this.label ? " *" : "")
    }

    /**
     * Composant créé
     */
    protected created (): void {
        this.fieldId = Math.ceil(Math.random() * Math.pow(10, 10)).toString()
    }

    /**
     * Composant monté
     */
    protected mounted (): void {
        this.updateMutatedValue()
    }

    /**
     * Remontée du changement de valeur du champ
     * @param {string | boolean} value - Nouvelle valeur
     */
    protected change (value: string | boolean): void {
        this.$emit("change", value)
    }

    /**
     * Changement de la valeur courante
     * @param {string | boolean} value - Nouvelle valeur
     */
    protected changeValue (value: string | boolean): void {
        this.mutatedValue = value
        this.change(value)
    }

    /**
     * Génération des rules en fonction des rules des specs et des props
     *
     * @return {Array}
     */
    protected get fieldRules (): Array<Function> {
        const rules = this.rules
        if (this.notNull) {
            rules.push(v => (!!v || OxVue.str("TammDhePatientSelection-missing-field")))
        }
        return rules
    }
}

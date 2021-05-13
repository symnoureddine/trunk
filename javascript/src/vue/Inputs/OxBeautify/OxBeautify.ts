/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxVue from "@system/OxVue"
import OxDate from "@system/OxDate"

/**
 * Mise en forme automatique de valeurs, dates, booleens, tableaux, etc...
 */
@Component
export default class OxBeautify extends OxVue {
    @Prop()
    private value!: string | boolean | number | object

    // todo: Date as date
    @Prop()
    private voidLabel!: string

    /**
     * La valeur est une date
     *
     * @return {boolean}
     */
    private get isDate (): boolean {
        return this.isString && OxDate.isDate(this.value as string)
    }

    /**
     * La valeur est un temps
     *
     * @return {boolean}
     */
    private get isTime (): boolean {
        return this.isString && OxDate.isTime(this.value as string)
    }

    /**
     * La valeur est un booléen
     *
     * @return {boolean}
     */
    private get isBoolean (): boolean {
        return typeof (this.value) === "boolean"
    }

    /**
     * La valeur est une chaine de caractères
     *
     * @return {boolean}
     */
    private get isString (): boolean {
        return typeof (this.value) === "string" && !this.isNumber
    }

    /**
     * La valeur est un nombre
     *
     * @return {boolean}
     */
    private get isNumber (): boolean {
        return typeof (this.value) === "number" || parseFloat(this.value as string).toString() === this.value
    }

    /**
     * La valeur est vide
     *
     * @return {boolean}
     */
    private get isVoid (): boolean {
        return (this.isString && ["", " "].indexOf(this.value as string) >= 0) ||
            typeof (this.value) === "undefined" ||
            this.value === null ||
            (typeof (this.value) === "object" && !this.value)
    }

    /**
     * La valeur est un tableau
     *
     * @return {boolean}
     */
    private get isArray (): boolean {
        return typeof (this.value) === "object" && Array.isArray(this.value)
    }

    /**
     * La valeur est un tableau vide
     *
     * @return {boolean}
     */
    private get isEmpty (): boolean {
        return this.isArray && (this.value as unknown as object[]).length === 0
    }

    /**
     * Récupération d'une version affichable de la valeur
     *
     * @return {string}
     */
    private get view (): string {
        if (this.isBoolean) {
            return this.value ? this.tr("Yes") : this.tr("No")
        }
        if (this.isVoid) {
            return this.voidLabel ? this.voidLabel : "(" + this.tr("OxBeautify-void") + ")"
        }
        if (this.isEmpty) {
            return this.voidLabel ? this.voidLabel : "(" + this.tr("OxBeautify-empty") + ")"
        }
        if (this.isNumber) {
            return this.value.toString()
        }
        if (this.isDate || this.isTime) {
            const dateFormat = OxDate.getAutoFormat(this.value as string)
            let dateView = this.value as string
            if (this.isDate) {
                dateView = OxDate.formatStatic(new Date(this.value as string), dateFormat)
            }
            if (dateFormat === "time" || dateFormat === "datetime") {
                dateView = OxDate.beautifyTime(dateView)
            }
            return dateView
        }
        return this.value.toString()
    }

    /**
     * Classes dédiées au conteneur principal
     *
     * @return {Object}
     */
    private get containerClasses (): object {
        return {
            void: this.isVoid || this.isEmpty
        }
    }
}

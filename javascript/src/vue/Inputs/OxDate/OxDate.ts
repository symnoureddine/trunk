/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component, Prop } from "vue-property-decorator"
import OxVue from "@system/OxVue"

/**
 * OxDate
 *
 * Gestion de l'affichage de la date
 */
@Component
export default class OxDate extends OxVue {
    // Date de base
    @Prop()
    private date!: Date

    // Format de la date
    @Prop({ default: "date" })
    private mode!: "month" | "day" | "date" | "datetime" | "time" | "completeday"

    @Prop()
    private dateString!: string

    /**
     * Récupération de la date (type Date)
     *
     * @return {Date|false}
     */
    private get getDate (): Date | false {
        if (this.date) {
            return this.date
        }
        if (!this.dateString) {
            return false
        }
        return new Date(this.dateString)
    }

    /**
     * Mise en forme de la date
     *
     * @return {string}
     */
    private get dateFormat (): string {
        let format = ""
        if (!this.getDate) {
            return format
        }
        if (this.mode === "day" || this.mode === "completeday") {
            format += ["L", "M", "M", "J", "V", "S", "D"][this.getDate.getDay()] + " " + OxDate.completeNum(this.getDate.getDate(), 2) + " "
        }
        if (this.mode === "month" || this.mode === "completeday") {
            format += [
                this.tr("OxDate-ShortMonth-Janvier"),
                this.tr("OxDate-ShortMonth-Fevrier"),
                this.tr("OxDate-ShortMonth-Mars"),
                this.tr("OxDate-ShortMonth-Avril"),
                this.tr("OxDate-ShortMonth-Mai"),
                this.tr("OxDate-ShortMonth-Juin"),
                this.tr("OxDate-ShortMonth-Juillet"),
                this.tr("OxDate-ShortMonth-Aout"),
                this.tr("OxDate-ShortMonth-Septembre"),
                this.tr("OxDate-ShortMonth-Octobre"),
                this.tr("OxDate-ShortMonth-Novembre"),
                this.tr("OxDate-ShortMonth-Decembre")][this.getDate.getMonth()] + ". "
        }
        if (this.mode === "date" || this.mode === "datetime") {
            format += OxDate.completeNum(this.getDate.getDate(), 2) + "/" + OxDate.completeNum(this.getDate.getMonth() + 1, 2) + "/"
        }
        if (this.mode === "date" || this.mode === "datetime" || this.mode === "month" || this.mode === "completeday") {
            format += OxDate.completeNum(this.getDate.getFullYear(), 4)
        }
        if (this.mode === "datetime") {
            format += " "
        }
        if (this.mode === "time" || this.mode === "datetime") {
            format += OxDate.completeNum(this.getDate.getHours(), 2) + ":" + OxDate.completeNum(this.getDate.getMinutes(), 2)
        }
        return format
    }

    /**
     * Complete un string de zéros
     * @param {string} char - Texte de base
     * @param {number} nb - Nombre de caractères cible
     *
     * @return {string}
     */
    private static completeNum (char: number, nb: number): string {
        let charString = char.toString()
        while (charString.length < nb) {
            charString = "0" + charString
        }
        return charString
    }

    /**
     * Retourne la date au format YYYY-MM-DD
     * @param {Date} date - Date à décomposer
     *
     * @return {string}
     */
    public static getYMD (date: Date = new Date()): string {
        return OxDate.completeNum(date.getFullYear(), 4) + "-" +
            OxDate.completeNum((date.getMonth() + 1), 2) + "-" +
            OxDate.completeNum(date.getDate(), 2)
    }

    /**
     * Retourne la date au format HH:ii
     * @param {Date} date - Date à décomposer
     *
     * @return {string}
     */
    public static getHm (date: Date): string {
        return OxDate.completeNum(date.getHours(), 2) + ":" +
            OxDate.completeNum(date.getMinutes(), 2)
    }

    /**
     * Retourne la date au format HH:ii:ss
     * @param {Date} date - Date à décomposer
     *
     * @return {string}
     */
    public static getHms (date: Date): string {
        return OxDate.completeNum(date.getHours(), 2) + ":" +
            OxDate.completeNum(date.getMinutes(), 2) + ":" +
            OxDate.completeNum(date.getSeconds(), 2)
    }

    /**
     * Retourne la date au format YYYY-MM-DD HH:ii
     * @param {Date} date - Date à décomposer
     *
     * @return {string}
     */
    public static getYMDHm (date: Date): string {
        return OxDate.getYMD(date) + " " + OxDate.getHm(date)
    }

    /**
     * Retourne la date au format YYYY-MM-DD HH:ii
     * @param {Date} date - Date à décomposer
     *
     * @return {string}
     */
    public static getYMDHms (date: Date): string {
        return OxDate.getYMD(date) + " " + OxDate.getHms(date)
    }

    /**
     * Utilisation du dateFormat en static
     * @param {Date} date - Date à décomposer
     * @param {string} mode - Format de date
     *
     * @return {string}
     */
    public static formatStatic (date: Date, mode: "month" | "day" | "date" | "datetime" | "time" | "completeday"): string {
        const tmpDate = new OxDate()
        tmpDate.date = date
        tmpDate.mode = mode

        return tmpDate.dateFormat
    }

    /**
     * Différence entre deux date
     * @param {Date} from - Date minimale
     * @param {Date} to - Date maximale
     *
     * @return {Object}
     */
    public static diff (from: Date, to: Date):
        {
            sec: number
            min: number
            hou: number
            day: number
            string: string
        } {
        /* eslint-disable  @typescript-eslint/no-explicit-any */
        let dateDiff = ((to as any) - (from as any)) / 1000
        const diff = {
            sec: 0,
            min: 0,
            hou: 0,
            day: 0,
            string: ""
        }

        diff.day = Math.floor(dateDiff / (60 * 60 * 24))
        dateDiff -= diff.day * (60 * 60 * 24)

        diff.hou = Math.floor(dateDiff / (60 * 60))
        dateDiff -= diff.hou * (60 * 60)

        diff.min = Math.floor(dateDiff / (60))
        dateDiff -= diff.min * (60)

        diff.sec = dateDiff

        // Affichage des jours
        if (diff.day > 0) {
            diff.string += diff.day + " " + new OxDate().tr("day") + " "
        }
        // Affichage des heures
        if (diff.hou > 0) {
            diff.string += ((diff.hou < 10) ? "0" : "") + diff.hou + "h"
        }
        // Affichage des minutes
        diff.string += ((diff.hou !== 0 && diff.min < 10) ? "0" : "") + diff.min + ((diff.hou === 0) ? "min" : "")

        return diff
    }

    /**
     * Test sur la viabilité d'une chaine de caractère en date
     * @param {string} date - Texte à tester
     *
     * @return {boolean}
     */
    public static isDate (date: string): boolean {
        return ((!!date.match(/([12]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))$/)) ||
            !!date.match(/([12]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])) [012][0-9](:[0-5][0-9]){2}$/)) &&
            (new Date(date)).toString() !== "Invalid Date"
    }

    /**
     * Test sur la viabilité d'une chaine de caractère en temps
     * @param {string} time - Texte à tester
     *
     * @return {boolean}
     */
    public static isTime (time: string): boolean {
        return !!time.match(/^([0-9]{2}:){2}[0-9]{2}$/)
    }

    /**
     * Récupération d'un mode d'affichage automatique en fonction d'une date (string)
     * @param {string} date - Label de date à tester
     *
     * @return {string}
     */
    public static getAutoFormat (date: string): "month" | "day" | "date" | "datetime" | "time" | "completeday" {
        if (date.length === 10) {
            return "date"
        }
        if (date.length === 5 || date.length === 8) {
            return "time"
        }
        return "datetime"
    }

    /**
     * Retourne une date au format HH:ii:ss au format HHhii
     * @param {string} date - Temps à transformer
     *
     * @return {string}
     */
    public static beautifyTime (date: string): string {
        if (date.length === 8 || date.length === 19) {
            date = date.slice(0, -3)
        }
        return date.replace(":", "h")
    }
}

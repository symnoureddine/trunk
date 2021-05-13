/**
 * @package Mediboard\Planning
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import OxDate from "@system/OxDate"

/**
 * SejourDurationHelper
 * Classe utilitaire de gestion de la durée d'un séjour
 */
export default class SejourDurationHelper {
    private entree = ""
    private hours = 0
    private nights = 0
    private sortie = ""

    /**
     * Récupération du nombre d'heures
     *
     * @return {number}
     */
    public getHours (): number {
        return this.hours
    }

    /**
     * Récupération du nombre de nuits
     *
     * @return {number}
     */
    public getNights (): number {
        return this.nights
    }

    /**
     * Récupération de la date de sortie
     *
     * @return {string}
     */
    public getSortie (): string {
        if (!this.entree) {
            return ""
        }
        const entree = new Date(this.entree)
        entree.setDate(entree.getDate() + this.nights)
        entree.setHours(entree.getHours() + this.hours)
        return OxDate.getYMDHms(entree)
    }

    /**
     * Mise à jour de l'entrée
     * @param {string} entree - Date d'entrée
     *
     * @return {SejourDurationHelper}
     */
    public updateEntree (entree: string): SejourDurationHelper {
        this.entree = entree
        return this
    }

    /**
     * Mise à jour du nombre d'heures
     * @param {number} hours - Nombre d'heures
     *
     * @return {SejourDurationHelper}
     */
    public updateHours (hours: string | number): SejourDurationHelper {
        this.hours = parseInt(hours.toString())
        return this
    }

    /**
     * Mise à jour du nombre de nuits
     * @param {number} nights - Nombre de nuits
     *
     * @return {SejourDurationHelper}
     */
    public updateNights (nights: string | number): SejourDurationHelper {
        this.nights = parseInt(nights.toString())
        return this
    }

    /**
     * Mise à jour de la date de sortie
     * @param {string} sortie - Date de sortie
     *
     * @return {SejourDurationHelper}
     */
    public updateSortie (sortie: string): SejourDurationHelper {
        if (!this.entree || !sortie) {
            return this
        }
        this.sortie = sortie
        const diff = OxDate.diff(new Date(this.entree), new Date(this.sortie))
        this.nights = diff.day
        this.hours = diff.hou
        return this
    }
}

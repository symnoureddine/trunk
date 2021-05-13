/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import OxBeautify from "@system/OxBeautify"
import { OxiTest } from "oxify"

/**
 * Test pour la classe OxBeautify
 */
export default class OxBeautifyTest extends OxiTest {
    protected component = OxBeautify

    /**
     * @inheritDoc
     */
    protected vueComponent (props: object = {}): OxBeautify {
        return super.vueComponent(props) as OxBeautify
    }

    /**
     * Test reconnaissance date
     */
    public testDateRecognition (): void {
        this.assertTrue(
            this.privateCall(
                this.vueComponent(
                    {
                        value: "2020-01-01"
                    }
                ),
                "isDate"
            )
        )
    }

    /**
     * Test reconnaissance heure
     */
    public testTimeRecognition (): void {
        this.assertTrue(
            this.privateCall(
                this.vueComponent(
                    {
                        value: "10:15:58"
                    }
                ),
                "isTime"
            )
        )
    }


    /**
     * Test reconnaissance booleen d'un boolean
     */
    public testBooleanRecognition (): void {
        this.assertTrue(
            this.privateCall(
                this.vueComponent(
                    {
                        value: true
                    }
                ),
                "isBoolean"
            )
        )
    }

    /**
     * Test de non reconnaissance booleen d'un string
     */
    public testStringNonBooleanRecognition (): void {
        this.assertFalse(
            this.privateCall(
                this.vueComponent(
                    {
                        value: ""
                    }
                ),
                "isBoolean"
            )
        )
    }

    /**
     * Test de non reconnaissance booleen d'un number
     */
    public testNumberNonBooleanRecognition (): void {
        this.assertFalse(
            this.privateCall(
                this.vueComponent(
                    {
                        value: 0
                    }
                ),
                "isBoolean"
            )
        )
    }


    /**
     * Test reconnaissance chaine de caractères
     */
    public testStringRecognition (): void {
        this.assertTrue(
            this.privateCall(
                this.vueComponent(
                    {
                        value: "Some string"
                    }
                ),
                "isString"
            )
        )
    }

    /**
     * Test reconnaissance nombre
     */
    public testNumberRecognition (): void {
        this.assertTrue(
            this.privateCall(
                this.vueComponent(
                    {
                        value: 75
                    }
                ),
                "isNumber"
            )
        )
    }

    /**
     * Test reconnaissance nombre (en chaine de caractères)
     */
    public testStringNumberRecognition (): void {
        this.assertTrue(
            this.privateCall(
                this.vueComponent(
                    {
                        value: "75"
                    }
                ),
                "isNumber"
            )
        )
    }

    /**
     * Test reconnaissance chaine de caractères vide
     */
    public testEmptyStringRecognition (): void {
        this.assertTrue(
            this.privateCall(
                this.vueComponent(
                    {
                        value: ""
                    }
                ),
                "isVoid"
            )
        )
    }

    /**
     * Test reconnaissance espace
     */
    public testSpaceStringRecognition (): void {
        this.assertTrue(
            this.privateCall(
                this.vueComponent(
                    {
                        value: ""
                    }
                ),
                "isVoid"
            )
        )
    }

    /**
     * Test reconnaissance valeur indéfinie
     */
    public testUndefinedRecognition (): void {
        this.assertTrue(
            this.privateCall(
                this.vueComponent(
                    {}
                ),
                "isVoid"
            )
        )
    }


    /**
     * Test reconnaissance tableau
     */
    public testArrayRecognition (): void {
        this.assertTrue(
            this.privateCall(
                this.vueComponent(
                    {
                        value: [
                            "first item",
                            "second item"
                        ]
                    }
                ),
                "isArray"
            )
        )
    }

    /**
     * Test reconnaissance tableau vide
     */
    public testEmptyArrayRecognition (): void {
        this.assertTrue(
            this.privateCall(
                this.vueComponent(
                    {
                        value: []
                    }
                ),
                "isEmpty"
            )
        )
    }

    /**
     * Test utilisation du label de valeur vide
     */
    public testVoidLabel (): void {
        const label = "Test"
        this.assertEqual(
            this.privateCall(this.vueComponent({ value: "", voidLabel: label }), "view"),
            label
        )
    }

    /**
     * Test utilisation du label de valeur empty
     */
    public testEmptyLabel (): void {
        const label = "Test empty"
        this.assertEqual(
            this.privateCall(this.vueComponent({ value: [], voidLabel: label }), "view"),
            label
        )
    }

    /**
     * Test affichage d'une date
     */
    public testViewDate (): void {
        const beautify = this.vueComponent({ value: "2021-04-30" })
        this.assertEqual(this.privateCall(beautify, "view"), "30/04/2021")
    }
}

(new OxBeautifyTest()).launchTests()

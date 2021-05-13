/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */
import {OxiTest} from "oxify"
import OxButton from "@system/OxButton"
import {Wrapper} from "@vue/test-utils"

export default class OxButtonTest extends OxiTest {
    component = OxButton

    /**
     * @inheritDoc
     */
    protected mountComponent (props: object): Wrapper<OxButton> {
        return super.mountComponent(props) as Wrapper<OxButton>
    }

    /**
     * Test click du bouton
     */
    public testClick (): void {
        const button = this.mountComponent({})
        this.privateCall(button.vm, "click", {})
        this.assertTrue(button.emitted("click"))
    }

    /**
     * Test click pendant chargement
     */
    public testClickWhileLoading (): void {
        const button = this.mountComponent({ loading: true})
        this.privateCall(button.vm, "click", {})
        this.assertFalse(button.emitted("click"))
    }

    /**
     * Test couleur du bouton primary
     */
    public testButtonPrimaryColor (): void {
        const button = this.vueComponent({ buttonStyle: "primary" })
        this.assertEqual(this.privateCall(button, "buttonColor"), "primary")
    }

    /**
     * Test couleur du bouton secondary
     */
    public testButtonSecondaryColor (): void {
        const button = this.vueComponent({ buttonStyle: "secondary" })
        this.assertEqual(this.privateCall(button, "buttonColor"), "primary")
    }

    /**
     * Test couleur du bouton tertiary
     */
    public testButtonTertiaryColor (): void {
        const button = this.vueComponent({ buttonStyle: "tertiary" })
        this.assertEqual(this.privateCall(button, "buttonColor"), "secondary")
    }

    /**
     * Test couleur du bouton tertiary dark
     */
    public testButtonTertiaryDarkColor (): void {
        const button = this.vueComponent({ buttonStyle: "tertiary-dark" })
        this.assertNull(this.privateCall(button, "buttonColor"))
    }

    /**
     * Test la taille par défaut de l'icone
     */
    public testDefaultIconSize (): void {
        const button = this.vueComponent({ smallPrimaryIconOnly: false })
        this.assertEqual(this.privateCall(button, "iconSize"), OxButton.DEFAULT_ICON_SIZE)
    }

    /**
     * Test la taille de l'icone en small primary icon-only
     */
    public testSmallIconSize (): void {
        const button = this.vueComponent({ smallPrimaryIconOnly: true })
        this.assertEqual(this.privateCall(button, "iconSize"), OxButton.SMALL_ICON_SIZE)
    }

    /**
     * Test la taille du bouton small primary icon-only
     */
    public testSmallButtonSize (): void {
        const button = this.vueComponent({ smallPrimaryIconOnly: true })
        this.assertEqual(this.privateCall(button, "buttonSize"), OxButton.SMALL_BUTTON_SIZE)
    }

    /**
     * Test la taille du bouton par défaut
     */
    public testDefaultButtonSize (): void {
        const button = this.vueComponent({ smallPrimaryIconOnly: false })
        this.assertUndefined(this.privateCall(button, "buttonSize"))
    }
}

(new OxButtonTest()).launchTests()

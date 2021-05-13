/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { OxiTest } from "oxify"
import { Wrapper } from "@vue/test-utils"
import ObjectCardEdit from "@system/ObjectCardEdit"

export default class ObjectCardEditTest extends OxiTest {
    protected component = ObjectCardEdit

    /**
     * @inheritDoc
     */
    protected mountComponent (props: object): Wrapper<ObjectCardEdit> {
        return super.mountComponent(props) as Wrapper<ObjectCardEdit>
    }

    /**
     * @inheritDoc
     */
    protected vueComponent (props: object): ObjectCardEdit {
        return super.vueComponent(props) as ObjectCardEdit
    }

    /**
     * Test d'affichage du titre de carte
     */
    public testTitle (): void {
        const title = "Custom Title"
        this.assertEqual(
            this.mountComponent({ title: title }).find(".ObjectCardEdit-title").text(),
            title
        )
    }
}

(new ObjectCardEditTest()).launchTests()

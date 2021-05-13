/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import Menu from "@components/Menu"
import Chapitre from "@components/Chapitre"
import Configuration from "@components/Configuration"
import Installation from "@components/Installation"
import Information from "@components/Information"
import Prerequis from "@components/Prerequis"
import ErreurLog from "@components/ErreurLog"
import Connexion from "@components/Connexion"
import {Prop, Component} from "vue-property-decorator"
import INVue from "@components/INVue"
import GoTopButton from "@components/GoTopButton"
import INProvider from "@providers/INProvider"

/**
 * Composant racine de l'Install : Gestion et monitoring
 */
@Component({
  components: {
    Menu,
    Chapitre,
    Configuration,
    Installation,
    Information,
    Prerequis,
    ErreurLog,
    Connexion,
    GoTopButton
  }
})
export default class Install extends INVue {
  @Prop({default: "."})
  private endPoint!: string;

  private defaultChapter: string = "Prerequis"
  private selectedChapter: string = ""
  private connected: boolean =false

  private compact: object ={
    Prerequis: false,
    Installation: false,
    Configuration: false,
    Information: false,
    ErreurLog: false
  }

  private async chapterClick(chapter: string): Promise<void>{
    this.selectedChapter = chapter

    let chapterContainer = this.getChapter(chapter)
    await chapterContainer.load()
  }

  private getChapter(chapter?: string): Chapitre{
    chapter = chapter ? chapter : this.selectedChapter
    let chapterContainer = <Chapitre>this.$refs[chapter]
    if (!chapterContainer) {
      return new Chapitre()
    }
    return chapterContainer
  }

  private get menuClassName(): object {
    return {
      "Install-menuCompact": this.getCompact
    }
  }

  private get contentClassName(): object {
    return {
      "Install-contentCompact": this.getCompact
    }
  }

  private disconnect(): void {
    this.connected = false
    this.selectedChapter = "Connexion"
  }

  private async connect(): Promise<void> {
    this.connected = true
    this.selectedChapter = this.defaultChapter
    await this.chapterClick(this.selectedChapter)
    await (<Menu>this.$refs["Menu"]).loadFlags()
  }

  private mounted(): void {
    this.selectedChapter = this.connected ? this.defaultChapter : "Connexion"
  }

  private setCompact(compact: boolean): void {
    this.compact[this.selectedChapter] = compact
  }

  private get getCompact(): boolean {
    return this.compact[this.selectedChapter]
  }

  private goTop(): void {
    this.emptyScroll(0)
  }

  private emptyScroll(scrollTo?: number): void {
    let chaptersContainer = this.getChapter(this.selectedChapter === "ErreurLog" ? "ErreurLog" : "Chapitres")
    chaptersContainer.scroll({target: chaptersContainer.$el}, scrollTo)
  }

  private created(): void {
    document.title = this.tr('AssistantInstallation')
    window.addEventListener(
      "beforeunload",
      (event) => {
        (event || window.event).returnValue = ""
        return ""
      }
    );
    INProvider.initEndPoint(this.endPoint);
  }
}

/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { Component } from "vue-property-decorator"
import Atome from "@dPdeveloppement/Atome"
import OxVue from "@system/OxVue"
import TammSejourProvider from "@oxCabinet/TammSejourProvider"
import AtomeProvider from "@dPdeveloppement/AtomeProvider"
import AtomeObjectProvider from "@dPdeveloppement/AtomeObjectProvider"
import OxSpecField from "@system/OxSpecField"
import OxButton from "@system/OxButton"
import { OxiDateProgressive } from "oxify"
import { NotificationDelay } from "@system/OxNotifyModel"

/**
 * Vue listant un ensemble d'atomes de base
 *
 * Gestion de l'affichage de la date
 */
@Component({ components: { Atome, OxButton, OxiDateProgressive } })
export default class VueAtomes extends OxVue {
    protected async mounted (): Promise<void> {
        await (new TammSejourProvider()).initSejourSpecs("urgences,admission,sortie,annulation,placement,repas,cotation,default")
        const oxSpecField = ((this.$refs.oxspecfield_atome as Atome).$refs.component_OxSpecField as OxSpecField)
        oxSpecField.updateLocalSpec()
    }

    private oxBeautifyProps = {
        value: {
            value: "test",
            type: "string"
        },
        voidLabel: {
            value: "Label vide",
            type: "string"
        }
    }

    private oxfieldProps = {
        value: {
            value: "test",
            type: "string"
        },
        type: {
            value: "string",
            type: "list",
            list: [
                {
                    name: "boolean",
                    value: "boolean"
                },
                {
                    name: "string",
                    value: "string"
                },
                {
                    name: "date",
                    value: "date"
                },
                {
                    name: "datetime",
                    value: "datetime"
                },
                {
                    name: "time",
                    value: "time"
                },
                {
                    name: "list",
                    value: "list"
                }
            ]
        },
        label: {
            value: "Label",
            type: "string"
        },
        list: {
            value: [
                { name: "Valeur 1", value: 1 },
                { name: "Valeur 2", value: 2 },
                { name: "Valeur 3", value: 3 },
                { name: "Valeur 4", value: 4 }
            ],
            type: "none"
        },
        message: {
            value: "",
            type: "string"
        },
        state: {
            value: "info",
            type: "list",
            list: ["info", "error", "success"]
        },
        icon: {
            value: "",
            type: "string"
        }
    }

    private oxdateProps = {
        date: {
            value: new Date(),
            type: "datetime"
        },
        mode: {
            value: "day",
            type: "list",
            list: [
                {
                    name: "month",
                    value: "month"
                },
                {
                    name: "day",
                    value: "day"
                },
                {
                    name: "date",
                    value: "date"
                },
                {
                    name: "datetime",
                    value: "datetime"
                },
                {
                    name: "time",
                    value: "time"
                },
                {
                    name: "completeday",
                    value: "completeday"
                }
            ]
        }
    }

    private oxbuttonProps = {
        label: {
            value: "Click",
            type: "string"
        },
        buttonStyle: {
            value: "primary",
            type: "list",
            list: [
                {
                    name: "primary",
                    value: "primary"
                },
                {
                    name: "secondary",
                    value: "secondary"
                },
                {
                    name: "tertiary",
                    value: "tertiary"
                }
            ]
        },
        icon: {
            value: "refresh",
            type: "string"
        },
        title: {
            value: "Click on the button",
            type: "string"
        },
        customClass: {
            value: "",
            type: "string"
        },
        iconSide: {
            value: "right",
            type: "list",
            list: ["right", "left"]
        }
    }

    private oxdropdownbuttonProps = {
        label: {
            value: "Click",
            type: "string"
        },
        buttonStyle: {
            value: "primary",
            type: "list",
            list: [
                {
                    name: "primary",
                    value: "primary"
                },
                {
                    name: "secondary",
                    value: "secondary"
                },
                {
                    name: "tertiary",
                    value: "tertiary"
                }
            ]
        },
        icon: {
            value: "tick",
            type: "string"
        },
        title: {
            value: "Click on the button",
            type: "string"
        },
        customClass: {
            value: "",
            type: "string"
        },
        buttons: {
            value: [
                {
                    label: "Bouton #1",
                    icon: "tick"
                },
                {
                    label: "Bouton #2",
                    icon: "open"
                }
            ],
            type: "none"
        }
    }

    private oxspecfieldProps = {
        resource: {
            value: "sejour",
            type: "none"
        },
        field: {
            value: "type",
            type: "list",
            list: [
                {
                    name: "reanimation",
                    value: "reanimation"
                },
                {
                    name: "UHCD",
                    value: "UHCD"
                },
                {
                    name: "last_UHCD",
                    value: "last_UHCD"
                },
                {
                    name: "entree_prevue",
                    value: "entree_prevue"
                },
                {
                    name: "entree_reelle",
                    value: "entree_reelle"
                },
                {
                    name: "entree",
                    value: "entree"
                },
                {
                    name: "entree_preparee",
                    value: "entree_preparee"
                },
                {
                    name: "entree_preparee_date",
                    value: "entree_preparee_date"
                },
                {
                    name: "entree_modifiee",
                    value: "entree_modifiee"
                },
                {
                    name: "mode_entree",
                    value: "mode_entree"
                },
                {
                    name: "provenance",
                    value: "provenance"
                },
                {
                    name: "date_entree_reelle_provenance",
                    value: "date_entree_reelle_provenance"
                },
                {
                    name: "transport",
                    value: "transport"
                },
                {
                    name: "type_pec",
                    value: "type_pec"
                },
                {
                    name: "pec_accueil",
                    value: "pec_accueil"
                },
                {
                    name: "pec_service",
                    value: "pec_service"
                },
                {
                    name: "pec_ambu",
                    value: "pec_ambu"
                },
                {
                    name: "rques_pec_ambu",
                    value: "rques_pec_ambu"
                },
                {
                    name: "sortie_prevue",
                    value: "sortie_prevue"
                },
                {
                    name: "sortie_reelle",
                    value: "sortie_reelle"
                },
                {
                    name: "sortie",
                    value: "sortie"
                },
                {
                    name: "sortie_preparee",
                    value: "sortie_preparee"
                },
                {
                    name: "sortie_modifiee",
                    value: "sortie_modifiee"
                },
                {
                    name: "mode_sortie",
                    value: "mode_sortie"
                },
                {
                    name: "commentaires_sortie",
                    value: "commentaires_sortie"
                },
                {
                    name: "destination",
                    value: "destination"
                },
                {
                    name: "transport_sortie",
                    value: "transport_sortie"
                },
                {
                    name: "rques_transport_sortie",
                    value: "rques_transport_sortie"
                },
                {
                    name: "reception_sortie",
                    value: "reception_sortie"
                },
                {
                    name: "completion_sortie",
                    value: "completion_sortie"
                },
                {
                    name: "annule",
                    value: "annule"
                },
                {
                    name: "motif_annulation",
                    value: "motif_annulation"
                },
                {
                    name: "rques_annulation",
                    value: "rques_annulation"
                },
                {
                    name: "chambre_seule",
                    value: "chambre_seule"
                },
                {
                    name: "pathologie",
                    value: "pathologie"
                },
                {
                    name: "septique",
                    value: "septique"
                },
                {
                    name: "isolement",
                    value: "isolement"
                },
                {
                    name: "isolement_date",
                    value: "isolement_date"
                },
                {
                    name: "isolement_fin",
                    value: "isolement_fin"
                },
                {
                    name: "raison_medicale",
                    value: "raison_medicale"
                },
                {
                    name: "television",
                    value: "television"
                },
                {
                    name: "handicap",
                    value: "handicap"
                },
                {
                    name: "nuit_convenance",
                    value: "nuit_convenance"
                },
                {
                    name: "aide_organisee",
                    value: "aide_organisee"
                },
                {
                    name: "repas_diabete",
                    value: "repas_diabete"
                },
                {
                    name: "repas_sans_sel",
                    value: "repas_sans_sel"
                },
                {
                    name: "repas_sans_residu",
                    value: "repas_sans_residu"
                },
                {
                    name: "repas_sans_porc",
                    value: "repas_sans_porc"
                },
                {
                    name: "facture",
                    value: "facture"
                },
                {
                    name: "recuse",
                    value: "recuse"
                },
                {
                    name: "facturable",
                    value: "facturable"
                },
                {
                    name: "cloture_activite_1",
                    value: "cloture_activite_1"
                },
                {
                    name: "cloture_activite_4",
                    value: "cloture_activite_4"
                },
                {
                    name: "frais_sejour",
                    value: "frais_sejour"
                },
                {
                    name: "reglement_frais_sejour",
                    value: "reglement_frais_sejour"
                },
                {
                    name: "type",
                    value: "type"
                },
                {
                    name: "convalescence",
                    value: "convalescence"
                },
                {
                    name: "ATNC",
                    value: "ATNC"
                },
                {
                    name: "confirme",
                    value: "confirme"
                },
                {
                    name: "libelle",
                    value: "libelle"
                },
                {
                    name: "hospit_de_jour",
                    value: "hospit_de_jour"
                },
                {
                    name: "circuit_ambu",
                    value: "circuit_ambu"
                }
            ]
        }
    }

    private oxspecfield2Props = {
        label: {
            value: "Label",
            type: "string"
        },
        customSpecs: {
            value: "str",
            type: "list",
            list: [
                {
                    name: "str",
                    value: "str"
                },
                {
                    name: "text",
                    value: "text"
                },
                {
                    name: "bool",
                    value: "bool"
                },
                {
                    name: "date",
                    value: "date"
                },
                {
                    name: "dateTime",
                    value: "dateTime"
                },
                {
                    name: "time",
                    value: "time"
                }
            ],
            transformer: (value) => {
                return {
                    type: value,
                    notNull: true
                }
            }
        }
    }

    private oxfieldautoSpecs = {
        provider: {
            value: new AtomeProvider(),
            type: "none"
        },
        object: {
            value: false,
            type: "none"
        },
        label: {
            value: "",
            type: "string"
        },
        message: {
            value: "",
            type: "string"
        },
        state: {
            value: "info",
            type: "list",
            list: ["info", "error", "success"]
        },
        icon: {
            value: "",
            type: "string"
        }
    }

    private oxfieldautoobjectSpecs = {
        provider: {
            value: new AtomeObjectProvider(),
            type: "none"
        },
        object: {
            value: true,
            type: "none"
        },
        itemText: {
            value: "mainText",
            type: "none"
        },
        itemId: {
            value: "id",
            type: "none"
        },
        label: {
            value: "",
            type: "string"
        },
        message: {
            value: "",
            type: "string"
        },
        state: {
            value: "info",
            type: "list",
            list: ["info", "error", "success"]
        },
        icon: {
            value: "",
            type: "string"
        }
    }

    /**
     * Mise à jour d'une propriété injectée
     * @param prop {string} - Nom de la propriété à mettre à jour
     * @param newProps {object} - Nouvelle valeur à appliquer
     */
    private updateProp (prop: string, newProps: object): void {
        this[prop] = newProps
    }

    private selectDate (date): void {
        console.log(date)
    }

    private date = ""

    private clear (): void {
        console.log("clearing date")
    }

    /**
     * Ajoute une notification
     */
    private addNotification (): void {
        this.notifyInfo("Information")
    }

    /**
     * Ajoute une notification
     */
    private addWarning (): void {
        this.notifyWarning("Warning with delay", { delay: NotificationDelay.short })
    }

    /**
     * Ajoute une notification
     */
    private addError (): void {
        this.notifyError(
            "Error with callback",
            {
                delay: NotificationDelay.short,
                callback: () => {
                    console.log("Notify removed")
                }
            }
        )
    }

    /**
     * Affiche uen alerte
     */
    private showAlert1 (): void {
        this.alert("Alerte avec uniquement un message")
    }

    /**
     * Affiche uen alerte
     */
    private showAlert2 (): void {
        this.alert(
            "Alerte avec un callback en okOpt",
            () => {
                console.log("ok")
            }
        )
    }

    /**
     * Affiche uen alerte
     */
    private showAlert3 (): void {
        // this.alert("Test Lorem Ipsum coco oui en effet")
        this.alert(
            "Alerte avec option complexe en okOpt, et callback en nokOpt",
            {
                label: "Oui",
                callback: () => {
                    console.log("ok")
                }
            },
            () => {
                console.log("not ok")
            }
        )
    }
}

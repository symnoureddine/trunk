/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import OxProviderCore from "@system/OxProviderCore"

export default class AtomeProvider extends OxProviderCore {
    public async getAutocomplete (filter?: string): Promise<{ id: number; text: string }[]> {
        await new Promise((resolve) => {
            setTimeout(resolve, 250)
        })
        const names = [
            {
                id: 1,
                text: "Kalysta"
            },
            {
                id: 2,
                text: "Leony"
            },
            {
                id: 3,
                text: "Neissa"
            },
            {
                id: 4,
                text: "Thea"
            },
            {
                id: 5,
                text: "Nadine"
            },
            {
                id: 6,
                text: "Ny"
            },
            {
                id: 7,
                text: "Valina"
            },
            {
                id: 8,
                text: "Albrecht"
            },
            {
                id: 9,
                text: "Sharlen"
            },
            {
                id: 10,
                text: "O'bryan"
            },
            {
                id: 11,
                text: "Ljiljana"
            },
            {
                id: 12,
                text: "Elyesse"
            },
            {
                id: 13,
                text: "Soade"
            },
            {
                id: 14,
                text: "Habsatou"
            },
            {
                id: 15,
                text: "Auberie"
            },
            {
                id: 16,
                text: "Ladi"
            },
            {
                id: 17,
                text: "Adria"
            },
            {
                id: 18,
                text: "Yveric"
            },
            {
                id: 19,
                text: "Ebtisem"
            },
            {
                id: 20,
                text: "Claude-Henri"
            },
            {
                id: 21,
                text: "Abdelkader"
            },
            {
                id: 22,
                text: "Gracia"
            },
            {
                id: 23,
                text: "Nacer-Eddine"
            },
            {
                id: 24,
                text: "N'namou"
            },
            {
                id: 25,
                text: "Venda"
            },
            {
                id: 26,
                text: "Paul-Yves"
            },
            {
                id: 27,
                text: "Davidson"
            },
            {
                id: 28,
                text: "Mathys"
            },
            {
                id: 29,
                text: "Mikel"
            },
            {
                id: 30,
                text: "Fatiah"
            },
            {
                id: 31,
                text: "Benedetta"
            },
            {
                id: 32,
                text: "Pierre-Franck"
            },
            {
                id: 33,
                text: "Yagmur"
            },
            {
                id: 34,
                text: "Magomed"
            },
            {
                id: 35,
                text: "Lorane"
            },
            {
                id: 36,
                text: "Valina"
            },
            {
                id: 37,
                text: "Jessica"
            },
            {
                id: 38,
                text: "Sharlen"
            },
            {
                id: 39,
                text: "Sakura"
            },
            {
                id: 40,
                text: "Hanifi"
            },
            {
                id: 41,
                text: "Rossella"
            },
            {
                id: 42,
                text: "Chehrazad"
            },
            {
                id: 43,
                text: "Nouba"
            },
            {
                id: 44,
                text: "Shalva"
            },
            {
                id: 45,
                text: "Phillip"
            },
            {
                id: 46,
                text: "Isadora"
            },
            {
                id: 47,
                text: "Haya-Mouchka"
            },
            {
                id: 48,
                text: "Swanie"
            },
            {
                id: 49,
                text: "Jonathann"
            },
            {
                id: 50,
                text: "Shanee"
            },
            {
                id: 51,
                text: "Lorelai"
            },
            {
                id: 52,
                text: "Ilham"
            },
            {
                id: 53,
                text: "Amil"
            },
            {
                id: 54,
                text: "Jaufret"
            },
            {
                id: 55,
                text: "Floriane"
            },
            {
                id: 56,
                text: "Jorry"
            },
            {
                id: 57,
                text: "Farhan"
            },
            {
                id: 58,
                text: "Marie-Hermine"
            },
            {
                id: 59,
                text: "Yvanie"
            },
            {
                id: 60,
                text: "Bleona"
            },
            {
                id: 61,
                text: "Fredrick"
            },
            {
                id: 62,
                text: "Anouchka"
            },
            {
                id: 63,
                text: "Pernille"
            },
            {
                id: 64,
                text: "Theotine"
            },
            {
                id: 65,
                text: "Balal"
            },
            {
                id: 66,
                text: "Zenab"
            },
            {
                id: 67,
                text: "Leroy"
            },
            {
                id: 68,
                text: "Kennedy"
            },
            {
                id: 69,
                text: "David-Vincent"
            },
            {
                id: 70,
                text: "Enio"
            },
            {
                id: 71,
                text: "Ladji"
            },
            {
                id: 72,
                text: "Aurianna"
            },
            {
                id: 73,
                text: "Ireine"
            },
            {
                id: 74,
                text: "Cristobal"
            },
            {
                id: 75,
                text: "Phuong"
            },
            {
                id: 76,
                text: "Dele"
            },
            {
                id: 77,
                text: "Ryann"
            },
            {
                id: 78,
                text: "Molene"
            },
            {
                id: 79,
                text: "Tigane"
            },
            {
                id: 80,
                text: "Mendie"
            },
            {
                id: 81,
                text: "Loam"
            },
            {
                id: 82,
                text: "Chryslaine"
            },
            {
                id: 83,
                text: "Sarika"
            },
            {
                id: 84,
                text: "Steren"
            },
            {
                id: 85,
                text: "Aladji"
            },
            {
                id: 86,
                text: "Napoleon"
            },
            {
                id: 87,
                text: "Jaufret"
            },
            {
                id: 88,
                text: "Julieta"
            },
            {
                id: 89,
                text: "Guy-Emmanuel"
            },
            {
                id: 90,
                text: "Sabine"
            }
        ]
        if (!filter) {
            return names
        }
        return names.filter((_name) => {
            return _name.text.toUpperCase().indexOf(filter.toUpperCase()) >= 0
        })
    }
}

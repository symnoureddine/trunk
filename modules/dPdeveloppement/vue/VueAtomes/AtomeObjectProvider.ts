/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import OxProviderCore from "@system/OxProviderCore"

interface NameInterface {
    id: number
    icon: string
    subText: string
    mainText: string
}

export default class AtomeObjectProvider extends OxProviderCore {
    public async getAutocomplete (filter?: string): Promise<NameInterface[]> {
        await new Promise((resolve) => {
            setTimeout(resolve, 250)
        })
        const names = [
            {
                id: 1,
                icon: "user",
                subText: "text",
                mainText: "Kalysta"
            },
            {
                id: 2,
                icon: "user",
                subText: "text",
                mainText: "Leony"
            },
            {
                id: 3,
                icon: "user",
                subText: "text",
                mainText: "Neissa"
            },
            {
                id: 4,
                icon: "user",
                subText: "text",
                mainText: "Thea"
            },
            {
                id: 5,
                icon: "user",
                subText: "text",
                mainText: "Nadine"
            },
            {
                id: 6,
                icon: "user",
                subText: "text",
                mainText: "Ny"
            },
            {
                id: 7,
                icon: "user",
                subText: "text",
                mainText: "Valina"
            },
            {
                id: 8,
                icon: "user",
                subText: "text",
                mainText: "Albrecht"
            },
            {
                id: 9,
                icon: "user",
                subText: "text",
                mainText: "Sharlen"
            },
            {
                id: 10,
                icon: "user",
                subText: "text",
                mainText: "O'bryan"
            },
            {
                id: 11,
                icon: "user",
                subText: "text",
                mainText: "Ljiljana"
            },
            {
                id: 12,
                icon: "user",
                subText: "text",
                mainText: "Elyesse"
            },
            {
                id: 13,
                icon: "user",
                subText: "text",
                mainText: "Soade"
            },
            {
                id: 14,
                icon: "user",
                subText: "text",
                mainText: "Habsatou"
            },
            {
                id: 15,
                icon: "user",
                subText: "text",
                mainText: "Auberie"
            },
            {
                id: 16,
                icon: "user",
                subText: "text",
                mainText: "Ladi"
            },
            {
                id: 17,
                icon: "user",
                subText: "text",
                mainText: "Adria"
            },
            {
                id: 18,
                icon: "user",
                subText: "text",
                mainText: "Yveric"
            },
            {
                id: 19,
                icon: "user",
                subText: "text",
                mainText: "Ebtisem"
            },
            {
                id: 20,
                icon: "user",
                subText: "text",
                mainText: "Claude-Henri"
            },
            {
                id: 21,
                icon: "user",
                subText: "text",
                mainText: "Abdelkader"
            },
            {
                id: 22,
                icon: "user",
                subText: "text",
                mainText: "Gracia"
            },
            {
                id: 23,
                icon: "user",
                subText: "text",
                mainText: "Nacer-Eddine"
            },
            {
                id: 24,
                icon: "user",
                subText: "text",
                mainText: "N'namou"
            },
            {
                id: 25,
                icon: "user",
                subText: "text",
                mainText: "Venda"
            },
            {
                id: 26,
                icon: "user",
                subText: "text",
                mainText: "Paul-Yves"
            },
            {
                id: 27,
                icon: "user",
                subText: "text",
                mainText: "Davidson"
            },
            {
                id: 28,
                icon: "user",
                subText: "text",
                mainText: "Mathys"
            },
            {
                id: 29,
                icon: "user",
                subText: "text",
                mainText: "Mikel"
            },
            {
                id: 30,
                icon: "user",
                subText: "text",
                mainText: "Fatiah"
            },
            {
                id: 31,
                icon: "user",
                subText: "text",
                mainText: "Benedetta"
            },
            {
                id: 32,
                icon: "user",
                subText: "text",
                mainText: "Pierre-Franck"
            },
            {
                id: 33,
                icon: "user",
                subText: "text",
                mainText: "Yagmur"
            },
            {
                id: 34,
                icon: "user",
                subText: "text",
                mainText: "Magomed"
            },
            {
                id: 35,
                icon: "user",
                subText: "text",
                mainText: "Lorane"
            },
            {
                id: 36,
                icon: "user",
                subText: "text",
                mainText: "Valina"
            },
            {
                id: 37,
                icon: "user",
                subText: "text",
                mainText: "Jessica"
            },
            {
                id: 38,
                icon: "user",
                subText: "text",
                mainText: "Sharlen"
            },
            {
                id: 39,
                icon: "user",
                subText: "text",
                mainText: "Sakura"
            },
            {
                id: 40,
                icon: "user",
                subText: "text",
                mainText: "Hanifi"
            },
            {
                id: 41,
                icon: "user",
                subText: "text",
                mainText: "Rossella"
            },
            {
                id: 42,
                icon: "user",
                subText: "text",
                mainText: "Chehrazad"
            },
            {
                id: 43,
                icon: "user",
                subText: "text",
                mainText: "Nouba"
            },
            {
                id: 44,
                icon: "user",
                subText: "text",
                mainText: "Shalva"
            },
            {
                id: 45,
                icon: "user",
                subText: "text",
                mainText: "Phillip"
            },
            {
                id: 46,
                icon: "user",
                subText: "text",
                mainText: "Isadora"
            },
            {
                id: 47,
                icon: "user",
                subText: "text",
                mainText: "Haya-Mouchka"
            },
            {
                id: 48,
                icon: "user",
                subText: "text",
                mainText: "Swanie"
            },
            {
                id: 49,
                icon: "user",
                subText: "text",
                mainText: "Jonathann"
            },
            {
                id: 50,
                icon: "user",
                subText: "text",
                mainText: "Shanee"
            },
            {
                id: 51,
                icon: "user",
                subText: "text",
                mainText: "Lorelai"
            },
            {
                id: 52,
                icon: "user",
                subText: "text",
                mainText: "Ilham"
            },
            {
                id: 53,
                icon: "user",
                subText: "text",
                mainText: "Amil"
            },
            {
                id: 54,
                icon: "user",
                subText: "text",
                mainText: "Jaufret"
            },
            {
                id: 55,
                icon: "user",
                subText: "text",
                mainText: "Floriane"
            },
            {
                id: 56,
                icon: "user",
                subText: "text",
                mainText: "Jorry"
            },
            {
                id: 57,
                icon: "user",
                subText: "text",
                mainText: "Farhan"
            },
            {
                id: 58,
                icon: "user",
                subText: "text",
                mainText: "Marie-Hermine"
            },
            {
                id: 59,
                icon: "user",
                subText: "text",
                mainText: "Yvanie"
            },
            {
                id: 60,
                icon: "user",
                subText: "text",
                mainText: "Bleona"
            },
            {
                id: 61,
                icon: "user",
                subText: "text",
                mainText: "Fredrick"
            },
            {
                id: 62,
                icon: "user",
                subText: "text",
                mainText: "Anouchka"
            },
            {
                id: 63,
                icon: "user",
                subText: "text",
                mainText: "Pernille"
            },
            {
                id: 64,
                icon: "user",
                subText: "text",
                mainText: "Theotine"
            },
            {
                id: 65,
                icon: "user",
                subText: "text",
                mainText: "Balal"
            },
            {
                id: 66,
                icon: "user",
                subText: "text",
                mainText: "Zenab"
            },
            {
                id: 67,
                icon: "user",
                subText: "text",
                mainText: "Leroy"
            },
            {
                id: 68,
                icon: "user",
                subText: "text",
                mainText: "Kennedy"
            },
            {
                id: 69,
                icon: "user",
                subText: "text",
                mainText: "David-Vincent"
            },
            {
                id: 70,
                icon: "user",
                subText: "text",
                mainText: "Enio"
            },
            {
                id: 71,
                icon: "user",
                subText: "text",
                mainText: "Ladji"
            },
            {
                id: 72,
                icon: "user",
                subText: "text",
                mainText: "Aurianna"
            },
            {
                id: 73,
                icon: "user",
                subText: "text",
                mainText: "Ireine"
            },
            {
                id: 74,
                icon: "user",
                subText: "text",
                mainText: "Cristobal"
            },
            {
                id: 75,
                icon: "user",
                subText: "text",
                mainText: "Phuong"
            },
            {
                id: 76,
                icon: "user",
                subText: "text",
                mainText: "Dele"
            },
            {
                id: 77,
                icon: "user",
                subText: "text",
                mainText: "Ryann"
            },
            {
                id: 78,
                icon: "user",
                subText: "text",
                mainText: "Molene"
            },
            {
                id: 79,
                icon: "user",
                subText: "text",
                mainText: "Tigane"
            },
            {
                id: 80,
                icon: "user",
                subText: "text",
                mainText: "Mendie"
            },
            {
                id: 81,
                icon: "user",
                subText: "text",
                mainText: "Loam"
            },
            {
                id: 82,
                icon: "user",
                subText: "text",
                mainText: "Chryslaine"
            },
            {
                id: 83,
                icon: "user",
                subText: "text",
                mainText: "Sarika"
            },
            {
                id: 84,
                icon: "user",
                subText: "text",
                mainText: "Steren"
            },
            {
                id: 85,
                icon: "user",
                subText: "text",
                mainText: "Aladji"
            },
            {
                id: 86,
                icon: "user",
                subText: "text",
                mainText: "Napoleon"
            },
            {
                id: 87,
                icon: "user",
                subText: "text",
                mainText: "Jaufret"
            },
            {
                id: 88,
                icon: "user",
                subText: "text",
                mainText: "Julieta"
            },
            {
                id: 89,
                icon: "user",
                subText: "text",
                mainText: "Guy-Emmanuel"
            },
            {
                id: 90,
                icon: "user",
                subText: "text",
                mainText: "Sabine"
            }
        ]
        if (!filter) {
            return names
        }
        return names.filter((_name) => {
            return (_name.subText.toUpperCase().indexOf(filter.toUpperCase()) >= 0 ||
                _name.mainText.toUpperCase().indexOf(filter.toUpperCase()) >= 0)
        })
    }
}

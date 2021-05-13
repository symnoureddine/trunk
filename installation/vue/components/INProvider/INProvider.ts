/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import axios from "axios"
import Api from "@api/Api"

/**
 * Provider principal de l'Install
 */
export default class INProvider {
  protected url: string = ""
  protected static getHeader(getData: object = {}) {
    return {
      headers: {
        "Authorization": "Basic " + Api.state.credential
      },
      params: getData
    }
  }
  private static getEndPoint() {
    return Api.state.endPoint + "/"
  }
  public async getDataAndLink(
    link: string = "",
    getData: object = {},
    noLinks: boolean = false,
    raw: boolean =false
  ): Promise<object|boolean> {
    try {
      let response = await axios.get(link ? link : (INProvider.getEndPoint() + this.url), INProvider.getHeader(getData))
      if (raw) {
        return response
      }
      let data = this.translateData(response.data.data)
      if (noLinks) {
        return data
      }
      return {
        data: data,
        links: response.data.links,
        meta: response.data.meta
      }
    }
    catch(error) {
      if (error.response) {
        return {
          data: false,
          status: error.response.status,
          message: error.response.message
        }
      }
      throw new Error(error)
      return false
    }
  }

  protected translateData(data: object): object {
    return data
  }

  public genSortParam(sortField: string): object {
    if (sortField === "") {
      return {}
    }
    let _field = sortField[0] !== "-" ? sortField : sortField.substr(1)
    sortField = (sortField[0] === "-" ? "-" : "") + this.getFieldApi(_field)
    return {
      sort: sortField
    }
  }

  protected getFieldApi(field: string): string {
    return field
  }

  public getData(link: string = "", getData: object = {}): Promise<any> {
    return this.getDataAndLink(link, getData, true)
  }
  public getRaw(link: string = "", getData: object = {}): Promise<any> {
    return this.getDataAndLink(link, getData, false, true)
  }

  protected postTraitment(data: any): void {
  }

  public static async initEndPoint(endPoint: string): Promise<boolean> {
    Api.commit("setEndPoint", endPoint)
  }
}
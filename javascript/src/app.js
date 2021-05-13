import Vue from 'vue'
import {polyfill} from 'es6-promise'
import OxVueApi from "@system/OxVueApi"
import OxVueWrap from "@system/OxVueWrap"
import vuetify from "@system/OxVuetifyCore"
import {polyfillLoader} from 'polyfill-io-feature-detection'
import Components from './chunks'

window.initVueRoots = (container) => {
  let vueRoots = container
    .querySelectorAll('.vue-root')
  if (vueRoots.length === 0) {
    return;
  }

  polyfillLoader({
    "features":    "Promise",
    "onCompleted": polyfill
  });
  if (window.NodeList && !NodeList.prototype.forEach) {
    NodeList.prototype.forEach = Array.prototype.forEach
  }

  OxVueApi.init(Preferences, locales, App.config.external_url, "api/")

  vueRoots.forEach(
    function (element) {
      let vueComponent = element.getAttribute('vue-component')
      if (!vueComponent || !Components[vueComponent]) {
        // Escaping the non-component containers
        return false
      }
      // Initializing the container id
      while (!element.id) {
        let tmpId = 'vue_container_' + Math.ceil(Math.random() * (Math.pow(10, 10)))
        element.id = document.getElementById(tmpId) ? false : tmpId
      }
      let vueProps = ""
      for (let i = 0; i < element.attributes.length; i++){
        let _attribute = element.attributes[i]
        let _attributeName = _attribute.nodeName
        if (_attributeName.indexOf("vue-") !== 0 || _attributeName === "vue-root") {
          continue
        }
        vueProps += _attributeName.replace("vue-", "") + "='" + _attribute.nodeValue + "' "
      }
      new Vue(
        {
          vuetify:    vuetify,
          el:         '#' + element.id,
          template:   '<OxVueWrap><Comp ' + vueProps + '/></OxVueWrap>',
          components: {
            Comp: Components[vueComponent],
            OxVueWrap
          }
        }
      )
    }
  );
}

document.addEventListener(
  'readystatechange',
  (event) => {
    if (document.readyState !== 'complete') {
      return false;
    }
    initVueRoots(document)
  }
)

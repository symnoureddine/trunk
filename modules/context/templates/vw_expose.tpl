{{*
 * @package Mediboard\Context
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<div class="small-info">{{tr}}mod-context-description{{/tr}}</div>

<div class="small-warning">{{tr}}mod-context-warning_use{{/tr}}</div>

<script>
  Context = {
    callurl: null,
    tokenurl: null,
    followurl: null,

    refresh: function(context) {
      // Clear and disable inputs
      $(context).select('input').each(function(input) {
        input.clear().disable();
      })

      // Disable view buttons
      $(context).select('button').each(function(button) {
        button.disable();
      })

      // Enable radios and inner inputs and buttons
      $$('input[type=radio]').each(function(radio) {
        radio.enable();
        if (radio.checked) {
          $(context).select('button').each(function(button) {
            button.enable();
          })

          radio.up('div').select('div input').each(function(input) {
            input.enable();
          })
        }
      })
    },

    show: function(form, view) {
      var params = {
        m: 'context',
        a: 'call',
        view: view
      }

      var form = getForm(form);
      $A(form.elements).each(function(input) {
        if (input.type == 'text' && input.value) {
          params[input.name] = input.value;
        }
      })

      params['login'] = '{{$app->_ref_user->_user_username}}:<password>';

      // Call params and URL
      var call_params = '';
      $H(params).each(function(param) {
        call_params += param.key + ' = ' + param.value + '\n';
      })
      $V('call-params', call_params);

      this.callurl = new Url();
      this.callurl.mergeParams(params);
      $V('call-url', decodeURIComponent(this.callurl.makeAbsolute()));

      delete this.callurl.oParams['login'];

      // Token params and URL
      delete params['a'];
      params['raw'] = 'tokenize';
      params['token_username'] = '<token_username>';
      var token_params = '';
      $H(params).each(function(param) {
        token_params += param.key + ' = ' + param.value + '\n';
      })
      $V('token-params', token_params);

      this.tokenurl = new Url();
      this.tokenurl.mergeParams(params);
      $V('token-url', decodeURIComponent(this.tokenurl.makeAbsolute()));

      delete this.tokenurl.oParams['login'];

      Modal.open('show-context', {showClose: true, title:'Appel contextuel'});
    },

    callModal: function() {
      this.callurl.modal();
    },

    callOpen: function() {
      this.callurl.open();
    },

    tokenize: function() {
      this.tokenurl.addElement($('token-username'));
      this.tokenurl.requestJSON(function(response) {
        $V('token-response', JSON.stringify(response));
        Context.followurl = new Url();
        Context.followurl.addParam('token', response.token);
        $V('follow-url', response.code ? decodeURIComponent(Context.followurl.makeAbsolute()) : null);
        $('follow-button').disabled = !response.code
      });
    },

    follow: function() {
      this.followurl.redirect();
    }
  }

  Main.add(Context.refresh.curry('patient-context'));
  Main.add(Context.refresh.curry('sejour-context'));

</script>

<div id="show-context" style="display: none;">

  <fieldset>
    <legend>{{tr}}context-open_direct{{/tr}}</legend>
    <label for="call-params">{{tr}}Parameters{{/tr}}</label>:
    <textarea id="call-params" rows="6"></textarea>
    <label for="call-url">{{tr}}common-URL{{/tr}}</label>:
    <textarea id="call-url" rows="2"></textarea>
    <div>
      <button class="search me-tertiary" onclick="Context.callModal();">{{tr}}context-open_modale{{/tr}}</button>
      <button class="link me-tertiary" onclick="Context.callOpen();">{{tr}}context-open_onglet{{/tr}}</button>
    </div>
  </fieldset>

  <fieldset>
    <legend>{{tr}}context-token{{/tr}}</legend>
    <label for="token-params">{{tr}}Parameters{{/tr}}</label>:
    <textarea id="token-params" rows="6"></textarea>
    <label for="token-url">{{tr}}context-url_for_token{{/tr}}</label>:
    <textarea id="token-url" rows="2"></textarea>
    <label for="token-username">{{tr}}User{{/tr}}</label>:
    <input id="token-username" name="token_username" type="text" />
    <div>
      <button class="new me-tertiary" onclick="Context.tokenize();">{{tr}}context-get_token_user{{/tr}}</button>
    </div>
    <label for="token-response">{{tr}}context-response{{/tr}}</label>
    <textarea id="token-response" rows="2"></textarea>
    <label for="follow-url">{{tr}}context-url_token{{/tr}}</label>:
    <textarea id="follow-url" rows="2"></textarea>
    <div>
      <button class="link me-tertiary" id="follow-button" disabled onclick="Context.follow();">
        {{tr}}context-redirect_destination{{/tr}}
      </button>
    </div>
  </fieldset>
</div>

<table class="main me-align-auto" style="width: 99%;">
  <tr>

    <!-- Contexte patient -->
    <td style="width: 50%; vertical-align: top;">
      <fieldset id="patient-context">
        <legend>{{tr}}context-views_patient{{/tr}}</legend>

        <form name="patient-context" method="post">

          {{tr}}context-choose_contexte_patient{{/tr}}<br/><br/>
          <div>
            <label>
              <input name="patient-context-type" type="radio" value="IPP" onclick="Context.refresh('patient-context')">
              {{tr}}context-search_by_ipp{{/tr}}
            </label>

            <div style="padding-left: 2em;">
              <input type="text" placeholder="IPP" name="ipp" value=""/>
            </div>
          </div>

          <div>
            <label>
              <input name="patient-context-type" value="traits" type="radio" onclick="Context.refresh('patient-context')">
              {{tr}}context-search_by_traits{{/tr}}<br/>
            </label>

            <div style="padding-left: 2em;">
              <input type="text" placeholder="Nom" name="name" value=""/><br/>
              <input type="text" placeholder="Prénom" name="firstname" value=""/><br/>
              <input type="text" placeholder="Naissance AAAA-MM-JJ" name="birthdate" value=""/><br/>
            </div>
          </div>

        </form>

        <br/>{{tr}}context-view_options{{/tr}}<br/><br/>

        <div style="padding-left: 2em;">
          <button class="search me-tertiary" onclick="Context.show('patient-context', 'patient');">
            {{tr}}context-fiche_patient{{/tr}}
          </button><br />
          <button class="search me-tertiary" onclick="Context.show('patient-context', 'full_patient');">
            {{tr}}dPpatients-CPatient-Dossier_complet{{/tr}}
          </button><br />
          <button class="search me-tertiary" onclick="Context.show('patient-context', 'sejour');">
            {{tr}}CSejour.create{{/tr}}
          </button><br />
          <button class="search me-tertiary" onclick="Context.show('patient-context', 'intervention');">
            {{tr}}COperation.create{{/tr}}
          </button><br />
          <button class="search me-tertiary" onclick="Context.show('patient-context', 'documents');">
            {{tr}}mod-dPpatients-tab-ajax_add_doc{{/tr}}
          </button><br />
        </div>
      </fieldset>
    </td>

    <!-- Contexte séjour -->
    <td style="width: 50%; vertical-align: top;">
      <fieldset id="sejour-context">
        <legend>{{tr}}context-views_sejour{{/tr}}</legend><br/>

        <form name="sejour-context" method="post">
          <div>
            <label>
              <input name="sejour-context-type" type="radio" onclick="Context.refresh('sejour-context')">
              {{tr}}context-search_by_nda{{/tr}}
            </label>

            <div style="padding-left: 2em;">
              <input type="text" placeholder="NDA" name="nda" value=""/>
            </div>
          </div>
        </form>

        <br/>{{tr}}context-view_options{{/tr}}<br/><br/>

        <div style="padding-left: 2em;">
          <button class="search me-tertiary me-dark" onclick="Context.show('sejour-context', 'soins');">
            {{tr}}CSejour-action-Folder care{{/tr}}
          </button><br />
          <button class="search me-tertiary me-dark" onclick="Context.show('sejour-context', 'prescription_pre_admission');">
            {{tr}}context-prescription_pre_admission{{/tr}}
          </button><br />
          <button class="search me-tertiary me-dark" onclick="Context.show('sejour-context', 'prescription');">
            {{tr}}context-prescription{{/tr}}
          </button><br />
          <button class="search me-tertiary me-dark" onclick="Context.show('sejour-context', 'prescription_sortie');">
            {{tr}}context-prescription_sortie{{/tr}}
          </button><br />
          <button class="search me-tertiary me-dark" onclick="Context.show('sejour-context', 'labo'        );">
            {{tr}}context-labo{{/tr}}
          </button><br />
          <button class="search me-tertiary me-dark" onclick="Context.show('sejour-context', 'ecap_ssr');">
            {{tr}}context-ecap_ssr{{/tr}}
          </button><br />
        </div>
      </fieldset>
    </td>
  </tr>
</table>


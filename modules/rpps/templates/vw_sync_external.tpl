{{*
 * @package Mediboard\Rpps
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<table class="main tbl">
  <tr>
    <th>
      {{tr}}CPersonneExercice{{/tr}}
    </th>
  </tr>
  <tr>
    <td>
      <div class="progressBarModern"
           title="{{$avancement_personne_exercice.sync}} / {{$avancement_personne_exercice.total}} ({{$avancement_personne_exercice.pct}}%)">
        <div class="bar bar-{{$avancement_personne_exercice.threshold}}" style="width: {{$avancement_personne_exercice.width}}%">
          <div class="progress">{{$avancement_personne_exercice.pct}}%</div>
          <div class="values">
            {{$avancement_personne_exercice.sync}} / {{$avancement_personne_exercice.total}}
          </div>
        </div>
      </div>
    </td>
  </tr>

  <tr>
    <th>
      {{tr}}CSavoirFaire{{/tr}}
    </th>
  </tr>
  <tr>
    <td>
      <div class="progressBarModern"
           title="{{$avancement_savoir_faire.sync}} / {{$avancement_savoir_faire.total}} ({{$avancement_savoir_faire.pct}}%)">
        <div class="bar bar-{{$avancement_savoir_faire.threshold}}" style="width: {{$avancement_savoir_faire.width}}%">
          <div class="progress">{{$avancement_savoir_faire.pct}}%</div>
          <div class="values">
            {{$avancement_savoir_faire.sync}} / {{$avancement_savoir_faire.total}}
          </div>
        </div>
      </div>
    </td>
  </tr>

  <tr>
    <th>
      {{tr}}CDiplomeAutorisationExercice{{/tr}}
    </th>
  </tr>
  <tr>
    <td>
      <div class="progressBarModern"
           title="{{$avancement_diplome.sync}} / {{$avancement_diplome.total}} ({{$avancement_diplome.pct}}%)">
        <div class="bar bar-{{$avancement_diplome.threshold}}" style="width: {{$avancement_diplome.width}}%">
          <div class="progress">{{$avancement_diplome.pct}}%</div>
          <div class="values">
            {{$avancement_diplome.sync}} / {{$avancement_diplome.total}}
          </div>
        </div>
      </div>
    </td>
  </tr>
</table>


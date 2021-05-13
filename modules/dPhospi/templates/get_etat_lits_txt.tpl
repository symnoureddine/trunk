{{*
 * @package Mediboard\Hospi
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}
{{foreach from=$list_affectations item="affectation"}}
  {{$affectation.nom}};{{$affectation.prenom}};{{$affectation.id}};{{if $detail_lit && $nom_jf}}{{$affectation.nom_naissance}};{{/if}}{{$affectation.service}};{{if !$detail_lit}}{{$affectation.chambre}};{{/if}}{{if $detail_lit}}{{$affectation.chambre_nom}};{{$affectation.lit_nom}};{{/if}}{{$affectation.lit}};{{if $id_chambre}}{{$affectation.chambre}};{{/if}}{{$affectation.sexe}};{{$affectation.naissance}};{{$affectation.date_entree}};{{$affectation.heure_entree}};{{$affectation.date_sortie}};{{$affectation.heure_sortie}};{{if $service}}{{$affectation.libelle_service}};{{/if}}{{if $NDA}}{{$affectation.NDA}};{{/if}}{{$affectation.type}}
{{/foreach}}
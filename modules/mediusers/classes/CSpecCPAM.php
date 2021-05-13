<?php

/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Mediusers;

use Ox\Core\Cache;
use Ox\Core\CMbString;
use Ox\Core\CModelObject;

/**
 * The CDiscipline Class
 */
class CSpecCPAM extends CModelObject
{

    /** @var string */
    public const RESOURCE_NAME = 'specialty';

    /** @var array The list of specialities */
  protected static $specialities = array(
    1 => "M�decine g�n�rale",
    2 => "Anesthesie - R�animation",
    3 => "Cardiologie",
    4 => "Chirurgie g�n�rale",
    5 => "Dermatologie et V�n�rologie",
    6 => "Radiologie",
    7 => "Gyn�cologie obst�trique",
    8 => "Gastro-Ent�rologie et H�patologie",
    9 => "M�decine interne",
    10 => "Neuro-Chirurgie",
    11 => "Oto-Rhino-Laryngologie",
    12 => "P�diatrie",
    13 => "Pneumologie",
    14 => "Rhumatologie",
    15 => "Ophtalmologie",
    16 => "Chirurgie urologique",
    17 => "Neuro-Psychiatrie",
    18 => "Stomatologie",
    19 => "Chirurgie dentaire",
    20 => "R�animation m�dicale",
    21 => "Sage-femme",
    22 => "Sp�cialiste en m�decin g�n�rale (Dipl�m�)",
    23 => "Sp�cialiste en m�decin g�n�rale (Ordre)",
    24 => "Infirmier",
    26 => "Masseur Kin�sith�rapeute",
    27 => "Pedicure Podologue",
    28 => "Orthophoniste",
    29 => "Orthoptiste",
    30 => "Laboratoire d'analyses m�dicales",
    31 => "R��ducation R�adaption fonctionnelle",
    32 => "Neurologie",
    33 => "Psychiatrie",
    34 => "G�riatrie",
    35 => "N�phrologie",
    36 => "Chirurgie dentaire (sp�. O.D.F.)",
    37 => "Anatomo Cyto-Pathologie",
    38 => "M�decin biologiste",
    39 => "Laboratoire polyvalent",
    40 => "Laboratoire anatomo-cyto-pathologie",
    41 => "Chirurgie orthop�dique et Traumatologie",
    42 => "Endocrinologie et M�tabolisme",
    43 => "Chirurgie infantile",
    44 => "Chirurgie maxillo-faciale",
    45 => "Chirurgie maxillo-faciale et Stomatologie",
    46 => "Chirurgie Plastique reconstructrice",
    47 => "Chirurgie thoracique et cardio-vasculaire",
    48 => "Chirurgie vasculaire",
    49 => "Chirurgie visc�rale et digestive",
    50 => "Pharmacien",
    51 => "Pharmacien mutualiste",
    53 => "Chirurgie dentaire (sp�. C.O.)",
    54 => "Chirurgie dentaire (sp�. M.B.D.)",
    60 => "Prestataire de type soci�t�",
    61 => "Prestataire artisan",
    62 => "Prestataire de type association",
    63 => "Orth�siste",
    64 => "Opticien",
    65 => "Audioproth�siste",
    66 => "Epith�siste Oculariste",
    67 => "Podo-orth�siste",
    68 => "Orthoproth�siste",
    69 => "Chirurgie orale",
    70 => "Gyn�cologie m�dicale",
    71 => "H�matologie",
    72 => "M�decine nucl�aire",
    73 => "Oncologie m�dicale",
    74 => "Oncologie radioth�rapique",
    75 => "Psychiatrie de l'enfant et de l'adolescent",
    76 => "Radioth�rapie",
    77 => "Obst�trique",
    78 => "G�n�tique m�dicale",
    79 => "Obst�trique et Gyn�cologie m�dicale",
    80 => "Sant� publique et m�decine sociale",
    81 => "M�decine des maladies infectieuses et tropicales",
    82 => "M�decin l�gale et expertises m�dicales",
    83 => "M�decine d'urgence",
    84 => "M�decin vasculaire",
    85 => "Allergologie",
    86 => "Infirmier exer�ant en pratiques avanc�es (IPA)",
  );

  /** @var int The id of the speciality */
  public $spec_cpam_id;

  /** @var string The number of the speciality, in string format */
  public $number;

  /** @var string The name of the speciality */
  public $text;

  /**
   * CSpecCPAM constructor.
   *
   * @param int $id The id of the speciality
   */
  public function __construct($id = null) {
    parent::__construct();

    if ($id && array_key_exists(intval($id), self::$specialities)) {
      $this->spec_cpam_id = $this->_id = intval($id);
      $this->number = str_pad($this->spec_cpam_id, 2, '0', STR_PAD_LEFT);
      $this->text = self::$specialities[$this->spec_cpam_id];
      $this->_view = "{$this->number} - {$this->text}";
      $this->_shortview = CMbString::truncate($this->_view);
    }
  }

  /**
   * @see parent::getProps
   */
  public function getProps() {
    $props = parent::getProps();

    $props['number']  = 'str fieldset|default';
    $props['text']    = 'str notNull fieldset|default';

    return $props;
  }

  /**
   * Returns the CSpecCPAM for the given id
   *
   * @param int $id The id of the speciality
   *
   * @return CSpecCPAM
   */
  public static function get($id) {
    return new self($id);
  }

  /**
   * Returns the CSpecCPAM with the given name
   *
   * @param string $name The name of the speciality
   *
   * @return CSpecCPAM
   */
  public static function getByName($name) {
    $search = array('-', '.', "'", '(', ')');

    $specialities = array_map(
      function ($v) {
        return strtolower(CMbString::removeDiacritics(str_replace(array('-', '.', "'", '(', ')'), '', $v)));
      },
      self::$specialities
    );

    $name = strtolower(CMbString::removeDiacritics(str_replace(array('-', '.', "'", '(', ')'), '', $name)));

    /* If the value is not found, an empty object will be returned */
    return self::get(array_search($name, $specialities));
  }

    /**
     * @param string $name
     *
     * @return CSpecCPAM[]
     */
    public static function searchByName(string $name): array
    {
        $search       = ['-', '.', "'", '(', ')'];
        $specialities = array_map(
            function ($v) use ($search) {
                return strtolower(CMbString::removeDiacritics(str_replace($search, '', $v)));
            },
            self::$specialities
        );

        $name = strtolower(CMbString::removeDiacritics(str_replace($search, '', $name)));

        $matches = array_filter($specialities, function ($speciality) use ($name) {
            return preg_match("/$name/", $speciality);
        });

        return array_map(
            function ($speciality_id) {
                return self::get($speciality_id);
            },
            array_keys($matches)
        );
    }

    /**
     * Returns the list of all the specialities
   *
   * @param string $order The order (asc or desc)
   *
   * @return CSpecCPAM[]
   */
  public static function getList($order = 'asc') {
    $cache = new Cache(__METHOD__, null, Cache::INNER);

    $specialities = array();
    if ($cache->exists()) {
      $specialities = $cache->get();
    }
    else {
      foreach (self::$specialities as $id => $text) {
        $specialities[$id] = new self($id);
      }

      $cache->put($specialities);
    }

    if ($order == 'asc') {
      ksort($specialities);
    }
    elseif ($order == 'desc') {
      krsort($specialities);
    }

    return $specialities;
  }

  /**
   * Do not remove (loadRefModule() is called in the CModelObject but only declared in CStoredObject, and not in CModelObject)
   *
   * @return void
   */
  function loadRefModule() {
  }
}

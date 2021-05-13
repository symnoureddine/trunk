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
    1 => "Médecine générale",
    2 => "Anesthesie - Réanimation",
    3 => "Cardiologie",
    4 => "Chirurgie générale",
    5 => "Dermatologie et Vénérologie",
    6 => "Radiologie",
    7 => "Gynécologie obstétrique",
    8 => "Gastro-Entérologie et Hépatologie",
    9 => "Médecine interne",
    10 => "Neuro-Chirurgie",
    11 => "Oto-Rhino-Laryngologie",
    12 => "Pédiatrie",
    13 => "Pneumologie",
    14 => "Rhumatologie",
    15 => "Ophtalmologie",
    16 => "Chirurgie urologique",
    17 => "Neuro-Psychiatrie",
    18 => "Stomatologie",
    19 => "Chirurgie dentaire",
    20 => "Réanimation médicale",
    21 => "Sage-femme",
    22 => "Spécialiste en médecin générale (Diplômé)",
    23 => "Spécialiste en médecin générale (Ordre)",
    24 => "Infirmier",
    26 => "Masseur Kinésithérapeute",
    27 => "Pedicure Podologue",
    28 => "Orthophoniste",
    29 => "Orthoptiste",
    30 => "Laboratoire d'analyses médicales",
    31 => "Rééducation Réadaption fonctionnelle",
    32 => "Neurologie",
    33 => "Psychiatrie",
    34 => "Gériatrie",
    35 => "Néphrologie",
    36 => "Chirurgie dentaire (spé. O.D.F.)",
    37 => "Anatomo Cyto-Pathologie",
    38 => "Médecin biologiste",
    39 => "Laboratoire polyvalent",
    40 => "Laboratoire anatomo-cyto-pathologie",
    41 => "Chirurgie orthopédique et Traumatologie",
    42 => "Endocrinologie et Métabolisme",
    43 => "Chirurgie infantile",
    44 => "Chirurgie maxillo-faciale",
    45 => "Chirurgie maxillo-faciale et Stomatologie",
    46 => "Chirurgie Plastique reconstructrice",
    47 => "Chirurgie thoracique et cardio-vasculaire",
    48 => "Chirurgie vasculaire",
    49 => "Chirurgie viscérale et digestive",
    50 => "Pharmacien",
    51 => "Pharmacien mutualiste",
    53 => "Chirurgie dentaire (spé. C.O.)",
    54 => "Chirurgie dentaire (spé. M.B.D.)",
    60 => "Prestataire de type société",
    61 => "Prestataire artisan",
    62 => "Prestataire de type association",
    63 => "Orthésiste",
    64 => "Opticien",
    65 => "Audioprothésiste",
    66 => "Epithèsiste Oculariste",
    67 => "Podo-orthésiste",
    68 => "Orthoprothésiste",
    69 => "Chirurgie orale",
    70 => "Gynécologie médicale",
    71 => "Hématologie",
    72 => "Médecine nucléaire",
    73 => "Oncologie médicale",
    74 => "Oncologie radiothérapique",
    75 => "Psychiatrie de l'enfant et de l'adolescent",
    76 => "Radiothérapie",
    77 => "Obstétrique",
    78 => "Génétique médicale",
    79 => "Obstétrique et Gynécologie médicale",
    80 => "Santé publique et médecine sociale",
    81 => "Médecine des maladies infectieuses et tropicales",
    82 => "Médecin légale et expertises médicales",
    83 => "Médecine d'urgence",
    84 => "Médecin vasculaire",
    85 => "Allergologie",
    86 => "Infirmier exerçant en pratiques avancées (IPA)",
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

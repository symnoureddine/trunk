<?php
/**
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Maternite;

use Exception;
use Ox\Core\CMbObject;

/**
 * Suivi des mesures d'�chographie du dossier de p�rinatalit�
 */
class CSurvEchoGrossesse extends CMbObject
{
    // DB Table key
    public $surv_echo_grossesse_id;

    public $grossesse_id;

    public $date;
    public $num_enfant;
    public $type_echo;

    public $lcc;             // Longueur Cranio-Caudale (mm)
    public $bip;             // Diam�tre bipari�tal (mm)
    public $pc;              // P�rim�tre c�phalique (mm)
    public $dat;             // Diam�tre abdominal transverse (mm)
    public $pa;              // P�rim�tre abdominal (mm)
    public $lf;              // Longueur f�morale (mm)
    public $lp;              // Longueur du pied (mm)
    public $dfo;             // Diam�tre fronto-occipital (mm)
    public $cn;              // Clart� nucale (mm)
    public $opn;             // Os propres du nez
    public $poids_foetal;    // Poids Foetal (g)
    public $avis_dan;        // Diagnostic Ant�natal
    public $pos_placentaire; // Position placentaire
    public $bcba;            // Bi choriale bi amniotique (2 placentas pour les foetus dans 2 poches amniotiques)
    public $mcma;            // Mono choriale mono amniotique (1 seul placenta pour les foetus dans 1 poche amniotique)
    public $mcba;            // Mono choriale bi amniotique (1 seul placenta pour les foetus dans 2 poches amniotiques)

    public $remarques;
    public $_nb_enfant;

    /** @var CGrossesse */
    public $_ref_grossesse;
    /** @var CSurvEchoGrossesse[] */
    public $_ref_echo_children;

    public $_sa;

    /**
     * @see parent::getSpec()
     */
    function getSpec()
    {
        $spec        = parent::getSpec();
        $spec->table = 'surv_echo_grossesse';
        $spec->key   = 'surv_echo_grossesse_id';

        return $spec;
    }

    /**
     * @see parent::getProps()
     */
    function getProps()
    {
        $props = parent::getProps();

        $props["grossesse_id"]    = "ref notNull class|CGrossesse back|echographies";
        $props["date"]            = "date notNull";
        $props["num_enfant"]      = "num min|0";
        $props["type_echo"]       = "enum list|t1|t2|t3|autre default|autre";
        $props["lcc"]             = "float min|0"; // mm
        $props["bip"]             = "float min|0"; // mm
        $props["pc"]              = "float min|0"; // mm
        $props["dat"]             = "float min|0"; // mm
        $props["pa"]              = "float min|0"; // mm
        $props["lf"]              = "float min|0"; // mm
        $props["lp"]              = "float min|0"; // mm
        $props["dfo"]             = "float min|0"; // mm
        $props["cn"]              = "float min|0"; // mm
        $props["opn"]             = "bool";
        $props["remarques"]       = "text helped";
        $props["poids_foetal"]    = "num"; // g
        $props["avis_dan"]        = "bool";
        $props["pos_placentaire"] = "text helped";
        $props["bcba"]            = "num";
        $props["mcma"]            = "num";
        $props["mcba"]            = "num";

        $props["_sa"]        = "num";
        $props["_nb_enfant"] = "num";

        return $props;
    }

    /**
     * Chargement de la grossesse
     *
     * @return CGrossesse
     * @throws Exception
     */
    function loadRefGrossesse(): CGrossesse
    {
        return $this->_ref_grossesse = $this->loadFwdRef("grossesse_id", true);
    }

    /**
     * Get children from a multiple pregnancy
     *
     * @param string $date Date
     *
     * @return CSurvEchoGrossesse[]
     * @throws Exception
     */
    public function loadRefEchoChildren($date = null): array
    {
        $where                 = [];
        $where["grossesse_id"] = " = '$this->grossesse_id'";

        if ($date) {
            $where["date"] = " = '$date'";
        }

        $echographie  = new self();
        $echographies = $echographie->loadList($where, "date ASC", null, "num_enfant");

        $this->_nb_enfant = count($echographies) ?: 1;

        return $this->_ref_echo_children = $echographies;
    }

    /**
     * Calcul de la date en semaines d'am�norrh�e
     *
     * @return int
     */
    public function getSA(): int
    {
        $this->loadRefGrossesse();
        $sa_comp = $this->_ref_grossesse->getAgeGestationnel($this->date);

        return $this->_sa = $sa_comp["SA"];
    }

    static $graph_axes = [
        'lcc'          => [
            [7, 10],
            [8, 16],
            [9, 24],
            [10, 33],
            [11, 44],
            [12, 56],
            [13, 69],
            [14, 84],
        ],
        'bip'          => [
            16 => [31.8, 33.3, 36.5, 39.7, 41.3],
            17 => [34.3, 35.8, 39.2, 42.5, 44],
            18 => [36.9, 38.5, 41.9, 45.3, 46.9],
            19 => [39.7, 41.3, 44.8, 48.3, 49.9],
            20 => [42.6, 44.2, 47.8, 51.3, 53],
            21 => [45.5, 47.2, 50.8, 54.5, 56.2],
            22 => [48.5, 50.2, 53.9, 57.7, 59.4],
            23 => [51.5, 53.3, 57.1, 60.9, 62.7],
            24 => [54.5, 56.3, 60.2, 64.1, 65.9],
            25 => [57.5, 59.4, 63.3, 67.3, 69.1],
            26 => [60.5, 62.4, 66.4, 70.4, 72.3],
            27 => [63.4, 65.3, 69.4, 73.5, 75.5],
            28 => [66.2, 68.2, 72.4, 76.6, 78.5],
            29 => [68.9, 70.9, 75.2, 79.5, 81.5],
            30 => [71.5, 73.5, 77.9, 82.2, 84.3],
            31 => [73.9, 76, 80.4, 84.9, 87],
            32 => [76.1, 78.3, 82.8, 87.3, 89.5],
            33 => [78.2, 80.4, 85, 89.6, 91.7],
            34 => [80, 82.2, 86.9, 91.6, 93.8],
            35 => [81.6, 83.9, 88.7, 93.4, 95.7],
            36 => [83, 85.3, 90.1, 95, 97.3],
            37 => [84, 86.4, 91.3, 96.2, 98.5],
            38 => [84.8, 87.2, 92.2, 97.2, 99.5],
            39 => [85.2, 87.6, 92.7, 97.8, 100.2],
            40 => [85.3, 87.7, 92.9, 98.1, 100.5],
        ],
        'pc'           => [
            16 => [112.4, 117.4, 128, 138.6, 143.6],
            17 => [122.3, 127.5, 138.6, 149.6, 154.9],
            18 => [132.4, 137.8, 149.4, 160.9, 166.3],
            19 => [142.8, 148.4, 160.4, 172.3, 177.9],
            20 => [153.2, 159, 171.4, 183.9, 189.7],
            21 => [163.7, 169.7, 182.6, 195.4, 201.5],
            22 => [174.2, 180.4, 193.7, 207, 213.3],
            23 => [184.6, 191.1, 204.8, 218.5, 225],
            24 => [194.9, 201.5, 215.7, 229.9, 236.5],
            25 => [204.9, 211.8, 226.4, 241, 247.9],
            26 => [214.7, 221.8, 236.9, 251.9, 259],
            27 => [224.2, 231.5, 247, 262.5, 269.7],
            28 => [233.2, 240.7, 256.7, 272.6, 280.1],
            29 => [241.8, 249.5, 265.9, 282.3, 290],
            30 => [249.9, 257.8, 274.6, 291.4, 299.3],
            31 => [257.4, 265.5, 282.7, 300, 308.1],
            32 => [264.2, 272.5, 290.2, 307.9, 316.2],
            33 => [270.3, 278.8, 297, 315.1, 323.6],
            34 => [275.6, 284.3, 302.9, 321.5, 330.2],
            35 => [280.1, 289, 308, 327.1, 336],
            36 => [283.6, 292.8, 312.2, 331.7, 340.9],
            37 => [286.2, 295.5, 315.5, 335.4, 344.7],
            38 => [287.7, 297.3, 317.7, 338, 347.6],
            39 => [288.2, 297.9, 318.7, 339.5, 349.3],
            40 => [287.4, 297.4, 318.6, 339.9, 349.9],
        ],
        'dat'          => [
            11 => [9.68, 11, 13.5, 16, 17.25],
            12 => [12.68, 14, 17, 20, 21.46],
            13 => [15.6, 17.25, 20.56, 24, 25.51],
            14 => [18.69, 20.41, 24, 27.84, 29.56],
            15 => [21.76, 23.64, 27.69, 31.74, 33.61],
            16 => [25, 27, 31.21, 35.53, 37.48],
            17 => [28.23, 30.34, 34.7, 39.21, 41.39],
            18 => [31.54, 33.64, 38.31, 42.89, 45.14],
            19 => [34.78, 37, 41.69, 46.42, 48.59],
            20 => [38.16, 40.26, 45.21, 50, 52.2],
            21 => [41.14, 43.46, 48.34, 53.22, 55.63],
            22 => [44.21, 46.61, 51.57, 56.75, 59.08],
            23 => [47, 49.47, 54.72, 60, 62.46],
            24 => [49.77, 52.39, 57.88, 63.43, 66],
            25 => [52.54, 55.18, 61, 66.74, 69.44],
            26 => [55.17, 58, 64, 70.12, 72.89],
            27 => [57.72, 60.73, 67.11, 73.42, 76.42],
            28 => [60.43, 63.58, 70.27, 76.8, 79.87],
            29 => [63.13, 66.36, 73.27, 80.17, 83.33],
            30 => [65.8, 69.17, 76.17, 83.45, 86.75],
            31 => [68.35, 71.88, 79.25, 86.68, 90.13],
            32 => [70.9, 74.43, 82.1, 89.76, 93.36],
            33 => [73.08, 76.75, 84.78, 92.89, 96.64],
            34 => [75.25, 79.08, 87.55, 95.89, 99.86],
            35 => [77, 81.1, 90, 99, 103],
            36 => [78.48, 82.9, 92.36, 102, 106.31],
            37 => [79.79, 84.6, 94.81, 105, 109.67],
            38 => [80.92, 86.1, 97, 108.19, 113.29],
            39 => [81.85, 87.41, 99.33, 111.34, 117],
            40 => [82.58, 88.59, 101.64, 114.52, 120.7],
            41 => [82.8, 89.2, 103, 117, 123],
        ],
        'pa'           => [
            16 => [96.7, 101.5, 111.7, 121.9, 126.7],
            17 => [105, 110.2, 121.2, 132.3, 137.4],
            18 => [113.7, 119.2, 131.1, 143, 148.5],
            19 => [122.6, 128.6, 141.2, 153.9, 159.9],
            20 => [131.8, 138.1, 151.6, 165.1, 171.5],
            21 => [141.1, 147.8, 162.2, 176.5, 183.2],
            22 => [150.6, 157.7, 172.9, 188, 195.1],
            23 => [160.1, 167.7, 183.6, 199.6, 207.1],
            24 => [169.7, 177.6, 194.5, 211.3, 219.2],
            25 => [179.3, 187.6, 205.3, 222.9, 231.2],
            26 => [188.9, 197.5, 216, 234.5, 243.2],
            27 => [198.3, 207.4, 226.7, 246, 255],
            28 => [207.6, 217, 237.1, 257.3, 266.7],
            29 => [216.7, 226.5, 247.4, 268.4, 278.2],
            30 => [225.5, 235.7, 257.5, 279.3, 289.5],
            31 => [234, 244.6, 267.2, 289.8, 300.4],
            32 => [242.2, 253.2, 276.6, 300.1, 311.1],
            33 => [250, 261.4, 285.6, 309.9, 321.3],
            34 => [257.3, 269.1, 294.2, 319.3, 331.1],
            35 => [264.2, 276.4, 302.3, 328.2, 340.4],
            36 => [270.5, 283.1, 309.8, 336.6, 349.1],
            37 => [276.3, 289.2, 316.8, 344.3, 357.3],
            38 => [281.4, 294.7, 323.1, 351.5, 364.8],
            39 => [285.8, 299.5, 328.7, 358, 371.7],
            40 => [289.5, 303.6, 333.7, 363.7, 377.8],
        ],
        'lf'           => [
            16 => [17.1, 18.4, 21, 23.6, 24.9],
            17 => [19.9, 21.2, 23.9, 26.6, 27.9],
            18 => [22.7, 24, 26.8, 29.6, 30.9],
            19 => [25.5, 26.8, 29.7, 32.5, 33.9],
            20 => [28.2, 29.5, 32.5, 35.4, 36.8],
            21 => [30.8, 32.2, 35.3, 38.3, 39.7],
            22 => [33.4, 34.9, 38, 41.1, 42.5],
            23 => [36, 37.5, 40.7, 43.8, 45.3],
            24 => [38.5, 40, 43.3, 46.5, 48.1],
            25 => [41, 42.5, 45.8, 49.2, 50.7],
            26 => [43.3, 44.9, 48.4, 51.8, 53.4],
            27 => [45.7, 47.3, 50.8, 54.3, 55.9],
            28 => [47.9, 49.6, 53.2, 56.7, 58.4],
            29 => [50.1, 51.8, 55.5, 59.1, 60.8],
            30 => [52.2, 54, 57.7, 61.4, 63.2],
            31 => [54.3, 56.1, 59.9, 63.7, 65.4],
            32 => [56.2, 58, 61.9, 65.8, 67.6],
            33 => [58.1, 59.9, 63.9, 67.9, 69.7],
            34 => [59.9, 61.8, 65.8, 69.8, 71.7],
            35 => [61.6, 63.5, 67.6, 71.7, 73.6],
            36 => [63.1, 65.1, 69.3, 73.5, 75.5],
            37 => [64.6, 66.6, 70.9, 75.2, 77.2],
            38 => [66, 68.1, 72.4, 76.8, 78.8],
            39 => [67.3, 69.4, 73.8, 78.2, 80.3],
            40 => [68.5, 70.6, 75.1, 79.6, 81.7],
        ],
        'cn'           => [
            78 => [0.81, 0.95, 1.25, 1.55, 2.05],
            79 => [0.85, 1, 1.28, 1.59, 2.1],
            80 => [0.89, 1.05, 1.33, 1.63, 2.15],
            81 => [0.93, 1.1, 1.37, 1.67, 2.2],
            82 => [0.97, 1.15, 1.41, 1.71, 2.25],
            83 => [1, 1.2, 1.46, 1.76, 2.3],
            84 => [1.03, 1.22, 1.52, 1.81, 2.34],
            85 => [1.07, 1.3, 1.57, 1.86, 2.39],
            86 => [1.09, 1.33, 1.61, 1.91, 2.43],
            87 => [1.11, 1.37, 1.64, 1.95, 2.47],
            88 => [1.13, 1.4, 1.68, 2, 2.51],
            89 => [1.15, 1.45, 1.73, 2.05, 2.56],
            90 => [1.18, 1.5, 1.78, 2.1, 2.61],
            91 => [1.2, 1.53, 1.81, 2.12, 2.64],
            92 => [1.21, 1.55, 1.83, 2.14, 2.67],
            93 => [1.22, 1.58, 1.85, 2.15, 2.7],
            94 => [1.22, 1.6, 1.87, 2.17, 2.72],
            95 => [1.21, 1.62, 1.9, 2.2, 2.75],
            96 => [1.21, 1.63, 1.93, 2.23, 2.78],
            97 => [1.2, 1.64, 1.95, 2.25, 2.8],
        ],
        'poids_foetal' => [
            17 => [148.35, 159.1, 182.04, 204.98, 215.72],
            18 => [202.89, 215.61, 242.75, 269.9, 282.62],
            19 => [251.15, 266.57, 299.47, 332.37, 347.79],
            20 => [297.62, 316.46, 356.66, 396.86, 415.71],
            21 => [346.17, 369.16, 418.22, 467.27, 490.26],
            22 => [400.08, 427.94, 487.39, 546.84, 574.7],
            23 => [461.98, 495.45, 566.84, 638.23, 671.69],
            24 => [533.93, 573.72, 658.6, 743.48, 783.27],
            25 => [617.35, 664.18, 764.1, 864.01, 910.85],
            26 => [713.05, 767.66, 884.16, 1000.65, 1055.26],
            27 => [821.24, 884.34, 1018.97, 1153.6, 1216.71],
            28 => [941.51, 1013.84, 1168.15, 1322.45, 1394.78],
            29 => [1072.85, 1155.13, 1330.66, 1506.19, 1588.47],
            30 => [1213.62, 1306.57, 1504.87, 1703.17, 1796.13],
            31 => [1361.58, 1465.93, 1688.55, 1911.17, 2015.52],
            32 => [1513.89, 1630.36, 1878.84, 2127.32, 2243.8],
            33 => [1667.06, 1796.39, 2072.28, 2348.17, 2477.49],
            34 => [1817.03, 1959.93, 2264.78, 2569.63, 2712.53],
            35 => [1959.12, 2116.31, 2451.66, 2787.02, 2944.21],
            36 => [2088, 2260.22, 2627.63, 2995.03, 3167.25],
            37 => [2197.79, 2385.76, 2786.76, 3187.76, 3375.73],
            38 => [2281.95, 2486.39, 2922.53, 3358.68, 3563.12],
        ],
    ];
}

<?php
/**
 * @package Mediboard\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Api\Request;


use Ox\Core\Api\Request\CRequestLanguages;
use Ox\Tests\UnitTestMediboard;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description
 */
class CRequestLanguagesTest extends UnitTestMediboard {

  /**
   * @param string $query_content
   * @param array  $expected
   *
   * @dataProvider languagesProvider
   */
  public function testFormats($query_content, $expected) {
    $header = ($query_content !== null) ? ['HTTP_' . CRequestLanguages::HEADER_KEY_WORD => $query_content] : [];
    $req    = new Request([], [], [], [], [], $header);

    $req_lang = new CRequestLanguages($req);
    // Must compare strict array
    $this->assertTrue($expected['languages'] === $req_lang->getLanguage());
    $this->assertTrue($expected['languages_weighting'] === $req_lang->getWeithtingLanguages());

    $weighted_keys = array_keys($expected['languages_weighting']);
    $this->assertEquals(reset($weighted_keys), $req_lang->getExpected());
  }

  /**
   * @return array
   */
  public function languagesProvider() {
    return [
      'noLanguage'             => [
        '',
        [
          'languages'           => [''],
          'languages_weighting' => ['' => null],
        ]
      ],
      'languageNull'           => [
        null,
        [
          'languages'           => [CRequestLanguages::SHORT_TAG_FR],
          'languages_weighting' => [CRequestLanguages::SHORT_TAG_FR => null],
        ]
      ],
      'singleLanguageNoWeight' => [
        CRequestLanguages::SHORT_TAG_FR,
        [
          'languages'           => [CRequestLanguages::SHORT_TAG_FR],
          'languages_weighting' => [CRequestLanguages::SHORT_TAG_FR => null],
        ]
      ],
      'singleLanguageWeighted' => [
        CRequestLanguages::LONG_TAG_FR . ';' . 'q=0.5',
        [
          'languages'           => [CRequestLanguages::LONG_TAG_FR . ';' . 'q=0.5'],
          'languages_weighting' => [CRequestLanguages::LONG_TAG_FR => '0.5'],
        ]
      ],
      'multiLanguageNoWeight'  => [
        CRequestLanguages::SHORT_TAG_FR . ',' . CRequestLanguages::LONG_TAG_EN . ',' . CRequestLanguages::SHORT_TAG_EN,
        [
          'languages'           => [CRequestLanguages::SHORT_TAG_FR, CRequestLanguages::LONG_TAG_EN, CRequestLanguages::SHORT_TAG_EN],
          'languages_weighting' => [
            CRequestLanguages::SHORT_TAG_FR => null,
            CRequestLanguages::LONG_TAG_EN  => null,
            CRequestLanguages::SHORT_TAG_EN => null,
          ],
        ]
      ],
      'multiLanguageWeighted'  => [
        CRequestLanguages::SHORT_TAG_FR . ';q=0.2,' . CRequestLanguages::LONG_TAG_EN . ';q=0.8,'
        . CRequestLanguages::SHORT_TAG_EN,
        [
          'languages'           => [
            CRequestLanguages::SHORT_TAG_FR . ';q=0.2',
            CRequestLanguages::LONG_TAG_EN . ';q=0.8',
            CRequestLanguages::SHORT_TAG_EN
          ],
          'languages_weighting' => [
            CRequestLanguages::LONG_TAG_EN  => '0.8',
            CRequestLanguages::SHORT_TAG_FR => '0.2',
            CRequestLanguages::SHORT_TAG_EN => null,
          ],
        ]
      ],
      'multipleSingleLanguage'  => [
        CRequestLanguages::SHORT_TAG_FR . ';q=0.2,' . CRequestLanguages::SHORT_TAG_FR . ';q=0.8',
        [
          'languages'           => [
            CRequestLanguages::SHORT_TAG_FR . ';q=0.2',
            CRequestLanguages::SHORT_TAG_FR . ';q=0.8',
          ],
          'languages_weighting' => [
            CRequestLanguages::SHORT_TAG_FR => '0.8',
          ],
        ]
      ],
    ];
  }
}
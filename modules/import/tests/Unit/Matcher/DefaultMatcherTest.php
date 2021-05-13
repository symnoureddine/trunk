<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Tests\Unit\Matcher;

use Ox\Core\CMbDT;
use Ox\Core\CMbString;
use Ox\Import\Framework\Matcher\DefaultMatcher;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Patients\CMedecin;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Tests\UnitTestMediboard;

class DefaultMatcherTest extends UnitTestMediboard
{
    /**
     * @var DefaultMatcher
     */
    private $defaultMatcher;


    public function setUp(): void
    {
        $this->defaultMatcher = new DefaultMatcher();
    }

    public function testMatchUser(): void
    {
        $user = $this->getRandomObjects(CUser::class);

        $user_after = new CUser();
        $user_after->cloneFrom($user);

        $user_after = $this->defaultMatcher->matchUser($user_after);

        $this->assertEquals($user->_id, $user_after->_id);
    }

    public function testMatchUserNotMatch(): void
    {
        $user = $this->getRandomObjects(CUser::class);

        $user_after = new CUser();
        $user_after->cloneFrom($user);
        $user_after->user_username = uniqid();

        $this->defaultMatcher->matchUser($user_after);

        $this->assertNull($user_after->_id);
    }

    protected function generatePatient(): CPatient
    {
        return $this->getRandomObjects(CPatient::class);
    }

    public function testMatchPatient(): void
    {
        $patient = $this->generatePatient();

        $patient_after = new CPatient();
        $patient_after->cloneFrom($patient);

        $this->defaultMatcher->matchPatient($patient_after);

        $this->assertEquals($patient->_id, $patient_after->_id);
    }

//    public function testMatchNotMatchPatient(): void
//    {
//        $this->markTestSkipped("pb sur les tests des pipelines");
//        $patient = $this->generatePatient();
//
//        $patient_after = new CPatient();
//        $patient_after->cloneFrom($patient);
//        $patient_after->nom = uniqid();
//
//        $this->defaultMatcher->matchPatient($patient_after);
//
//        $this->assertNull($patient_after->_id);
//    }

    protected function generateMedecin(): CMedecin
    {
        $medecin       = new CMedecin();
        $medecin->nom  = uniqid();
        $medecin->sexe = "m";

        return $medecin;
    }

    public function testMatchMedecinWithRpps(): void
    {
        $rpps          = CMbString::createLuhn('1234567890');

        $medecin       = new CMedecin();
        $medecin->rpps = strval($rpps);
        $medecin->loadMatchingObjectEsc();

        if (!$medecin->_id) {
            $medecin       = $this->generateMedecin();
            $medecin->rpps = $rpps;
            $medecin->store();
        }

        $medecin_after = new CMedecin();
        $medecin_after->cloneFrom($medecin);

        $medecin_after = $this->defaultMatcher->matchMedecin($medecin_after);

        $this->assertEquals($medecin->_id, $medecin_after->_id);
    }

    public function testMatchMedecinWithAdeli(): void
    {
        $adeli          = CMbString::createLuhn('12345678');

        $medecin = new CMedecin();
        $medecin->adeli = strval($adeli);
        $medecin->loadMatchingObjectEsc();

        if (!$medecin->_id) {
            $medecin       = $this->generateMedecin();
            $medecin->adeli = $adeli;
            $medecin->store();
        }

        $medecin_after = new CMedecin();
        $medecin_after->cloneFrom($medecin);

        $medecin_after = $this->defaultMatcher->matchMedecin($medecin_after);

        $this->assertEquals($medecin->_id, $medecin_after->_id);
    }

    public function testMatchMedecinWithNameAndCp(): void
    {
        $medecin     = $this->generateMedecin();
        $medecin->cp = '17000';
        $medecin->store();

        $medecin_after = new CMedecin();
        $medecin_after->cloneFrom($medecin);

        $medecin_after = $this->defaultMatcher->matchMedecin($medecin_after);

        $this->assertEquals($medecin->_id, $medecin_after->_id);
    }

    public function testMatchMedecinWithNameAndCpPartial(): void
    {
        $medecin     = $this->generateMedecin();
        $medecin->cp = '17000';
        $medecin->store();

        $medecin_after = new CMedecin();
        $medecin_after->cloneFrom($medecin);
        $medecin_after->cp = '17500';

        $medecin_after = $this->defaultMatcher->matchMedecin($medecin_after);

        $this->assertEquals($medecin->_id, $medecin_after->_id);
    }

    public function testMatchMedecinNotMatch(): void
    {
        $medecin = $this->generateMedecin();
        $medecin->store();

        $medecin_after = new CMedecin();
        $medecin_after->cloneFrom($medecin);
        $medecin_after->nom = uniqid();

        $this->defaultMatcher->matchMedecin($medecin_after);

        $this->assertNull($medecin_after->_id);
    }

    protected function generatePlageConsult(): CPlageconsult
    {
        $plage_consult                = new CPlageconsult();
        $plage_consult->chir_id       = $this->getRandomObjects(CUser::class)->_id;
        $plage_consult->date          = CMbDT::date('1800-12-12');
        $plage_consult->freq          = CMbDT::time('22:22:22');
        $plage_consult->debut         = CMbDT::time('22:22:22');
        $plage_consult->fin           = CMbDT::time('23:23:23');
        $plage_consult->desistee      = 0;
        $plage_consult->remplacant_ok = 0;
        $plage_consult->color         = 'DDDDDD';

        return $plage_consult;
    }

    public function testMatchPlageConsultNotMatch(): void
    {
        $plage_consult = $this->generatePlageConsult();
        $plage_consult->store();

        $plage_consult_after = new CPlageconsult();
        $plage_consult_after->load($plage_consult->_id);
        $plage_consult_after->_id     = null;
        $plage_consult_after->chir_id = null;
        $plage_consult_after->date    = CMbDT::date();

        $this->defaultMatcher->matchPlageConsult($plage_consult_after);

        $this->assertNull($plage_consult_after->_id);
    }

    public function testMatchPlageConsultMatchToDate(): void
    {
        $plage_consult = $this->generatePlageConsult();
        $plage_consult->store();

        $plage_consult_after = new CPlageconsult();
        $plage_consult_after->cloneFrom($plage_consult);

        $this->defaultMatcher->matchPlageConsult($plage_consult_after);

        $this->assertEquals($plage_consult->_id, $plage_consult_after->_id);
    }

    protected function generateConsultation(): CConsultation
    {
        return $this->getRandomObjects(CConsultation::class);
    }

    public function testMatchConsultationNotMatchWithOtherPatient(): void
    {
        $consultation = $this->generateConsultation();

        $consultation_after = new CConsultation();
        $consultation_after->cloneFrom($consultation);
        $consultation_after->patient_id = uniqid();

        $this->defaultMatcher->matchConsultation($consultation_after);

        $this->assertNull($consultation_after->_id);
    }

    public function testMatchConsultationNotMatchWithOtherPlageConsult(): void
    {
        $consultation = $this->generateConsultation();

        $consultation_after = new CConsultation();
        $consultation_after->cloneFrom($consultation);
        $consultation_after->plageconsult_id = uniqid();

        $this->defaultMatcher->matchConsultation($consultation_after);

        $this->assertNull($consultation_after->_id);
    }

    public function testMatchConsultationIfMatch(): void
    {
        $consultation = $this->generateConsultation();

        $consultation_after = new CConsultation();
        $consultation_after->cloneFrom($consultation);
        $this->defaultMatcher->matchConsultation($consultation_after);

        $this->assertEquals($consultation->_id, $consultation_after->_id);
    }

    protected function generateSejour(): CSejour
    {
        return $this->getRandomObjects(CSejour::class);
    }

    public function testSejourMatchNotMatchWithOtherPatient(): void
    {
        $sejour = $this->generateSejour();

        $sejour_after = new CSejour();
        $sejour_after->cloneFrom($sejour);
        $sejour_after->patient_id = uniqid();

        $this->defaultMatcher->matchSejour($sejour_after);

        $this->assertNull($sejour_after->_id);
    }

    public function testSejourMatchNotMatchWithOtherGroup(): void
    {
        $sejour = $this->generateSejour();

        $sejour_after = new CSejour();
        $sejour_after->cloneFrom($sejour);
        $sejour_after->group_id = uniqid();

        $this->defaultMatcher->matchSejour($sejour_after);

        $this->assertNull($sejour_after->_id);
    }

    public function testSejourMatchNotMatchWithOtherEntreeMoreOneDay(): void
    {
        $sejour = $this->generateSejour();

        $sejour_after = new CSejour();
        $sejour_after->cloneFrom($sejour);
        $sejour_after->group_id = CMbDT::date('+10 DAYS');

        $this->defaultMatcher->matchSejour($sejour_after);

        $this->assertNull($sejour_after->_id);
    }

    public function testSejourMatchNotMatchWithOtherEntreeLessOneDay(): void
    {
        $sejour = $this->generateSejour();

        $sejour_after = new CSejour();
        $sejour_after->cloneFrom($sejour);
        $sejour_after->group_id = CMbDT::date('-100000 DAYS');

        $this->defaultMatcher->matchSejour($sejour_after);

        $this->assertNull($sejour_after->_id);
    }

    public function testSejourMatchIfMatch(): void
    {
        $sejour = $this->generateSejour();

        $sejour_after = new CSejour();
        $sejour_after->cloneFrom($sejour);

        $this->defaultMatcher->matchSejour($sejour_after);

        $this->assertEquals($sejour->_id, $sejour_after->_id);
    }

    public function testSejourMatchIfMatchWithOneDayMore(): void
    {
        $sejour = $this->generateSejour();

        $sejour_after = new CSejour();
        $sejour_after->cloneFrom($sejour);
        $sejour_after->entree_prevue = CMbDT::dateTime("+1 DAYS", $sejour->entree_prevue);

        $this->defaultMatcher->matchSejour($sejour_after);

        $this->assertEquals($sejour->_id, $sejour_after->_id);
    }

    public function testSejourMatchIfMatchWithOneDayLess(): void
    {
        $sejour = $this->generateSejour();

        $sejour_after = new CSejour();
        $sejour_after->cloneFrom($sejour);
        $sejour_after->entree_prevue = CMbDT::dateTime("-1 DAY", $sejour->entree_prevue);

        $this->defaultMatcher->matchSejour($sejour_after);

        $this->assertEquals($sejour->_id, $sejour_after->_id);
    }

    protected function generateFile(): CFile
    {
        return $this->getRandomObjects(CFile::class);
    }

    //    public function testMatchFile(): void
    //    {
    //        $this->markTestSkipped('attente implementation matchFile');
    //        $file = $this->generateFile();
    //
    //        $file_after = new CFile();
    //        $file_after->cloneFrom($file);
    //
    //        $this->defaultMatcher->matchFile($file_after);
    //
    //        $this->assertEquals($file->_id, $file_after->_id);
    //    }
    //
    //    public function testMatchFileNotMatchFileNotSameObjectClass(): void
    //    {
    //        $this->markTestSkipped('attente implementation matchFile');
    //        $file = $this->generateFile();
    //
    //        $file_after = new CFile();
    //        $file_after->cloneFrom($file);
    //        $file_after->object_class = 'toto';
    //        $file_after->object_id    = $file->object_id;
    //
    //        $this->defaultMatcher->matchFile($file_after);
    //
    //        $this->assertNull($file_after->_id);
    //    }
    //
    //    public function testMatchFileNotMatchNotSameAuthor(): void
    //    {
    //        $this->markTestSkipped('attente implementation matchFile');
    //        $file = $this->generateFile();
    //
    //        $file_after = new CFile();
    //        $file_after->cloneFrom($file);
    //        $file_after->author_id = uniqid();
    //
    //        $this->defaultMatcher->matchFile($file_after);
    //
    //        $this->assertNull($file_after->_id);
    //    }
    //
    //    public function testMatchFileNotMatchNotSameDate(): void
    //    {
    //        $this->markTestSkipped('attente implementation matchFile');
    //        $file = $this->generateFile();
    //
    //        $file_after = new CFile();
    //        $file_after->cloneFrom($file);
    //        $file_after->file_date = CMbDT::date('+10 DAYS', $file->file_date);
    //
    //        $this->defaultMatcher->matchFile($file_after);
    //
    //        $this->assertNull($file_after->_id);
    //    }
}

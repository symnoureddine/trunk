<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Tests\Unit\Persister;

use Ox\Core\CMbDT;
use Ox\Import\Framework\Persister\DefaultPersister;
use Ox\Import\Framework\Tests\Unit\GeneratorEntityTrait;
use Ox\Import\Framework\Transformer\DefaultTransformer;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Patients\CPatient;
use Ox\Tests\UnitTestMediboard;

class DefaultPersisterTest extends UnitTestMediboard
{
    use GeneratorEntityTrait;

    /**
     * @var DefaultPersister
     */
    private $default_persister;
    /**
     * @var DefaultTransformer
     */
    private $default_transformer;

    public function setUp(): void
    {
        $this->default_persister   = new DefaultPersister();
        $this->default_transformer = new DefaultTransformer();
    }


    public function testPersistUserOk(): void
    {
        $c_user = $this->getRandomObjects(CUser::class);

        $this->default_persister->persistObject($c_user);

        $this->assertNotNull($c_user->_id);
    }

    /**
     * @throws \Ox\Tests\TestsException
     * @dataProvider getBadCUserProvider
     */
    public function testPersistBadCUser(CUser $c_user): void
    {
        $this->expectExceptionMessageMatches("/PersisterException-error-*/");
        $this->default_persister->persistObject($c_user);
    }

    public function generateMinCUser(): CUser
    {
        $c_user                 = new CUser();
        $c_user->user_username  = uniqid('user', true);
        $c_user->user_last_name = uniqid('user', true);

        return $c_user;
    }

    public function getBadCUserProvider(): array
    {
        $tab = [];

        $c_user                        = $this->generateMinCUser();
        $c_user->user_username         = null;
        $tab["user without name"]      = [$c_user];
        $c_user                        = $this->generateMinCUser();
        $c_user->user_last_name        = null;
        $tab["user without last_name"] = [$c_user];

        return $tab;
    }

    /**
     * @throws \Ox\Tests\TestsException
     * @dataProvider getBadCPatientProvider
     */
    public function testPersistBadCPatient(CPatient $c_patient): void
    {
        $this->expectExceptionMessageMatches("/PersisterException-error-*/");
        $this->default_persister->persistPatient($c_patient);
    }

    public function generateMinCPatient(): CPatient
    {
        $c_patient            = new CPatient();
        $c_patient->prenom    = uniqid('', true);
        $c_patient->nom       = uniqid('', true);
        $c_patient->naissance = CMbDT::date();

        return $c_patient;
    }

    public function getBadCPatientProvider(): array
    {
        $tab = [];

        $c_patient             = $this->generateMinCPatient();
        $c_patient->prenom     = null;
        $tab["without prenom"] = [$c_patient];
        $c_patient             = $this->generateMinCPatient();
        $c_patient->nom        = null;
        $tab["without nom"]    = [$c_patient];
        $c_patient             = $this->generateMinCPatient();
        $c_patient->naissance  = strval(random_bytes(4));

        $c_patient            = $this->generateMinCPatient();
        $c_patient->sexe      = "j";
        $tab["with bad sexe"] = [$c_patient];

        return $tab;
    }
}

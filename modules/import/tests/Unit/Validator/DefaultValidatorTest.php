<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Tests\Unit\Validator;

use DateTime;
use Ox\Core\CMbDT;
use Ox\Core\Specification\SpecificationViolation;
use Ox\Import\Framework\Entity\Consultation;
use Ox\Import\Framework\Entity\EntityInterface;
use Ox\Import\Framework\Entity\File;
use Ox\Import\Framework\Entity\Medecin;
use Ox\Import\Framework\Entity\Patient;
use Ox\Import\Framework\Entity\PlageConsult;
use Ox\Import\Framework\Entity\Sejour;
use Ox\Import\Framework\Entity\User;
use Ox\Import\Framework\Tests\Unit\GeneratorEntityTrait;
use Ox\Import\Framework\Validator\DefaultValidator;
use Ox\Tests\UnitTestMediboard;

class DefaultValidatorTest extends UnitTestMediboard
{
    use GeneratorEntityTrait;

    private const MAPPING = [
        Patient::class => 'validatePatient',
    ];
    /**
     * @var DefaultValidator
     */
    private $default_validator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $external_user;

    public function setUp(): void
    {
        $this->default_validator = new DefaultValidator();

        $this->external_user = $this->createMock(User::class);
    }


    /**
     * @param string $external_class
     * @param array  $state
     *
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @dataProvider getValidatedObjectProvider
     *
     * @config [CConfiguration] dPpatients CPatient addr_patient_mandatory 0
     * @config [CConfiguration] dPpatients CPatient cp_patient_mandatory 0
     * @config [CConfiguration] dPpatients CPatient tel_patient_mandatory 0
     *
     */
    // test avec des entity valide
    public function testEntityValidationIsOK(string $external_class, array $state): void
    {
        /** @var EntityInterface $external_entity */
        $external_entity = $external_class::fromState($state);
        $violation       = $external_entity->validate($this->default_validator);

        $this->assertNull($violation);
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @dataProvider getFailObjectProvider
     *
     * Unset Config
     *
     * @config [CConfiguration] dPpatients CPatient addr_patient_mandatory 0
     * @config [CConfiguration] dPpatients CPatient cp_patient_mandatory 0
     * @config [CConfiguration] dPpatients CPatient tel_patient_mandatory 0
     * @config [CConfiguration] dPpatients CMedecin medecin_strict 0
     */
    public function testEntityValidationIsKO(string $external_class, array $state): void
    {
        /** @var EntityInterface $external_class */
        $external_entity = $external_class::fromState($state);
        $violation       = $external_entity->validate($this->default_validator);

        $this->assertInstanceOf(SpecificationViolation::class, $violation);
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @dataProvider getFailObjectConfProvider
     *
     * SetConfig
     *
     * @config [CConfiguration] dPpatients CPatient addr_patient_mandatory 1
     * @config [CConfiguration] dPpatients CPatient cp_patient_mandatory 1
     * @config [CConfiguration] dPpatients CPatient tel_patient_mandatory 1
     * @config [CConfiguration] dPpatients CMedecin medecin_strict 1
     */
//    public function testEntityValidationIsKOWithConfig(string $external_class, array $state): void
//    {
//        // test avec full configuration
//        //     TODO : à revoir une fois le problème de configuration réglé
//        $this->markTestSkipped();
//        /** @var EntityInterface $external_class */
//        $external_entity = $external_class::fromState($state);
//        $violation       = $external_entity->validate($this->default_validator);
//
//        $this->assertInstanceOf(SpecificationViolation::class, $violation);
//    }

    public function getValidatedObjectProvider(): array
    {
        $provider = [];
        $provider = array_merge($provider, ["user valide" => $this->generateCustomUser()]);
        //        $provider = array_merge($provider, ["Patient valide" => $this->generateCustomPatient()]);
        $provider = array_merge($provider, ["Medecin valide" => $this->generateCustomMedecin()]);
        $provider = array_merge($provider, ["PlageConsult valide" => $this->generateCustomPlageConsult()]);
        $provider = array_merge($provider, ["Consultation valide" => $this->generateCustomConsultation()]);
        $provider = array_merge($provider, ["Sejour valide" => $this->generateCustomSejour()]);
        $provider = array_merge($provider, ["File valide" => $this->generateCustomFile()]);

        return $provider;
    }

    public function getFailObjectProvider(): array
    {
        $provider = [];
        $provider = array_merge($provider, $this->getNotValidatedUser());
        $provider = array_merge($provider, $this->getNotValidatedPatient());
        $provider = array_merge($provider, $this->getNotValidatedMedecin());
        $provider = array_merge($provider, $this->getNotValidatedPlageConsult());
        $provider = array_merge($provider, $this->getNotValidatedConsultation());
        $provider = array_merge($provider, $this->getNotValidatedSejour());
        $provider = array_merge($provider, $this->getNotValidatedFile());

        return $provider;
    }

    public function getFailObjectConfProvider(): array
    {
        $provider = [];
        $provider = array_merge($provider, $this->getNotValidatedPatientWithConf());
        $provider = array_merge($provider, $this->getNotValidatedMedecinWithConf());

        return $provider;
    }

    public function getNotValidatedUser(): array
    {
        return [
            "user with extId null"          => $this->generateCustomUser(['external_id' => null]),
            "user with username null"       => $this->generateCustomUser(["username" => null]),
            "user with long username"       => $this->generateCustomUser(["username" => $this->completeString(81)]),
            "user with long first_name"     => $this->generateCustomUser(["first_name" => $this->completeString(51)]),
            "user without last_name"        => $this->generateCustomUser(["last_name" => null]),
            "user with long last_name"      => $this->generateCustomUser(["last_name" => $this->completeString(51)]),
            "user with gender invalide"     => $this->generateCustomUser(["gender" => 'r']),
            "user with gender double"       => $this->generateCustomUser(["gender" => 'ff']),
            "user with birthday no DT"      => $this->generateCustomUser(["birthday" => CMbDT::date()]),
            "user with email bad format"    => $this->generateCustomUser(["email" => "@.m"]),
            "user with email bad finish"    => $this->generateCustomUser(["email" => "toto@test.c"]),
            "user with email finish long"   => $this->generateCustomUser(["email" => "toto@test.commo"]),
            "user with email bad begin"     => $this->generateCustomUser(["email" => "@test.com"]),
            "user with email bad diacr"     => $this->generateCustomUser(["email" => "/é%ù@test.com"]),
            "user with email bad no @"      => $this->generateCustomUser(["email" => "tototest.com"]),
            "user with email bad middle"    => $this->generateCustomUser(["email" => "toto@.com"]),
            "user with email with space"    => $this->generateCustomUser(["email" => "toto  @test.com"]),
            "user with email with no point" => $this->generateCustomUser(["email" => "toto@testcom"]),
            "user with email to long"       => $this->generateCustomUser(
                ["email" => $this->completeString(256) . "@test.com"]
            ),
            "user with phone to long"       => $this->generateCustomUser(["phone" => "010203040506"]),
            "user with phone to short"      => $this->generateCustomUser(["phone" => "010203040"]),
            "user with mobile to long"      => $this->generateCustomUser(["mobile" => "010203040506"]),
            "user with mobile to short"     => $this->generateCustomUser(["mobile" => "010203040"]),
            "user with long address"        => $this->generateCustomUser(["address" => $this->completeString(256)]),
            "user with long city"           => $this->generateCustomUser(["city" => $this->completeString(31)]),
            "user with long zip"            => $this->generateCustomUser(["zip" => $this->completeString(6)]),
            "user with short zip"           => $this->generateCustomUser(["zip" => $this->completeString(4)]),
            "user with long country"        => $this->generateCustomUser(["country" => $this->completeString(31)]),
        ];
    }

    public function generateCustomUser(array $attributes = null): array
    {
        $user = [
            'external_id' => 11,
            'username'    => 'toto',
            'last_name'   => 'toto',
        ];
        if ($attributes) {
            foreach ($attributes as $attribute => $value) {
                $user[$attribute] = $value;
            }
        }

        return ["base" => User::class, "user" => $user];
    }

    public function getNotValidatedPatient(): array
    {
        return [
            "patient with extId null"          => $this->generateCustomPatient(['external_id' => null]),
            "patient without nom"              => $this->generateCustomPatient(["nom" => null]),
            "patient with nom to long"         => $this->generateCustomPatient(["nom" => $this->completeString(256)]),
            "patient without prenom"           => $this->generateCustomPatient(["prenom" => null]),
            "patient with prenom to long"      => $this->generateCustomPatient(
                ["prenom" => $this->completeString(256)]
            ),
            "patient with to old naissance"    => $this->generateCustomPatient(
                ["naissance" => new DateTime('1849-12-31')]
            ),
            "patient with to young naissance"  => $this->generateCustomPatient(["naissance" => new DateTime('+1 Day')]),
            "patient with no naissance"        => $this->generateCustomPatient(["naissance" => null]),
            "patient with no \DateTime"        => $this->generateCustomPatient(
                ["naissance" => CMbDT::date('2020/12/12')]
            ),
            "patient with profession to long " => $this->generateCustomPatient(
                ["profession" => $this->completeString(256)]
            ),
            "patient with email bad format"    => $this->generateCustomPatient(["email" => "@.m"]),
            "patient with email bad finish"    => $this->generateCustomPatient(["email" => "toto@test.c"]),
            "patient with email finish long"   => $this->generateCustomPatient(["email" => "toto@test.commo"]),
            "patient with email bad begin"     => $this->generateCustomPatient(["email" => "@test.com"]),
            "patient with email bad diacr"     => $this->generateCustomPatient(["email" => "/é%ù@test.com"]),
            "patient with email bad no @"      => $this->generateCustomPatient(["email" => "tototest.com"]),
            "patient with email bad middle"    => $this->generateCustomPatient(["email" => "toto@.com"]),
            "patient with email with space"    => $this->generateCustomPatient(["email" => "toto  @test.com"]),
            "patient with email with no point" => $this->generateCustomPatient(["email" => "toto@testcom"]),
            "patient with email to long"       => $this->generateCustomPatient(
                ["email" => $this->completeString(256) . "@test.com"]
            ),
            "patient with tel letter"          => $this->generateCustomPatient(["tel" => $this->completeString(10)]),
            "patient with tel to long"         => $this->generateCustomPatient(["tel" => "010203040506"]),
            "patient with tel to short"        => $this->generateCustomPatient(["tel" => "010203040"]),
            "patient with tel2 letter"         => $this->generateCustomPatient(["tel2" => $this->completeString(10)]),
            "patient with tel2 to long"        => $this->generateCustomPatient(["tel2" => "010203040506"]),
            "patient with tel2 to short"       => $this->generateCustomPatient(["tel2" => "010203040"]),
            "patient with tel_autre letter"    => $this->generateCustomPatient(
                ["tel_autre" => $this->completeString(10)]
            ),
            "patient with tel_autre to long"   => $this->generateCustomPatient(["tel_autre" => "010203040506"]),
            "patient with tel_autre to short"  => $this->generateCustomPatient(["tel_autre" => "010203040"]),
            "patient with matricule letter"    => $this->generateCustomPatient(
                ["matricule" => $this->completeString(15)]
            ),
            "patient with matricule to long"   => $this->generateCustomPatient(
                ["matricule" => $this->completeNumber(16)]
            ),
            "patient with matricule to short"  => $this->generateCustomPatient(
                ["matricule" => $this->completeNumber(10)]
            ),
            "patient with civilite bad"        => $this->generateCustomPatient(["civilite" => "a"]),
            "patient with medecin_t long"      => $this->generateCustomPatient(
                ["medecin_traitant" => $this->completeString(12)]
            ),
        ];
    }

    public function getNotValidatedPatientWithConf(): array
    {
        return [
            "patient with adresse null"            => $this->generateCustomPatientConf(["adresse" => null]),
            "patient with ville null"              => $this->generateCustomPatientConf(["ville" => null]),
            "patient with ville to long"           => $this->generateCustomPatientConf(
                ["ville" => $this->completeString(256)]
            ),
            "patient with pays null"               => $this->generateCustomPatientConf(["pays" => null]),
            "patient with pays to long"            => $this->generateCustomPatientConf(
                ["pays" => $this->completeString(256)]
            ),
            "patient with cp to short"             => $this->generateCustomPatientConf(
                ["cp" => $this->completeString(4)]
            ),
            "patient with cp to long"              => $this->generateCustomPatientConf(
                ["cp" => $this->completeString(6)]
            ),
            "patient with nom_jeune_fille to long" => $this->generateCustomPatientConf(
                ["nom_jeune_fille" => $this->completeString(256)]
            ),
            "patient with nom_jeune_fille null"    => $this->generateCustomPatientConf(["nom_jeune_fille" => null]),
            "patient with sexe null"               => $this->generateCustomPatientConf(["sexe" => null]),
            "patient with sexe bad"                => $this->generateCustomPatientConf(["sexe" => 'a']),
            "patient with sexe m +njf conf"        => $this->generateCustomPatientConf(["sexe" => 'm']),
        ];
    }

    public function generateCustomPatient(array $attributes = null): array
    {
        $patient = [
            'external_id' => 33,
            'nom'         => 'toto',
            'prenom'      => 'toto',
            'naissance'   => new DateTime('2000-12-12'),
            'sexe'        => "f",
        ];
        if ($attributes) {
            foreach ($attributes as $attribute => $value) {
                $patient[$attribute] = $value;
            }
        }

        return ["base" => Patient::class, "patient" => $patient];
    }

    public function generateCustomPatientConf(array $attributes = null): array
    {
        $patient = [
            'external_id'     => 33,
            'nom'             => 'toto',
            'prenom'          => 'toto',
            'naissance'       => new DateTime('2000-12-12'),
            'sexe'            => "f",
            'adresse'         => "1 rue du patient",
            'ville'           => "ville du patient",
            'cp'              => "17000",
            'nom_jeune_fille' => "tata",
        ];
        if ($attributes) {
            foreach ($attributes as $attribute => $value) {
                $patient[$attribute] = $value;
            }
        }

        return ["base" => Patient::class, "patient" => $patient];
    }

    public function getNotValidatedMedecin(): array
    {
        return [
            "medecin without extId"            => $this->generateCustomMedecin(['external_id' => null]),
            "medecin with nom null"            => $this->generateCustomMedecin(['nom' => null]),
            "medecin with nom to long"         => $this->generateCustomMedecin(['nom' => $this->completeString(256)]),
            "medecin with prenom long"         => $this->generateCustomMedecin(
                ['prenom' => $this->completeString(256)]
            ),
            "medecin with bad sexe"            => $this->generateCustomMedecin(['sexe' => 'aaa']),
            "medecin with bad titre"           => $this->generateCustomMedecin(['titre' => 'a']),
            "medecin with email bad format"    => $this->generateCustomMedecin(["email" => "@.m"]),
            "medecin with email bad finish"    => $this->generateCustomMedecin(["email" => "toto@test.c"]),
            "medecin with email finish long"   => $this->generateCustomMedecin(["email" => "toto@test.commo"]),
            "medecin with email bad begin"     => $this->generateCustomMedecin(["email" => "@test.com"]),
            "medecin with email bad diacr"     => $this->generateCustomMedecin(["email" => "/é%ù@test.com"]),
            "medecin with email bad no @"      => $this->generateCustomMedecin(["email" => "tototest.com"]),
            "medecin with email bad middle"    => $this->generateCustomMedecin(["email" => "toto@.com"]),
            "medecin with email with space"    => $this->generateCustomMedecin(["email" => "toto  @test.com"]),
            "medecin with email with no point" => $this->generateCustomMedecin(["email" => "toto@testcom"]),
            "medecin with email to long"       => $this->generateCustomMedecin(
                ["email" => $this->completeString(256) . "@test.com"]
            ),
            "medecin with tel letter"          => $this->generateCustomMedecin(["tel" => "dzadazdazd"]),
            "medecin with tel to long"         => $this->generateCustomMedecin(["tel" => "010203040506"]),
            "medecin with tel to short"        => $this->generateCustomMedecin(["tel" => "010203040"]),
            "medecin with tel_autre letter"    => $this->generateCustomMedecin(["tel_autre" => "dzadazdazd"]),
            "medecin with tel_autre to long"   => $this->generateCustomMedecin(["tel_autre" => "010203040506"]),
            "medecin with tel_autre to short"  => $this->generateCustomMedecin(["tel_autre" => "010203040"]),
            "medecin with ville to long"       => $this->generateCustomMedecin(["ville" => $this->completeString(256)]),
            "medecin with adeli character"     => $this->generateCustomMedecin(['adeli' => 'aaaa a a aaa']),
            "medecin with adeli to long"       => $this->generateCustomMedecin(['adeli' => $this->completeNumber(10)]),
            "medecin with adeli to short"      => $this->generateCustomMedecin(['adeli' => $this->completeNumber(8)]),
            "medecin with rpps character"      => $this->generateCustomMedecin(['rpps' => 'aaaa a a aaa']),
            "medecin with rpps to long"        => $this->generateCustomMedecin(['rpps' => $this->completeNumber(12)]),
            "medecin with rpps to short"       => $this->generateCustomMedecin(['rpps' => $this->completeNumber(10)]),
        ];
    }

    public function getNotValidatedMedecinWithConf(): array
    {
        return [
            "medecin with adresse null" => $this->generateCustomMedecinConf(['adresse' => null]),
            "medecin with cp to short"  => $this->generateCustomMedecinConf(["cp" => $this->completeString(4)]),
            "medecin with cp to long"   => $this->generateCustomMedecinConf(["cp" => $this->completeString(6)]),
            "medecin with tel null"     => $this->generateCustomMedecinConf(['tel' => null]),
            "medecin with ville null"   => $this->generateCustomMedecinConf(['ville' => null]),
        ];
    }

    public function generateCustomMedecin(array $attributes = null): array
    {
        $medecin = [
            'external_id' => 33,
            'nom'         => 'toto',
        ];
        if ($attributes) {
            foreach ($attributes as $attribute => $value) {
                $medecin[$attribute] = $value;
            }
        }

        return ["base" => Medecin::class, "medecin" => $medecin];
    }

    public function generateCustomMedecinConf(array $attributes = null): array
    {
        $medecin = [
            'external_id' => 33,
            'nom'         => 'toto',
            'prenom'      => 'toto',
            'naissance'   => new DateTime('2000-12-12'),
            'sexe'        => "f",
            'adresse'     => "1 rue du medecin",
            'tel'         => "0102030405",
            'ville'       => "ville du medecin",
            'cp'          => "17000",
        ];
        if ($attributes) {
            foreach ($attributes as $attribute => $value) {
                $medecin[$attribute] = $value;
            }
        }

        return ["base" => Medecin::class, "medecin" => $medecin];
    }

    public function getNotValidatedPlageConsult(): array
    {
        return [
            "pl_cons with extId null"   => $this->generateCustomPlageConsult(['external_id' => null]),
            "pl_cons with chir_id null" => $this->generateCustomPlageConsult(['chir_id' => null]),
            "pl_cons with date null"    => $this->generateCustomPlageConsult(['date' => null]),
            "pl_cons with date no DT"   => $this->generateCustomPlageConsult(['date' => CMbDT::date()]),
            "pl_cons with freq null"    => $this->generateCustomPlageConsult(['freq' => null]),
            "pl_cons with freq no DT"   => $this->generateCustomPlageConsult(['freq' => CMbDT::time()]),
            "pl_cons with freq <5min"   => $this->generateCustomPlageConsult(['freq' => new DateTime('00:04:59')]),
            "pl_cons with debut null"   => $this->generateCustomPlageConsult(['debut' => null]),
            "pl_cons with debut no DT"  => $this->generateCustomPlageConsult(['debut' => CMbDT::time()]),
            "pl_cons with fin null"     => $this->generateCustomPlageConsult(['fin' => null]),
            "pl_cons with fin no DT"    => $this->generateCustomPlageConsult(['fin' => CMbDT::time()]),
            "pl_cons with fin <debut"   => $this->generateCustomPlageConsult(['fin' => new DateTime('11:59:59')]),
            "pl_cons with libelle long" => $this->generateCustomPlageConsult(['libelle' => $this->completeString(256)]),
        ];
    }

    public function generateCustomPlageConsult(array $attributes = null): array
    {
        $plage_consult = [
            'external_id' => 33,
            'chir_id'     => 11,
            'date'        => new DateTime('1900-12-12'),
            'freq'        => new DateTime('00:05:01'),
            'debut'       => new DateTime('12:00:00'),
            'fin'         => new DateTime('13:13:13'),
        ];
        if ($attributes) {
            foreach ($attributes as $attribute => $value) {
                $plage_consult[$attribute] = $value;
            }
        }

        return ["base" => PlageConsult::class, "plage_consult" => $plage_consult];
    }

    public function getNotValidatedConsultation(): array
    {
        return [
            "consult with extId null"      => $this->generateCustomConsultation(['external_id' => null]),
            "consult with plage_id null"   => $this->generateCustomConsultation(['plageconsult_id' => null]),
            "consult with heure null"      => $this->generateCustomConsultation(['heure' => null]),
            "consult with heure not DT"    => $this->generateCustomConsultation(['heure' => CMbDT::time()]),
            "consult with duree null"      => $this->generateCustomConsultation(['duree' => null]),
            "consult with duree <1"        => $this->generateCustomConsultation(['duree' => 0]),
            "consult with duree >255"      => $this->generateCustomConsultation(['duree' => 256]),
            "consult with motif null"      => $this->generateCustomConsultation(['motif' => null]),
            "consult with chrono string"   => $this->generateCustomConsultation(['chrono' => 'a']),
            "consult with chrono not enum" => $this->generateCustomConsultation(['chrono' => 1]),
            "consult with patient_id long" => $this->generateCustomConsultation(
                ['patient_id' => $this->completeString(12)]
            ),
        ];
    }

    public function generateCustomConsultation(array $attributes = null): array
    {
        $consultation = [
            "external_id"     => 11,
            "plageconsult_id" => 33,
            "heure"           => new DateTime('12:00:00'),
            "duree"           => 1,
            "motif"           => $this->completeString(10),
        ];
        if ($attributes) {
            foreach ($attributes as $attribute => $value) {
                $consultation[$attribute] = $value;
            }
        }

        return ["base" => Consultation::class, "consultation" => $consultation];
    }

    public function getNotValidatedSejour(): array
    {
        return [
            "sejour with extId null"          => $this->generateCustomSejour(['external_id' => null]),
            "sejour with type null"           => $this->generateCustomSejour(['type' => null]),
            "sejour with type not enum"       => $this->generateCustomSejour(['type' => 'a']),
            "sejour with entree_p null"       => $this->generateCustomSejour(['entree_prevue' => null]),
            "sejour with entree_p not DT"     => $this->generateCustomSejour(['entree_prevue' => CMbDT::dateTime()]),
            "sejour with entree_r not DT"     => $this->generateCustomSejour(['entree_reelle' => CMbDT::dateTime()]),
            "sejour with sortie_p null"       => $this->generateCustomSejour(['sortie_prevue' => null]),
            "sejour with sortie_p not DT"     => $this->generateCustomSejour(['sortie_prevue' => CMbDT::dateTime()]),
            "sejour with sortie_p < entree_p" => $this->generateCustomSejour(
                [
                    'sortie_prevue' => new DateTime('2020-12-12 10:10:10'),
                ]
            ),
            "sejour with sortie_r not DT"     => $this->generateCustomSejour(['sortie_reelle' => CMbDT::dateTime()]),
            "sejour with sortie_r < entree_r" => $this->generateCustomSejour(
                [
                    'sortie_reelle' => new DateTime('2020-12-12 10:10:10'),
                ]
            ),
            "sejour with libelle > 255"       => $this->generateCustomSejour(['libelle' => $this->completeString(256)]),
            "sejour with patient null"        => $this->generateCustomSejour(['patient_id' => null]),
            "sejour with praticien null"      => $this->generateCustomSejour(['praticien_id' => null]),
            "sejour with group null"          => $this->generateCustomSejour(['group_id' => null]),
        ];
    }

    public function generateCustomSejour(array $attributes = null): array
    {
        $sejour = [
            "external_id"   => 11,
            "type"          => 'comp',
            "entree"        => new DateTime('2020-12-12 12:12:12'),
            "entree_prevue" => new DateTime('2020-12-12 12:12:12'),
            "sortie"        => new DateTime('2020-12-12 13:13:13'),
            "sortie_prevue" => new DateTime('2020-12-12 13:13:13'),
            "patient_id"    => 22,
            "praticien_id"  => 33,
            "group_id"      => 44,
        ];
        if ($attributes) {
            foreach ($attributes as $attribute => $value) {
                $sejour[$attribute] = $value;
            }
        }

        return ["base" => Sejour::class, "sejour" => $sejour];
    }


    public function getNotValidatedFile(): array
    {
        return [
            "file with extId null"      => $this->generateCustomFile(['external_id' => null]),
            "file with file_name null"  => $this->generateCustomFile(['file_name' => null]),
            "file with file_name long"  => $this->generateCustomFile(['file_name' => $this->completeString(256)]),
            "file with file_date null"  => $this->generateCustomFile(['file_date' => null]),
            "file with file_date no DT" => $this->generateCustomFile(['file_date' => CMbDT::dateTime()]),
            "file with file_type long"  => $this->generateCustomFile(['file_type' => $this->completeString(256)]),
        ];
    }

    public function generateCustomFile(array $attributes = null): array
    {
        $file = [
            "external_id" => 11,
            "file_date"   => new DateTime('2020-12-12 12:12:12'),
            "file_name"   => 'file_toto',
            "author_id"   => 22,
        ];
        if ($attributes) {
            foreach ($attributes as $attribute => $value) {
                $file[$attribute] = $value;
            }
        }

        return ["base" => File::class, "file" => $file];
    }

    private function completeString(int $length): string
    {
        return str_pad('', $length, 'a', STR_PAD_RIGHT);
    }

    private function completeNumber(int $length): string
    {
        return str_pad('', $length, '1', STR_PAD_RIGHT);
    }
}

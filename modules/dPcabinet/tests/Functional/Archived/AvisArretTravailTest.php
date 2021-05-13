<?php
/**
 * @package Mediboard\Cabinet\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CMbDT;
use Ox\Tests\SeleniumTestMediboard;

/**
 * Tests de création des avis d'arrets de travail
 *
 * @description Test the creation of acts (CCAM and NGAP) on the consultations
 *
 * @screen ConsultationPage
 */
class AvisArretTravailTest extends SeleniumTestMediboard {
  /** @var AvisArretTravailPage */
  public $page;

  public $chir_name = 'CHIR Test';
  public $patient_name = 'PatientAat';

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->page = new AvisArretTravailPage($this);
//  }

  /**
   * Teste la création d'un arret de travail initial à temps complet, ainsi que les points suivants :
   *   - L'obligation de saisir un motif, et l'obligation de saisir un complément pour certains motifs
   *   - Vérifie que la durée d'un arrêt inital à temps complet ne peut dépasser 364 jours
   *   - L'obligation de saisie d'une date d'accident tiers si la case est cochée,
   *     ainsi que l'impossibilité de saisir une date postérieure à la date du jour ou de l'arrêt
   *   - L'impossibilité de cocher un accident tiers et un patient pensionné de guerre
   *   - Teste la sélection de durée indicatives
   *   - L'impossibilité de saisir une situation patient à RSI ou NSA pour le régime général
   *   - Les autorisations de sorties, et l'impossibilité de saisir des dates de sorties postérieures à la date de fin de l'arrêt
   *   - L'obligation de saisir la date de cessation d'activité dans le cas d'un patient sans emploi
   */
  public function testArretInitalTempsComplet() {
    $this->importObject('dPcabinet/tests/Functional/data/aat_regime_gen.xml');
    $this->page->openConsultation($this->patient_name);
    $this->page->openArretTravail();
    $this->page->setType('initial');
    $this->page->setNature('TC');

    /* Vérifie que la saisie d'un motif est obligatoire */
    $this->assertTrue($this->page->isFieldNotNull('motif_id'));

    /* Renseigne un motif, et vérifie que le complément est bien obligatoire pour ce motif */
    $this->page->setMotif('Autres maladies virales et bactériennes');
    $this->assertTrue($this->page->isFieldNotNull('complement_motif'));
    $this->page->setComplementMotif('Infection virale éruptive aiguë : rougeole');

    $this->page->goToStep('context', 'duration');

    /* Renseigne une durée d'une valeur supérieure à 364 jours,
     * et vérifie que la durée est bien modifiée pour être conforme à la durée légale (364 jours maximum)
     * Vérifie également qu'un message d'avertissement est bien affiché
     */
    $this->page->setDuree(395);
    $this->assertEquals(364, $this->page->getDuree());
    $this->assertTrue($this->page->isMessageDisplayed('AAT_duree_tc_legal'));

    $this->page->goToStep('duration', 'patient_situation');

    /* Vérifie que la date de cessation d'activité est obligatoire quand la situation du patient est sans-emploi */
    $this->page->setPatientActivite('SE');
    $this->page->isFieldNotNull('patient_date_sans_activite');
    /* Vérifie que la date de cessation d'activité ne peux être postérieure à la date de l'arrêt */
    $this->page->setDateCessationActivite('+1 DAYS');
    $this->assertEquals(CMbDT::date(), $this->page->getDateCessationActivite());

    $this->page->goToStep('patient_situation', 'sorties');
    $this->page->checkSorties();

    /* Vérification que la date de sorties autorisées ne peut être antérieure à la date de l'arrêt */
    $this->page->setDateSorties('-1 day');
    $this->assertEquals(CMbDT::date(), $this->page->getDateSorties());
    
    /* Autorisation de sorties sans restrictions */
    $this->page->checkSortiesSansRestrictions();

    /* Vérification que la saisie d'un motif est obligatoire pour les sorties autorisées sans restrictions */
    $this->assertTrue($this->page->isFieldNotNull('sorties_sans_restriction_motif'));
    $this->page->setMotifSortiesSansRestrictions('Deuxième phase du traitement');

    /* Vérification que la date de sortie sans restriction ne peut être antérieure à la date de l'arrêt,
     * et à la date de sorties avec restriciton */
    $this->page->setDateSortiesSansRestrictions('-1 day');
    $this->assertEquals(CMbDT::date(), $this->page->getDateSortiesSansRestrictions());
    $this->page->setDateSorties('+2 days');
    $this->page->setDateSortiesSansRestrictions('+1 day');
    $this->assertEquals(CMbDT::date('+2 days'), $this->page->getDateSortiesSansRestrictions());

    /* Validation de l'arrêt de travail */
    $this->page->goToStep('sorties', 'summary');
    $this->page->saveAAT();
    $this->assertContains('Arrêt de travail créé', $this->page->getSystemMessage());
  }

  /**
   * Teste la création d'un arret de travail initial à temps complet concernant l'ALD du patient, ainsi que les points suivants :
   *   - L'obligation de saisir un complement du motif quand l'arrêt concerne l'ALD du patient
   *   - Vérifie que l'activité du patient ne peut être que Non salarié agricole pour le régime 02,
   *     avec un 3ème chiffre du code caisse à 1 ou 2
   */
  public function testArretInitialTempsCompletALD() {
    $this->importObject('dPcabinet/tests/Functional/data/aat_regime_nsa.xml');
    $this->page->openConsultation($this->patient_name);
    $this->page->openArretTravail();
    $this->page->setType('initial');
    $this->page->setNature('TC');

    /* Coche l'ALD à temps complet et vérifie que le complément est obligatoire, et qu'il est impossible de saisir un motif */
    $this->page->checkALDTempsComplet();
    $this->assertTrue($this->page->isFieldNotNull('complement_motif'));
    $this->assertFalse($this->page->isFieldNotNull('motif_id'));
    $this->assertFalse($this->page->isFieldDisplayed('motif_id'));
    $this->page->setComplementMotif('Eléments médicaux');

    $this->page->goToStep('context', 'duration');
    $this->page->setDuree(20);

    $this->page->goToStep('duration', 'patient_situation');

    /* Vérifie que l'activté du patient est bien renseigné à NS */
    $this->assertEquals('NS', $this->page->getPatientActivite());

    /* Validation de l'arrêt de travail */
    $this->page->goToStep('patient_situation', 'summary');
    $this->page->saveAAT();
    $this->assertContains('Arrêt de travail créé', $this->page->getSystemMessage());
  }

  /**
   * Teste la création d'un arret de travail initial à temps complet concernant un état pathologique de la grossesse,
   * ainsi que les points suivants :
   *   - Vérifie l'affichage d'un message d'erreur si ALD et Maternité sont cochés
   *   - Vérification de l'affichage d'un message d'erreur si la durée est supérieure à 14 jours
   *   - Vérifie que les sorties sont bien autorisées par défaut, avec des date égales à la date de l'arrêt
   */
  public function testArretInitialTempsCompletMater() {
    $this->importObject('dPcabinet/tests/Functional/data/aat_regime_gen.xml');
    $this->page->openConsultation($this->patient_name);
    $this->page->openArretTravail();
    $this->page->setType('initial');
    $this->page->setNature('TC');

    /* Coche l'état pathologique de la grossesse et vérifie que la saisie d'un motif est impossible */
    $this->page->checkMaternite();
    $this->assertTrue($this->page->isFieldDisplayed('complement_motif'));
    $this->assertFalse($this->page->isFieldNotNull('motif_id'));
    $this->assertFalse($this->page->isFieldDisplayed('motif_id'));

    /* Vérifie l'affichage d'un message d'erreur si l'ALD et l'état pathologique de la grossesse sont cochés */
    $this->page->checkALDTempsComplet();
    $this->assertTrue($this->page->isMessageDisplayed('AAT_ald_mater_error'));
    $this->page->checkALDTempsComplet();

    $this->page->goToStep('context', 'duration');
    /* Renseigne la durée est vérifie qu'un message est affichée si celle-ci est supérieure à 14 jours */
    $this->page->setDuree(20);
    $this->assertTrue($this->page->isMessageDisplayed('AAT_duree_mater_legal'));

    $this->page->goToStep('duration', 'patient_situation');

    /* Vérifie que l'activté du patient est bien renseigné à NS */
    $this->page->setPatientActivite('FO');

    $this->page->goToStep('patient_situation', 'sorties');
    /* Vérifie que les sorties sans restrictions et avec restrictions sont autorisées par défaut,
     * que les date de sorties sont égales à la date de l'arrêt
     * et que le motif de sorties sans restrictions est renseigné */
    $this->assertTrue($this->page->sortiesAutorisees());
    $this->assertEquals(CMbDT::date(), $this->page->getDateSorties());
    $this->assertTrue($this->page->sortiesSansRestrictionsAutorisees());
    $this->assertEquals(CMbDT::date(), $this->page->getDateSortiesSansRestrictions());
    $this->assertNotEmpty($this->page->getMotifSortiesSansRestrictions());

    /* Validation de l'arrêt de travail */
    $this->page->goToStep('sorties', 'summary');
    $this->page->saveAAT();
    $this->assertContains('Arrêt de travail créé', $this->page->getSystemMessage());
  }

  /**
   * Teste la création d'un arrêt de travail initial causé par un accident tiers, et vérifie les points suivants :
   *   - La sélection d'une durée indicative
   *   - L'obligation de saisir la date de l'accident (et que la date ne peut être postérieure à la date de l'arrêt)
   *   - L'impossibilité de cocher l'accident tiers et le patient pensionné de la guerre
   *   - Que l'activité des patients du régime 03 est bien renseigné à PI par défaut
   */
  public function testArretInitialTempsCompletAccidentTiers() {
    $this->importObject('dPcabinet/tests/Functional/data/aat_regime_rsi.xml');
    $this->page->openConsultation($this->patient_name);
    $this->page->openArretTravail();
    $this->page->setType('initial');
    $this->page->setNature('TC');

    /* Renseigne un motif */
    $this->page->setMotif('Grippe saisonnière');

    $this->page->goToStep('context', 'duration');

    /* Sélectionne la durée indicative */
    $this->page->selectDureeIndicative();
    $this->assertEquals(5, $this->page->getDuree());

    /* Vérifie que le champ Patient pensionné de guerre peut être coché */
    $this->assertTrue($this->page->isFieldEnabled('__pension_guerre'));

    /* Coche l'accident tiers, et vérifie que la date de l'accident ne peut être postérieure à la date de l'arrêt */
    $this->page->checkAccidentTiers();
    $this->assertTrue($this->page->isFieldNotNull('date_accident'));
    $this->page->setDateAccident('+2 days');
    $this->assertEquals(CMbDT::date(), $this->page->getDateAccident());
    
    /* Vérifie que le champ Patient pensionné de guerre ne peut être coché si l'accident est coché */
    $this->assertFalse($this->page->isFieldEnabled('__pension_guerre'));

    $this->page->goToStep('duration', 'patient_situation');

    /* Vérifie que l'activté du patient est bien renseigné à PI */
    $this->assertEquals('PI', $this->page->getPatientActivite());

    /* Validation de l'arrêt de travail */
    $this->page->goToStep('patient_situation', 'summary');
    $this->page->saveAAT();
    $this->assertContains('Arrêt de travail créé', $this->page->getSystemMessage());
  }

  /**
   * Teste la création d'un arrêt de travail de prolongation à temps complet, et vérifie les points suivants :
   *   - L'obligation de saisir le type de prescripteur pour les prolongations
   *   - L'obligation de saisir un complément lorsque le type de prescripteur est "autre"
   *   - L'impossibilité de sélectionner une durée indicative pour les prolongations
   *   - Qu'il est impossible de saisir une durée supérieure à 182 jours pour les prolongations
   */
  public function testArretProlongationTempsComplet() {
    $this->importObject('dPcabinet/tests/Functional/data/aat_regime_gen.xml');
    $this->page->openConsultation($this->patient_name);
    $this->page->openArretTravail();
    $this->page->setType('prolongation');
    
    /* Vérifie que la saisie du type de prescripteur est obligatoire */
    $this->assertTrue($this->page->isFieldNotNull('prescripteur_type'));
    $this->page->setTypePrescripteur('8');

    /* Vérifie que dans le cas ou le type de prescripteur est "autre", la saisie d'un complément d'information est obligatoire */
    $this->assertTrue($this->page->isFieldNotNull('prescripteur_text'));
    $this->page->setTextePrescripteur('Médecin hors zone résidence habituelle');

    $this->page->setNature('TC');

    /* Renseigne un motif */
    $this->page->setMotif('Grippe saisonnière');

    $this->page->goToStep('context', 'duration');

    /* Vérifie qu'il est impossible de sélectionner une durée indicative pour les arrêts de prolongation */
    $this->assertFalse($this->page->isButtonEnabled('AAT_button_show_duration'));

    /* Renseigne une durée d'une valeur supérieure à 182 jours,
     * et vérifie que la durée est bien modifiée pour être conforme à la durée légale (182 jours maximum)
     * Vérifie également qu'un message d'avertissement est bien affiché
     */
    $this->page->setDuree(200);
    $this->assertEquals(182, $this->page->getDuree());
    $this->assertTrue($this->page->isMessageDisplayed('AAT_duree_tc_legal'));

    $this->page->goToStep('duration', 'patient_situation');

    $this->page->setPatientActivite('FO');

    /* Validation de l'arrêt de travail */
    $this->page->goToStep('patient_situation', 'summary');
    $this->page->saveAAT();
    $this->assertContains('Arrêt de travail créé', $this->page->getSystemMessage());
  }

  /**
   * Teste la création d'un arrêt initial à temps partiel, et vérifie les points suivants :
   *   - L'impossibilité de sélectionner une durée indicative pour les arrêts à temps partiel
   *   - L'impossibilité de saisir une durée supérieure à 364 jours
   *   - L' impossibilité de saisir les autorisations de sorties pour les arrêts à temps partiel
   */
  public function testArretInitalTempsPartiel() {
    $this->importObject('dPcabinet/tests/Functional/data/aat_regime_gen.xml');
    $this->page->openConsultation($this->patient_name);
    $this->page->openArretTravail();
    $this->page->setType('initial');
    $this->page->setNature('TP');

    /* Renseigne un motif */
    $this->page->setMotif('Grippe saisonnière');

    $this->page->goToStep('context', 'duration');

    /* Vérifie qu'il est impossible de sélectionner une durée indicative pour les arrêts à temps partiel */
    $this->assertFalse($this->page->isButtonEnabled('AAT_button_show_duration'));

    /* Renseigne une durée d'une valeur supérieure à 364 jours,
     * et vérifie que la durée est bien modifiée pour être conforme à la durée légale (364 jours maximum)
     * Vérifie également qu'un message d'avertissement est bien affiché
     */
    $this->page->setDuree(380);
    $this->assertEquals(364, $this->page->getDuree());
    $this->assertTrue($this->page->isMessageDisplayed('AAT_duree_tp_legal'));

    $this->page->goToStep('duration', 'patient_situation');

    $this->page->setPatientActivite('FO');

    $this->page->goToStep('patient_situation', 'sorties');

    /* Vérifie qu'il n'est pas possible de saisir les sorties autorisées pour les arrêts à temps partiel */
    $this->assertTrue($this->page->isMessageDisplayed('AAT_msg_sorties_temps_partiel'));
    $this->assertFalse($this->page->isFieldDisplayed('__sorties'));

    /* Validation de l'arrêt de travail */
    $this->page->goToStep('sorties', 'summary');
    $this->page->saveAAT();
    $this->assertContains('Arrêt de travail créé', $this->page->getSystemMessage());
  }

  /**
   * Teste la création d'un arrêt de travail à temps partiel concernant l'ALD du patient, et vérifie les points suivants :
   *   - L'obligation de saisir un complément d'information quand l'arrêt concerne l'ALD du patient
   *   - L'impossibilité de saisir un motif d'arrêt
   */
  public function testArretInitalTempsPartielALD() {
    $this->importObject('dPcabinet/tests/Functional/data/aat_regime_gen.xml');
    $this->page->openConsultation($this->patient_name);
    $this->page->openArretTravail();
    $this->page->setType('initial');
    $this->page->setNature('TP');

    /* Coche l'ALD à temps partiel et vérifie que le complément est obligatoire, et qu'il est impossible de saisir un motif */
    $this->page->checkALDTempsPartiel();
    $this->assertTrue($this->page->isFieldNotNull('complement_motif'));
    $this->assertFalse($this->page->isFieldNotNull('motif_id'));
    $this->assertFalse($this->page->isFieldDisplayed('motif_id'));
    $this->page->setComplementMotif('Eléments médicaux');

    $this->page->goToStep('context', 'duration');

    $this->page->setDuree(20);

    $this->page->goToStep('duration', 'patient_situation');

    $this->page->setPatientActivite('FO');

    /* Validation de l'arrêt de travail */
    $this->page->goToStep('patient_situation', 'summary');
    $this->page->saveAAT();
    $this->assertContains('Arrêt de travail créé', $this->page->getSystemMessage());
  }

  /**
   * Teste la création d'un arrêt à temps complet avec reprise à temps partiel, et vérifie les points suivants :
   *   - L'impossibilité de saisir une durée d'arrêt à temps complet supérieure à 15 jours
   *   - Que la date de début de la reprise est égale à la fin de l'arrêt temps complet + 1 jour
   *   - Qu'il est impossible de saisir une durée de reprise supérieure à 364 jours
   */
  public function testArretTempsCompletRepriseTempsPartiel() {
    $this->importObject('dPcabinet/tests/Functional/data/aat_regime_gen.xml');
    $this->page->openConsultation($this->patient_name);
    $this->page->openArretTravail();
    $this->page->setType('initial');
    $this->page->setNature('TCP');

    /* Renseigne un motif */
    $this->page->setMotif('Grippe saisonnière');

    $this->page->goToStep('context', 'duration');

    /* Vérifie que la durée à temps complet ne peut être supérieure à 15 jours */
    $this->page->setDuree(20);
    $this->assertEquals(15, $this->page->getDuree());
    $this->assertTrue($this->page->isMessageDisplayed('AAT_duree_nature_tcp_legal'));

    /* Vérifie que le début de la reprise à temps partiel est égale à la fin du temps complet + 1 jour */
    $this->assertEquals(CMbDT::date('+1 days', $this->page->getDateFinArret()), $this->page->getDebutReprise());

    /* Vérifie que la durée maximum de la reprise est de 364 jours pour un arrêt inital */
    $this->page->setDureeReprise(380);
    $this->assertEquals(364, $this->page->getDureeReprise());

    $this->page->goToStep('duration', 'patient_situation');

    $this->page->setPatientActivite('FO');

    /* Validation de l'arrêt de travail */
    $this->page->goToStep('patient_situation', 'summary');
    $this->page->saveAAT();
    $this->assertContains('Arrêt de travail créé', $this->page->getSystemMessage());
  }

  /**
   * Teste la création d'un arrêt de prolongation en TCP concernant l'ALD du patient, et vérifie les points suivants :
   *   - Que la saisi du complément est obligatoire
   *   - Qu'il est impossible de saisir une durée de reprise supérieure à 182 jours
   */
  public function testArretTempsCompletRepriseTempsPartielALD() {
    $this->importObject('dPcabinet/tests/Functional/data/aat_regime_gen.xml');
    $this->page->openConsultation($this->patient_name);
    $this->page->openArretTravail();
    $this->page->setType('prolongation');
    $this->page->setNature('TCP');
    $this->page->setTypePrescripteur('MT');

    /* Coche l'ALD et vérifie que le complément est obligatoire, et qu'il est impossible de saisir un motif */
    $this->page->checkALDTempsComplet();
    $this->page->checkALDTempsPartiel();
    $this->assertTrue($this->page->isFieldNotNull('complement_motif'));
    $this->assertFalse($this->page->isFieldNotNull('motif_id'));
    $this->assertFalse($this->page->isFieldDisplayed('motif_id'));
    $this->page->setComplementMotif('Eléments médicaux');

    $this->page->goToStep('context', 'duration');

    $this->page->setDuree(10);

    /* Vérifie que la durée maximum de la reprise est de 182 jours pour un arrêt de prolongation */
    $this->page->setDureeReprise(200);
    $this->assertEquals(182, $this->page->getDureeReprise());

    $this->page->goToStep('duration', 'patient_situation');

    $this->page->setPatientActivite('FO');

    /* Validation de l'arrêt de travail */
    $this->page->goToStep('patient_situation', 'summary');
    $this->page->saveAAT();
    $this->assertContains('Arrêt de travail créé', $this->page->getSystemMessage());
  }
}
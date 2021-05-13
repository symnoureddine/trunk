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
 * Tests de cr�ation des avis d'arrets de travail
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
   * Teste la cr�ation d'un arret de travail initial � temps complet, ainsi que les points suivants :
   *   - L'obligation de saisir un motif, et l'obligation de saisir un compl�ment pour certains motifs
   *   - V�rifie que la dur�e d'un arr�t inital � temps complet ne peut d�passer 364 jours
   *   - L'obligation de saisie d'une date d'accident tiers si la case est coch�e,
   *     ainsi que l'impossibilit� de saisir une date post�rieure � la date du jour ou de l'arr�t
   *   - L'impossibilit� de cocher un accident tiers et un patient pensionn� de guerre
   *   - Teste la s�lection de dur�e indicatives
   *   - L'impossibilit� de saisir une situation patient � RSI ou NSA pour le r�gime g�n�ral
   *   - Les autorisations de sorties, et l'impossibilit� de saisir des dates de sorties post�rieures � la date de fin de l'arr�t
   *   - L'obligation de saisir la date de cessation d'activit� dans le cas d'un patient sans emploi
   */
  public function testArretInitalTempsComplet() {
    $this->importObject('dPcabinet/tests/Functional/data/aat_regime_gen.xml');
    $this->page->openConsultation($this->patient_name);
    $this->page->openArretTravail();
    $this->page->setType('initial');
    $this->page->setNature('TC');

    /* V�rifie que la saisie d'un motif est obligatoire */
    $this->assertTrue($this->page->isFieldNotNull('motif_id'));

    /* Renseigne un motif, et v�rifie que le compl�ment est bien obligatoire pour ce motif */
    $this->page->setMotif('Autres maladies virales et bact�riennes');
    $this->assertTrue($this->page->isFieldNotNull('complement_motif'));
    $this->page->setComplementMotif('Infection virale �ruptive aigu� : rougeole');

    $this->page->goToStep('context', 'duration');

    /* Renseigne une dur�e d'une valeur sup�rieure � 364 jours,
     * et v�rifie que la dur�e est bien modifi�e pour �tre conforme � la dur�e l�gale (364 jours maximum)
     * V�rifie �galement qu'un message d'avertissement est bien affich�
     */
    $this->page->setDuree(395);
    $this->assertEquals(364, $this->page->getDuree());
    $this->assertTrue($this->page->isMessageDisplayed('AAT_duree_tc_legal'));

    $this->page->goToStep('duration', 'patient_situation');

    /* V�rifie que la date de cessation d'activit� est obligatoire quand la situation du patient est sans-emploi */
    $this->page->setPatientActivite('SE');
    $this->page->isFieldNotNull('patient_date_sans_activite');
    /* V�rifie que la date de cessation d'activit� ne peux �tre post�rieure � la date de l'arr�t */
    $this->page->setDateCessationActivite('+1 DAYS');
    $this->assertEquals(CMbDT::date(), $this->page->getDateCessationActivite());

    $this->page->goToStep('patient_situation', 'sorties');
    $this->page->checkSorties();

    /* V�rification que la date de sorties autoris�es ne peut �tre ant�rieure � la date de l'arr�t */
    $this->page->setDateSorties('-1 day');
    $this->assertEquals(CMbDT::date(), $this->page->getDateSorties());
    
    /* Autorisation de sorties sans restrictions */
    $this->page->checkSortiesSansRestrictions();

    /* V�rification que la saisie d'un motif est obligatoire pour les sorties autoris�es sans restrictions */
    $this->assertTrue($this->page->isFieldNotNull('sorties_sans_restriction_motif'));
    $this->page->setMotifSortiesSansRestrictions('Deuxi�me phase du traitement');

    /* V�rification que la date de sortie sans restriction ne peut �tre ant�rieure � la date de l'arr�t,
     * et � la date de sorties avec restriciton */
    $this->page->setDateSortiesSansRestrictions('-1 day');
    $this->assertEquals(CMbDT::date(), $this->page->getDateSortiesSansRestrictions());
    $this->page->setDateSorties('+2 days');
    $this->page->setDateSortiesSansRestrictions('+1 day');
    $this->assertEquals(CMbDT::date('+2 days'), $this->page->getDateSortiesSansRestrictions());

    /* Validation de l'arr�t de travail */
    $this->page->goToStep('sorties', 'summary');
    $this->page->saveAAT();
    $this->assertContains('Arr�t de travail cr��', $this->page->getSystemMessage());
  }

  /**
   * Teste la cr�ation d'un arret de travail initial � temps complet concernant l'ALD du patient, ainsi que les points suivants :
   *   - L'obligation de saisir un complement du motif quand l'arr�t concerne l'ALD du patient
   *   - V�rifie que l'activit� du patient ne peut �tre que Non salari� agricole pour le r�gime 02,
   *     avec un 3�me chiffre du code caisse � 1 ou 2
   */
  public function testArretInitialTempsCompletALD() {
    $this->importObject('dPcabinet/tests/Functional/data/aat_regime_nsa.xml');
    $this->page->openConsultation($this->patient_name);
    $this->page->openArretTravail();
    $this->page->setType('initial');
    $this->page->setNature('TC');

    /* Coche l'ALD � temps complet et v�rifie que le compl�ment est obligatoire, et qu'il est impossible de saisir un motif */
    $this->page->checkALDTempsComplet();
    $this->assertTrue($this->page->isFieldNotNull('complement_motif'));
    $this->assertFalse($this->page->isFieldNotNull('motif_id'));
    $this->assertFalse($this->page->isFieldDisplayed('motif_id'));
    $this->page->setComplementMotif('El�ments m�dicaux');

    $this->page->goToStep('context', 'duration');
    $this->page->setDuree(20);

    $this->page->goToStep('duration', 'patient_situation');

    /* V�rifie que l'activt� du patient est bien renseign� � NS */
    $this->assertEquals('NS', $this->page->getPatientActivite());

    /* Validation de l'arr�t de travail */
    $this->page->goToStep('patient_situation', 'summary');
    $this->page->saveAAT();
    $this->assertContains('Arr�t de travail cr��', $this->page->getSystemMessage());
  }

  /**
   * Teste la cr�ation d'un arret de travail initial � temps complet concernant un �tat pathologique de la grossesse,
   * ainsi que les points suivants :
   *   - V�rifie l'affichage d'un message d'erreur si ALD et Maternit� sont coch�s
   *   - V�rification de l'affichage d'un message d'erreur si la dur�e est sup�rieure � 14 jours
   *   - V�rifie que les sorties sont bien autoris�es par d�faut, avec des date �gales � la date de l'arr�t
   */
  public function testArretInitialTempsCompletMater() {
    $this->importObject('dPcabinet/tests/Functional/data/aat_regime_gen.xml');
    $this->page->openConsultation($this->patient_name);
    $this->page->openArretTravail();
    $this->page->setType('initial');
    $this->page->setNature('TC');

    /* Coche l'�tat pathologique de la grossesse et v�rifie que la saisie d'un motif est impossible */
    $this->page->checkMaternite();
    $this->assertTrue($this->page->isFieldDisplayed('complement_motif'));
    $this->assertFalse($this->page->isFieldNotNull('motif_id'));
    $this->assertFalse($this->page->isFieldDisplayed('motif_id'));

    /* V�rifie l'affichage d'un message d'erreur si l'ALD et l'�tat pathologique de la grossesse sont coch�s */
    $this->page->checkALDTempsComplet();
    $this->assertTrue($this->page->isMessageDisplayed('AAT_ald_mater_error'));
    $this->page->checkALDTempsComplet();

    $this->page->goToStep('context', 'duration');
    /* Renseigne la dur�e est v�rifie qu'un message est affich�e si celle-ci est sup�rieure � 14 jours */
    $this->page->setDuree(20);
    $this->assertTrue($this->page->isMessageDisplayed('AAT_duree_mater_legal'));

    $this->page->goToStep('duration', 'patient_situation');

    /* V�rifie que l'activt� du patient est bien renseign� � NS */
    $this->page->setPatientActivite('FO');

    $this->page->goToStep('patient_situation', 'sorties');
    /* V�rifie que les sorties sans restrictions et avec restrictions sont autoris�es par d�faut,
     * que les date de sorties sont �gales � la date de l'arr�t
     * et que le motif de sorties sans restrictions est renseign� */
    $this->assertTrue($this->page->sortiesAutorisees());
    $this->assertEquals(CMbDT::date(), $this->page->getDateSorties());
    $this->assertTrue($this->page->sortiesSansRestrictionsAutorisees());
    $this->assertEquals(CMbDT::date(), $this->page->getDateSortiesSansRestrictions());
    $this->assertNotEmpty($this->page->getMotifSortiesSansRestrictions());

    /* Validation de l'arr�t de travail */
    $this->page->goToStep('sorties', 'summary');
    $this->page->saveAAT();
    $this->assertContains('Arr�t de travail cr��', $this->page->getSystemMessage());
  }

  /**
   * Teste la cr�ation d'un arr�t de travail initial caus� par un accident tiers, et v�rifie les points suivants :
   *   - La s�lection d'une dur�e indicative
   *   - L'obligation de saisir la date de l'accident (et que la date ne peut �tre post�rieure � la date de l'arr�t)
   *   - L'impossibilit� de cocher l'accident tiers et le patient pensionn� de la guerre
   *   - Que l'activit� des patients du r�gime 03 est bien renseign� � PI par d�faut
   */
  public function testArretInitialTempsCompletAccidentTiers() {
    $this->importObject('dPcabinet/tests/Functional/data/aat_regime_rsi.xml');
    $this->page->openConsultation($this->patient_name);
    $this->page->openArretTravail();
    $this->page->setType('initial');
    $this->page->setNature('TC');

    /* Renseigne un motif */
    $this->page->setMotif('Grippe saisonni�re');

    $this->page->goToStep('context', 'duration');

    /* S�lectionne la dur�e indicative */
    $this->page->selectDureeIndicative();
    $this->assertEquals(5, $this->page->getDuree());

    /* V�rifie que le champ Patient pensionn� de guerre peut �tre coch� */
    $this->assertTrue($this->page->isFieldEnabled('__pension_guerre'));

    /* Coche l'accident tiers, et v�rifie que la date de l'accident ne peut �tre post�rieure � la date de l'arr�t */
    $this->page->checkAccidentTiers();
    $this->assertTrue($this->page->isFieldNotNull('date_accident'));
    $this->page->setDateAccident('+2 days');
    $this->assertEquals(CMbDT::date(), $this->page->getDateAccident());
    
    /* V�rifie que le champ Patient pensionn� de guerre ne peut �tre coch� si l'accident est coch� */
    $this->assertFalse($this->page->isFieldEnabled('__pension_guerre'));

    $this->page->goToStep('duration', 'patient_situation');

    /* V�rifie que l'activt� du patient est bien renseign� � PI */
    $this->assertEquals('PI', $this->page->getPatientActivite());

    /* Validation de l'arr�t de travail */
    $this->page->goToStep('patient_situation', 'summary');
    $this->page->saveAAT();
    $this->assertContains('Arr�t de travail cr��', $this->page->getSystemMessage());
  }

  /**
   * Teste la cr�ation d'un arr�t de travail de prolongation � temps complet, et v�rifie les points suivants :
   *   - L'obligation de saisir le type de prescripteur pour les prolongations
   *   - L'obligation de saisir un compl�ment lorsque le type de prescripteur est "autre"
   *   - L'impossibilit� de s�lectionner une dur�e indicative pour les prolongations
   *   - Qu'il est impossible de saisir une dur�e sup�rieure � 182 jours pour les prolongations
   */
  public function testArretProlongationTempsComplet() {
    $this->importObject('dPcabinet/tests/Functional/data/aat_regime_gen.xml');
    $this->page->openConsultation($this->patient_name);
    $this->page->openArretTravail();
    $this->page->setType('prolongation');
    
    /* V�rifie que la saisie du type de prescripteur est obligatoire */
    $this->assertTrue($this->page->isFieldNotNull('prescripteur_type'));
    $this->page->setTypePrescripteur('8');

    /* V�rifie que dans le cas ou le type de prescripteur est "autre", la saisie d'un compl�ment d'information est obligatoire */
    $this->assertTrue($this->page->isFieldNotNull('prescripteur_text'));
    $this->page->setTextePrescripteur('M�decin hors zone r�sidence habituelle');

    $this->page->setNature('TC');

    /* Renseigne un motif */
    $this->page->setMotif('Grippe saisonni�re');

    $this->page->goToStep('context', 'duration');

    /* V�rifie qu'il est impossible de s�lectionner une dur�e indicative pour les arr�ts de prolongation */
    $this->assertFalse($this->page->isButtonEnabled('AAT_button_show_duration'));

    /* Renseigne une dur�e d'une valeur sup�rieure � 182 jours,
     * et v�rifie que la dur�e est bien modifi�e pour �tre conforme � la dur�e l�gale (182 jours maximum)
     * V�rifie �galement qu'un message d'avertissement est bien affich�
     */
    $this->page->setDuree(200);
    $this->assertEquals(182, $this->page->getDuree());
    $this->assertTrue($this->page->isMessageDisplayed('AAT_duree_tc_legal'));

    $this->page->goToStep('duration', 'patient_situation');

    $this->page->setPatientActivite('FO');

    /* Validation de l'arr�t de travail */
    $this->page->goToStep('patient_situation', 'summary');
    $this->page->saveAAT();
    $this->assertContains('Arr�t de travail cr��', $this->page->getSystemMessage());
  }

  /**
   * Teste la cr�ation d'un arr�t initial � temps partiel, et v�rifie les points suivants :
   *   - L'impossibilit� de s�lectionner une dur�e indicative pour les arr�ts � temps partiel
   *   - L'impossibilit� de saisir une dur�e sup�rieure � 364 jours
   *   - L' impossibilit� de saisir les autorisations de sorties pour les arr�ts � temps partiel
   */
  public function testArretInitalTempsPartiel() {
    $this->importObject('dPcabinet/tests/Functional/data/aat_regime_gen.xml');
    $this->page->openConsultation($this->patient_name);
    $this->page->openArretTravail();
    $this->page->setType('initial');
    $this->page->setNature('TP');

    /* Renseigne un motif */
    $this->page->setMotif('Grippe saisonni�re');

    $this->page->goToStep('context', 'duration');

    /* V�rifie qu'il est impossible de s�lectionner une dur�e indicative pour les arr�ts � temps partiel */
    $this->assertFalse($this->page->isButtonEnabled('AAT_button_show_duration'));

    /* Renseigne une dur�e d'une valeur sup�rieure � 364 jours,
     * et v�rifie que la dur�e est bien modifi�e pour �tre conforme � la dur�e l�gale (364 jours maximum)
     * V�rifie �galement qu'un message d'avertissement est bien affich�
     */
    $this->page->setDuree(380);
    $this->assertEquals(364, $this->page->getDuree());
    $this->assertTrue($this->page->isMessageDisplayed('AAT_duree_tp_legal'));

    $this->page->goToStep('duration', 'patient_situation');

    $this->page->setPatientActivite('FO');

    $this->page->goToStep('patient_situation', 'sorties');

    /* V�rifie qu'il n'est pas possible de saisir les sorties autoris�es pour les arr�ts � temps partiel */
    $this->assertTrue($this->page->isMessageDisplayed('AAT_msg_sorties_temps_partiel'));
    $this->assertFalse($this->page->isFieldDisplayed('__sorties'));

    /* Validation de l'arr�t de travail */
    $this->page->goToStep('sorties', 'summary');
    $this->page->saveAAT();
    $this->assertContains('Arr�t de travail cr��', $this->page->getSystemMessage());
  }

  /**
   * Teste la cr�ation d'un arr�t de travail � temps partiel concernant l'ALD du patient, et v�rifie les points suivants :
   *   - L'obligation de saisir un compl�ment d'information quand l'arr�t concerne l'ALD du patient
   *   - L'impossibilit� de saisir un motif d'arr�t
   */
  public function testArretInitalTempsPartielALD() {
    $this->importObject('dPcabinet/tests/Functional/data/aat_regime_gen.xml');
    $this->page->openConsultation($this->patient_name);
    $this->page->openArretTravail();
    $this->page->setType('initial');
    $this->page->setNature('TP');

    /* Coche l'ALD � temps partiel et v�rifie que le compl�ment est obligatoire, et qu'il est impossible de saisir un motif */
    $this->page->checkALDTempsPartiel();
    $this->assertTrue($this->page->isFieldNotNull('complement_motif'));
    $this->assertFalse($this->page->isFieldNotNull('motif_id'));
    $this->assertFalse($this->page->isFieldDisplayed('motif_id'));
    $this->page->setComplementMotif('El�ments m�dicaux');

    $this->page->goToStep('context', 'duration');

    $this->page->setDuree(20);

    $this->page->goToStep('duration', 'patient_situation');

    $this->page->setPatientActivite('FO');

    /* Validation de l'arr�t de travail */
    $this->page->goToStep('patient_situation', 'summary');
    $this->page->saveAAT();
    $this->assertContains('Arr�t de travail cr��', $this->page->getSystemMessage());
  }

  /**
   * Teste la cr�ation d'un arr�t � temps complet avec reprise � temps partiel, et v�rifie les points suivants :
   *   - L'impossibilit� de saisir une dur�e d'arr�t � temps complet sup�rieure � 15 jours
   *   - Que la date de d�but de la reprise est �gale � la fin de l'arr�t temps complet + 1 jour
   *   - Qu'il est impossible de saisir une dur�e de reprise sup�rieure � 364 jours
   */
  public function testArretTempsCompletRepriseTempsPartiel() {
    $this->importObject('dPcabinet/tests/Functional/data/aat_regime_gen.xml');
    $this->page->openConsultation($this->patient_name);
    $this->page->openArretTravail();
    $this->page->setType('initial');
    $this->page->setNature('TCP');

    /* Renseigne un motif */
    $this->page->setMotif('Grippe saisonni�re');

    $this->page->goToStep('context', 'duration');

    /* V�rifie que la dur�e � temps complet ne peut �tre sup�rieure � 15 jours */
    $this->page->setDuree(20);
    $this->assertEquals(15, $this->page->getDuree());
    $this->assertTrue($this->page->isMessageDisplayed('AAT_duree_nature_tcp_legal'));

    /* V�rifie que le d�but de la reprise � temps partiel est �gale � la fin du temps complet + 1 jour */
    $this->assertEquals(CMbDT::date('+1 days', $this->page->getDateFinArret()), $this->page->getDebutReprise());

    /* V�rifie que la dur�e maximum de la reprise est de 364 jours pour un arr�t inital */
    $this->page->setDureeReprise(380);
    $this->assertEquals(364, $this->page->getDureeReprise());

    $this->page->goToStep('duration', 'patient_situation');

    $this->page->setPatientActivite('FO');

    /* Validation de l'arr�t de travail */
    $this->page->goToStep('patient_situation', 'summary');
    $this->page->saveAAT();
    $this->assertContains('Arr�t de travail cr��', $this->page->getSystemMessage());
  }

  /**
   * Teste la cr�ation d'un arr�t de prolongation en TCP concernant l'ALD du patient, et v�rifie les points suivants :
   *   - Que la saisi du compl�ment est obligatoire
   *   - Qu'il est impossible de saisir une dur�e de reprise sup�rieure � 182 jours
   */
  public function testArretTempsCompletRepriseTempsPartielALD() {
    $this->importObject('dPcabinet/tests/Functional/data/aat_regime_gen.xml');
    $this->page->openConsultation($this->patient_name);
    $this->page->openArretTravail();
    $this->page->setType('prolongation');
    $this->page->setNature('TCP');
    $this->page->setTypePrescripteur('MT');

    /* Coche l'ALD et v�rifie que le compl�ment est obligatoire, et qu'il est impossible de saisir un motif */
    $this->page->checkALDTempsComplet();
    $this->page->checkALDTempsPartiel();
    $this->assertTrue($this->page->isFieldNotNull('complement_motif'));
    $this->assertFalse($this->page->isFieldNotNull('motif_id'));
    $this->assertFalse($this->page->isFieldDisplayed('motif_id'));
    $this->page->setComplementMotif('El�ments m�dicaux');

    $this->page->goToStep('context', 'duration');

    $this->page->setDuree(10);

    /* V�rifie que la dur�e maximum de la reprise est de 182 jours pour un arr�t de prolongation */
    $this->page->setDureeReprise(200);
    $this->assertEquals(182, $this->page->getDureeReprise());

    $this->page->goToStep('duration', 'patient_situation');

    $this->page->setPatientActivite('FO');

    /* Validation de l'arr�t de travail */
    $this->page->goToStep('patient_situation', 'summary');
    $this->page->saveAAT();
    $this->assertContains('Arr�t de travail cr��', $this->page->getSystemMessage());
  }
}
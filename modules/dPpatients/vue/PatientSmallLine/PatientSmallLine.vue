<!--
  @package Mediboard\Patient
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <div
    v-ripple
    class="PatientSmallLine"
    @click="clickPatient(patient._id)">
    <div class="PatientSmallLine-photo">
      <patient-avatar
        :patient-sexe="patient.sexe"
        :profil-id="''"
      />
    </div>
    <div class="PatientSmallLine-identite">
      <div class="PatientSmallLine-name">
        {{capitalize(patient.civilite)}}
        {{patient.nom}} <span v-if="patient.nom_jeune_fille">({{patient.nom_jeune_fille.toUpperCase()}})</span>
        {{patient.prenom}}
      </div>
      <div class="PatientSmallLine-naissance">
        <ox-date
          :date-string="patient ? patient.naissance : ''"
          mode="date"
        />
      </div>
    </div>
    <div class="PatientSmallLine-adresse">
      <div class="PatientSmallLine-adresseShort">
        {{patient.adresse}}<span v-if="patient.adresse">, </span> {{patient.cp}} {{patient.ville}}
      </div>
    </div>
    <div class="PatientSmallLine-telephone">
      {{patient.tel}}
    </div>
    <div class="PatientSmallLine-cta">
      <ox-vicon
        icon="chevronRight"
        size="24"
      />
    </div>
  </div>
</template>

<script src="./PatientSmallLine.ts" lang="ts"></script>
<style src="./PatientSmallLine.scss" lang="scss"></style>

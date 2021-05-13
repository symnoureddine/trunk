<!--
  @package Mediboard\Patient
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <div class="PatientSmallText">
    <div class="PatientSmallText-identite">
      <patient-avatar
        :patient-sexe="patient.sexe"
        :size="size"
      />
      <div class="PatientSmallText-nom">
        {{capitalize(patient.civilite)}}
        {{patient.nom}}
        <span v-if="patient.nom_jeune_fille">({{patient.nom_jeune_fille}})</span>
        {{patient.prenom}}
      </div>
    </div>
    <div class="PatientSmallText-etat">
      <ox-chip
        v-if="isBMR"
        :small="true"
        state="error"
      >
        BMR+
      </ox-chip>
      <ox-chip
        v-if="isBHReP"
        :small="true"
        state="error"
      >
        BHRe P
      </ox-chip>
      <ox-chip
        v-if="isBHReR"
        :small="true"
        state="error"
      >
        BHRe R
      </ox-chip>
      <ox-chip
        v-if="isBHReC"
        :small="true"
        state="error"
      >
        BHRe C
      </ox-chip>
    </div>
  </div>
</template>

<script src="./PatientSmallText.ts" lang="ts"></script>
<style src="./PatientSmallText.scss" lang="scss"></style>


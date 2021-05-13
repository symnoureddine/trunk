<!--
  @package Mediboard\Patient
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <div class="PatientSmallInfo-patient">
    <div
      v-if="patient && patient._id"
      class="PatientSmallInfo-line"
    >
      <div class="PatientSmallInfo-icon">
        <patient-avatar
          ref="patientavatar"
          :patient-sexe="patient ? patient.sexe : undefined"
          :profil-id="undefined"
        />
      </div>
      <div class="PatientSmallInfo-content">
        <div class="PatientSmallInfo-name">
          {{fullName}}
        </div>
        <div class="PatientSmallInfo-subinfo">
          <div>
            <ox-vicon
              :color="patientSexeColor"
              :icon="patientSexeIcon"
              size="20"
            />
          </div>
          <div>
            <ox-date
              :date-string="patient ? patient.naissance : ''"
              mode="date"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script src="./PatientSmallInfo.ts" lang="ts" />
<style src="./PatientSmallInfo.scss" lang="scss">

</style>

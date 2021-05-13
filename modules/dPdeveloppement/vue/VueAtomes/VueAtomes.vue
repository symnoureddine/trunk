<!--
  @package Mediboard\Developpement
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <div class="VueAtomes">
    <div class="VueAtomes-atome">
      <atome
        component="OxBeautify"
        :props="oxBeautifyProps"
        @change="(newProp) => { updateProp('oxBeautifyProps', newProp)}"
      />
    </div>
    <div class="VueAtomes-atome">
      <atome
        component="OxField"
        :props="oxfieldProps"
        @change="(newProp) => { updateProp('oxfieldProps', newProp)}"
      />
    </div>
    <div class="VueAtomes-atome">
      <atome
        component="OxDate"
        :props="oxdateProps"
        @change="(newProp) => { updateProp('oxdateProps', newProp)}"
      />
    </div>
    <div class="VueAtomes-atome">
      <atome
        component="OxButton"
        :props="oxbuttonProps"
        @change="(newProp) => { updateProp('oxbuttonProps', newProp)}"
      />
    </div>
    <div class="VueAtomes-atome">
      <atome
        component="OxDropDownButton"
        :props="oxdropdownbuttonProps"
        @change="(newProp) => { updateProp('oxdropdownbuttonProps', newProp)}"
      />
    </div>
    <div class="VueAtomes-atome">
      <atome
        ref="oxspecfield_atome"
        component="OxSpecField"
        :props="oxspecfieldProps"
        @change="(newProp) => { updateProp('oxspecfieldProps', newProp)}"
      />
    </div>
    <div class="VueAtomes-atome">
      <atome
        ref="oxspecfield2_atome"
        component="OxSpecField"
        :props="oxspecfield2Props"
        @change="(newProp) => { updateProp('oxspecfield2Props', newProp)}"
      />
    </div>
    <div class="VueAtomes-atome">
      <atome
        ref="oxfieldauto_atome"
        component="OxFieldAuto"
        :props="oxfieldautoSpecs"
        @change="(newProp) => { updateProp('oxfieldautoSpecs', newProp)}"
      />
    </div>
    <div class="VueAtomes-atome">
      <atome
        ref="oxfieldautoobject_atome"
        component="OxFieldAuto"
        :props="oxfieldautoobjectSpecs"
        @change="(newProp) => { updateProp('oxfieldautoobjectSpecs', newProp)}"
      />
    </div>
    <div class="VueAtomes-atome">
      <oxi-date-progressive
          :date="date"
          label="toto"
          icon="mdi-calendar"
          filled
          hint="DD/MM/YYYY format"
          button-icon="mdi-calendar"
          button-rounded
          @selectedDate="selectDate"
      >
        <template v-slot:cancel>
          <v-btn
              text
              color="primary"
              @click="clear()"
          >
            Effacer
          </v-btn>
        </template>
      </oxi-date-progressive>

    </div>
    <ox-button
        :label="'OxNotify 1'"
        @click="addNotification"
    />
    <ox-button
        :label="'OxNotify 2'"
        @click="addWarning"
    />
    <ox-button
        :label="'OxNotify 3'"
        @click="addError"
    />
    <ox-button
        :label="'OxAlert 1'"
        @click="showAlert1"
    />
    <ox-button
        :label="'OxAlert 2'"
        @click="showAlert2"
    />
    <ox-button
        :label="'OxAlert 3'"
        @click="showAlert3"
    />
  </div>
</template>

<script src="./VueAtomes.ts" lang="ts"></script>
<style src="./VueAtomes.scss">

</style>

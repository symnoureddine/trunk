<!--
  @package Mediboard\System
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <div>
    <v-select
      :background-color="fieldBG"
      :clearable="!notNull"
      :color="fieldColor"
      :dark="onPrimary"
      :disabled="disabled"
      filled
      hide-details="auto"
      :hint="hint"
      :items="items"
      item-text="text"
      item-value="id"
      :label="labelComputed"
      :loading="showLoading"
      :no-data-text="tr('OxFieldList-no-data')"
      persistent-hint
      :rules="fieldRules"
      :value="mutatedValueStr"
      @input="changeValue"
    >
    </v-select>
  </div>
</template>

<script src="./OxFieldList.ts" lang="ts"></script>

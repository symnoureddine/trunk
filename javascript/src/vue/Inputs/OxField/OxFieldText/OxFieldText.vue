<!--
  @package Mediboard\System
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <v-textarea
    :append-icon="icon ? iconName : ''"
    :color="fieldColor"
    :background-color="fieldBG"
    :dark="onPrimary"
    :disabled="disabled"
    filled
    hide-details="auto"
    :hint="hint"
    :loading="showLoading"
    :label="labelComputed"
    persistent-hint
    :rows="rows"
    :rules="fieldRules"
    :value="mutatedValue"
    @input="changeValue"
  />
</template>

<script src="./OxFieldText.ts" lang="ts"></script>

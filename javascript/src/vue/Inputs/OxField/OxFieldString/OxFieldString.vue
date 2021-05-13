<!--
  @package Mediboard\System
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <v-text-field
    :append-icon="icon ? iconName : undefined"
    :background-color="fieldBG"
    :color="fieldColor"
    :dark="onPrimary"
    :disabled="disabled"
    filled
    hide-details="auto"
    :hint="hint"
    :label="labelComputed"
    :loading="showLoading"
    :min="number ? 0 : undefined"
    persistent-hint
    :rules="fieldRules"
    :type="number ? 'number' : undefined"
    :value="mutatedValue"
    @click="click"
    @click:append="click"
    @input="changeValue"
  />
</template>

<script src="./OxFieldString.ts" lang="ts"></script>

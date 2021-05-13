<!--
  @package Mediboard\System
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <v-checkbox
    v-model="mutatedValue"
    dense
    :disabled="disabled"
    hide-details="auto"
    :label="label"
    persistent-hint
    :rules="fieldRules"
    @click="click"
  >
  </v-checkbox>
</template>

<script src="./OxFieldBoolean.ts" lang="ts"></script>

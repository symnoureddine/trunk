<!--
  @package Mediboard\System
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <div class="OxForm">
    <v-form
      ref="form"
      :disabled="disabled"
      :lazy-validation="lazyValidation"
      :readonly="readonly"
      :value="value"
    >
      <slot></slot>
    </v-form>
  </div>
</template>

<script src="./OxForm.ts" lang="ts"></script>
<style src="./OxForm.scss" lang="scss">

</style>

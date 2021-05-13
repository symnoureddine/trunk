<!--
  @package Mediboard\System
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->
<template>
  <div class="OxChip">
    <v-chip
      :active="chipActive"
      :close="close"
      :color="backgroundColor"
      :text-color="textColor"
      :small="small"
    >
      <slot></slot>
    </v-chip>
  </div>
</template>

<script src="./OxChip.ts" lang="ts"></script>
<style src="./OxChip.scss" lang="scss"></style>

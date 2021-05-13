<!--
  @package Mediboard\System
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <v-icon
    :class="className"
    :color="color"
    :dark="dark"
    :left="left"
    :right="right"
    :size="size"
  >
    {{iconSvg}}
  </v-icon>
</template>

<script src="./OxVicon.ts" lang="ts"></script>

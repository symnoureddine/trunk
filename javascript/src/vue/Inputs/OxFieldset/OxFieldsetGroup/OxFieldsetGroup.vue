<!--
  @package Mediboard\System
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <div class="OxFieldsetGroup">
    <div class="OxFieldsetGroup-label">
      {{label}}
    </div>
    <div class="OxFieldsetGroup-content">
      <slot></slot>
    </div>
  </div>
</template>

<script src="./OxFieldsetGroup.ts" lang="ts"></script>
<style src="./OxFieldsetGroup.scss">

</style>

<!--
  @package Mediboard\System
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <div class="OxFieldset">
    <div class="OxFieldset-top">
      <div
        v-if="icon"
        class="OxFieldset-icon"
      >
        <ox-icon
          :icon="icon"
          size="14"
        />
      </div>
      <div class="OxFieldset-label">
        {{label}}
      </div>
    </div>
    <div class="OxFieldset-content">
      <slot></slot>
    </div>
    <div class="OxFieldset-actions">
      <slot name="actions"></slot>
    </div>
  </div>
</template>

<script src="./OxFieldset.ts" lang="ts"></script>
<style src="./OxFieldset.scss">

</style>

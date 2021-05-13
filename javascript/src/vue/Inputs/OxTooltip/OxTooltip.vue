<!--
  @package Mediboard\System
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <div class="OxTooltip">
    <v-tooltip
      :bottom="bottom"
      :disabled="disabled"
      :left="left"
      :open-delay="delay"
      :right="right"
      :top="top"
    >
      <template v-slot:activator="{ on, attrs }">
        <slot
          :attrs="attrs"
          name="content"
          :on="on"
        >
        </slot>
      </template>
      <slot></slot>
    </v-tooltip>
  </div>
</template>

<script src="./OxTooltip.ts" lang="ts"></script>

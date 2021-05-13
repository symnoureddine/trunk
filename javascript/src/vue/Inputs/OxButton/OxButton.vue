<!--
  @package Mediboard\System
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <v-btn
    :class="customClass"
    :color="buttonColor"
    :dark="dark"
    :depressed="depressed"
    :disabled="disabled"
    :fab="!label && buttonStyle === 'primary'"
    :height="buttonSize"
    :icon="iconButton || (!label && buttonStyle !== 'primary')"
    :loading="loading"
    :outlined="buttonStyle === 'secondary'"
    rounded
    :small="(!label && buttonStyle === 'primary') || small"
    :text="buttonStyle === 'tertiary'"
    :title="title"
    :width="buttonSize"
    @click="click"
  >
    <ox-vicon
      v-if="icon && iconSide === 'left' && !!label"
      :icon="icon"
      left
      :size="iconSize"
    />
    {{label}}
    <ox-vicon
      v-if="icon && (iconSide === 'right' || !label)"
      :icon="icon"
      :right="!!label && iconSide === 'right'"
      :size="iconSize"
    />
  </v-btn>
</template>

<script src="./OxButton.ts" lang="ts"></script>

<!--
  @package Mediboard\System
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <div class="OxDropDownButton me-dropdown-button">
    <ox-button
      ref="mainButton"
      :class="buttonClasses"
      :label="label"
      :title="title"
      @click.stop="toggleDropDown"
    />
    <div
      ref="ddContent"
      class="me-dropdown-content"
      :style="contentStyle"
    >
      <button
        v-for="(_button, _buttonIndex) in buttons"
        :key="'button-' + _buttonIndex"
        class="secondary"
        :class="[_button.icon]"
        type="button"
        @click="_button.click"
      >
        {{_button.label}}
      </button>
    </div>
  </div>
</template>

<script src="./OxDropDownButton.ts" lang="ts"></script>

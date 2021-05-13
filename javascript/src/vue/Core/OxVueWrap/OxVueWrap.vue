<!--
  @package Mediboard\System
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <v-app>
    <slot></slot>
    <ox-loading />
    <ox-notify :notification-manager="notifyManager"/>
    <ox-alert :alert-manager="alertManager"/>
  </v-app>
</template>

<script src="./OxVueWrap.ts" lang="ts"></script>

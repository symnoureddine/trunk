<!--
  @package Mediboard\Developpement
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <v-list-item>
    <v-list-item-avatar
      color="indigo"
      class="headline font-weight-light white--text">
      {{ item.mainText.charAt(0) }}
    </v-list-item-avatar>
    <v-list-item-content>
      <v-list-item-title v-text="item.mainText"></v-list-item-title>
      <v-list-item-subtitle v-text="item.subText"></v-list-item-subtitle>
    </v-list-item-content>
    <v-list-item-action>
      <v-icon>mdi-coin</v-icon>
    </v-list-item-action>
  </v-list-item>
</template>

<script src="./AtomeAutoObject.ts" lang="ts"></script>
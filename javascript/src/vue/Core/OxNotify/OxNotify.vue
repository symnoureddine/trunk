<!--
  @package Mediboard\System
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <div class="OxNotify">
    <div
      v-for="notification in notifications"
      :key="'notify-' + notification.key"
    >
      <transition
        enter-active-class="enter"
        leave-active-class="leave"
      >
        <div
          v-show="notification.show"
          class="OxNotify-line"
        >
          <v-alert
            border="left"
            :color="notifyColor(notification)"
            dark
          >
            <v-row align="center">
              <v-col
                class="grow"
                v-html="notification.libelle"
              >
              </v-col>
              <v-col class="shrink">
                <ox-button
                  button-style="tertiary-dark"
                  :label="tr('Ok')"
                  @click="removeNotification(notification.key)"
                />
              </v-col>
            </v-row>
          </v-alert>
        </div>
      </transition>
    </div>

  </div>
</template>

<script src="./OxNotify.ts" lang="ts"></script>
<style src="./OxNotify.scss" lang="scss">

</style>

<!--
  @package Mediboard\System
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <div class="OxFieldAuto">
    <v-autocomplete
      v-model="mutatedValue"
      :append-icon="iconSearch"
      :background-color="fieldBG"
      :cache-items="!options.length"
      :chips="chips"
      :clearable="!disabled"
      :color="fieldColor"
      :dark="onPrimary"
      :disabled="disabled"
      filled
      hide-details="auto"
      :hint="hint"
      :items="options.length ? options : items"
      :item-text="itemText"
      :item-value="itemId"
      :label="labelComputed"
      :loading="loading"
      :multiple="multiple"
      persistent-hint
      :rules="fieldRules"
      :small-chips="chips"
      :search-input.sync="search"
      @change="changeAuto"
    >
      <template v-slot:no-data>
        <v-list-item>
          <v-list-item-title
            v-if="loading"
            :key="'loadingMessage-' + itemId"
            class="OxFieldAuto-emptyMessage"
          >
            {{tr('OxFieldAutoCore-loading')}}
          </v-list-item-title>
          <v-list-item-title
            v-else-if="noDataResponse"
            :key="'noDataMessage-' + itemId"
            class="OxFieldAuto-emptyMessage"
          >
            {{tr('OxFieldAutoCore-noData')}}
          </v-list-item-title>
          <v-list-item-title
            v-else
            :key="'CTAMessage-' + itemId"
            class="OxFieldAuto-emptyMessage"
          >
            {{tr('OxFieldAutoCore-search')}}
            <strong v-if="label">"{{label}}"</strong>
          </v-list-item-title>
        </v-list-item>
      </template>
      <template
        v-if="object"
        v-slot:selection="{ attr, on, item, selected }"
      >
        {{ item[itemView] }}
      </template>
      <template
        v-if="object"
        v-slot:item="{ item }"
      >
        <slot
          name="item"
          :item="item"
        >
        </slot>
      </template>
    </v-autocomplete>
  </div>
</template>

<script src="./OxFieldAuto.ts" lang="ts"></script>
<style src="./OxFieldAuto.scss" lang="scss"></style>

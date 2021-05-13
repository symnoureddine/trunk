<!--
  @package Mediboard\Developpement
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <div class="Atome">
    <div class="Atome-title">
      {{component}}
    </div>
    <div class="Atome-content">
      <div class="Atome-component">
        <div class="Atome-componentInner">
          <ox-beautify
            v-if="component === 'OxBeautify'"
            :ref="'component_' + component"
            :value="props.value.value"
            :void-label="props.voidLabel.value"
          />
          <ox-date
            :ref="'component_' + component"
            v-if="component === 'OxDate'"
            :date="new Date(props.date.value)"
            :mode="props.mode.value"
            @select="(value) => catchEvent('select', value)"
          />
          <ox-button
            v-else-if="component === 'OxButton'"
            :ref="'component_' + component"
            :button-style="props.buttonStyle.value"
            :custom-class="props.customClass.value"
            :icon="props.icon.value"
            :icon-side="props.iconSide.value"
            :label="props.label.value"
            :title="props.title.value"
            @click="catchEvent('click')"
          />
          <ox-spec-field
            v-else-if="component === 'OxSpecField'"
            :ref="'component_' + component"
            :custom-specs="props.customSpecs ? props.customSpecs.transformer(props.customSpecs.value) : null"
            :field="props.field ? props.field.value : null"
            :label="props.label ? props.label.value : null"
            :resource="props.resource ? props.resource.value : null"
            :title="props.title ? props.title.value : null"
            @change="(value) => catchEvent('change', value)"
          />
          <ox-field-auto
            v-else-if="component === 'OxFieldAuto'"
            :ref="'component_' + component"
            :icon="props.icon.value"
            :item-id="props.itemId ? props.itemId.value : undefined"
            :item-text="props.itemText ? props.itemText.value : undefined"
            :label="props.label.value"
            :message="props.message.value"
            :object="props.object.value"
            :provider="props.provider.value"
            :state="props.state.value"
            @change="(value) => catchEvent('change', value)"
          >
            <template
              v-if="props.object.value"
              v-slot:item="item">
              <atome-auto-object :item="item.item"/>
            </template>
          </ox-field-auto>
          <oxi-date-progressive
            v-else-if="component === 'OxiDateProgressive'"
            :ref="'component_' + component"
          />
        </div>
        <div class="Atome-componentEvent">
          <div
            v-if="event.name"
            class="AtomeEvent"
          >
            <div
              v-if="event.name"
              class="AtomeEvent-name"
            >
              @{{event.name}}
            </div>
            <div
              v-if="event.value || event.value === false"
              class="AtomeEvent-value"
            >
              {{event.value}}
            </div>
          </div>
        </div>
      </div>
      <div class="Atome-props">
        <div
          v-for="(_prop, _propIndex) in propsList"
          :key="'prop-' + _propIndex">
          <ox-field-string
            v-if="_prop.params.type === 'string'"
            :label="_prop.label"
            :value="_prop.params.value"
            @change="(value) => propsOnChange(_prop.label, value)"
          />
          <ox-field-list
            v-if="_prop.params.type === 'list'"
            :label="_prop.label"
            :list="_prop.params.list"
            option-id="value"
            option-view="name"
            :type="_prop.params.type"
            :value="_prop.params.value"
            @change="(value) => propsOnChange(_prop.label, value)"
          />
        </div>
        <ox-button
          icon="refresh"
          @click="applyProps"
        />
      </div>
    </div>
  </div>
</template>

<script src="./Atome.ts" lang="ts"></script>
<style src="./Atome.scss">

</style>

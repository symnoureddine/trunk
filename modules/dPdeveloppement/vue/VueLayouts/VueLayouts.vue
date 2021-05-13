<!--
  @package Mediboard\Developpement
  @author  SAS OpenXtrem <dev@openxtrem.com>
  @license https://www.gnu.org/licenses/gpl.html GNU General Public License
  @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
-->

<template>
  <div class="VueLayouts">
    <div class="VueLayouts-selecter">
<!--      <OxField-->
<!--        label="Layout"-->
<!--        :list="layoutsList"-->
<!--        :value="currentLayout"-->
<!--        type="list"-->
<!--        @change="setCurrentLayout"-->
<!--        />-->
    </div>
    <div class="VueLayouts-layout">

      <ox-layout-main v-if="currentLayout === 'OxLayoutMain'">
        <div>
          Main layout
        </div>
      </ox-layout-main>

      <ox-layout-aside-right v-else-if="currentLayout === 'OxLayoutAsideRight'">
        <div>
          Main content
        </div>
        <template v-slot:aside>
          <div>
            Aside content
          </div>
        </template>
      </ox-layout-aside-right>

      <div
        v-else-if="currentLayout === 'OxCard'"
        class="SampleCard">
        <ox-card>
          <template v-slot:header>
            <ox-card-header
              icon="procedures"
              :label="tr('CSejour')"
            />
          </template>
          <div class="SampleCard-body">
            <ox-field label="Field 1"/>
            <ox-field label="Field 2"/>
            <ox-field label="Field 3"/>
          </div>
          <template v-slot:action>
            <ox-card-action label="Détail"/>
          </template>
        </ox-card>
      </div>
    </div>
  </div>
</template>

<script src="./VueLayouts.ts" lang="ts"></script>
<style src="./VueLayouts.scss">

</style>
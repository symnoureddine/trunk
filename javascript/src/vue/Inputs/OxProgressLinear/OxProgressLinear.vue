<template>
  <div class="OxProgressLinear">
    <v-progress-linear
      background-color="transparent"
      :buffer-value="bufferValue"
      :color="color"
      :height="height"
      :rounded="rounded"
      :value="value"
    />
  </div>
</template>

<script src="./OxProgressLinear.ts" lang="ts"></script>
<style src="./OxProgressLinear.scss"></style>

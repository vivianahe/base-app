<template>
  <div v-if="loading" class="loader_component">
    <v-progress-circular
      :size="100"
      :width="7"
      color="blue-grey-lighten-4"
      indeterminate
    ></v-progress-circular>
  </div>
</template>

<script setup>
const props = defineProps({
  loading: Boolean,
});
</script>

  <style scoped>
.loader_component {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  margin: auto !important;
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 9999;
}
</style>

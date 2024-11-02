<template>
  <div class="container d-flex justify-content-center align-items-center">
    <img :src="imageUrl" class="image mr-4" v-if="imageUrl" style="width: 40%;" />
    <v-file-input accept="image/*" placeholder="Pick an avatar" prepend-icon="mdi-camera" label="Logo *"
      @change="handleFileChange" @click:clear="clearData"></v-file-input>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
  value: { type: File, default: null },
});

const emit = defineEmits([
  "upload-image",
]);

const imageUrl = ref(null);


const clearData = (e) => {
  handleFileChange(e);
}
const handleFileChange = (event) => {
  if (event.target.files && event.target.files.length > 0) {
    const file = event.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = () => {
        imageUrl.value = reader.result;
        // Emitimos un evento cuando se carga una nueva imagen
        emit('upload-image', file);
      };
      // Convertir el objeto File a Blob
      const blob = new Blob([file]);
      reader.readAsDataURL(blob);
    }
  } else {
    imageUrl.value = null;
    emit('upload-image', false);
  }
};
</script>

<style scoped>
.image {
  max-width: 80%;
  /* Ajusta el ancho máximo según tus necesidades */
  max-height: 80%;
  /* Ajusta la altura máxima según tus necesidades */
}
</style>

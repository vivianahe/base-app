<template>
  <div>
    <v-card :elevation="elevation">
      <v-card-title class="title-datatable-section" v-if="!hide_header">
        <div class="title-datatable">
          <span class="title-vuely">
            {{ title }}
          </span>

          <div class="d-flex flex-wrap div_botones">
            <v-text-field v-if="showSearch" v-model="search" prepend-inner-icon="mdi-magnify" density="compact"
              label="Buscar" single-line flat hide-details variant="solo-filled" class="showSearch"></v-text-field>
            <v-spacer></v-spacer>
            <v-btn prepend-icon="mdi mdi-plus-circle" color="#0f172a" v-if="button_add" @click="$emit('click-add')" style="margin-left: 1em;">
              Agregar
            </v-btn>
            <div v-if="addBtnRole">
              <v-btn prepend-icon="mdi mdi-plus-circle" class="btn btn-sm btn-dark btn-icon d-flex align-items-center me-2"
                @click="$emit('showCreateDtRole', true)">
                Nuevo rol
              </v-btn>
            </div>
          </div>
        </div>
        <v-divider></v-divider>
      </v-card-title>

      <slot name="autocomplet-header">
      </slot>

      <v-data-table :search="search" loading-text="Cargando ..." :no-data-text="'Listado vacío'" :headers="headers"
        :items-per-page="10" :items="items" @current-items="getFiltered" :show-select="show_select"
        v-model="itemsSelect" @update:modelValue="changeItems" return-object>
        <template v-for="(_, slot) of $slots" v-slot:[slot]="scope">
          <slot :name="slot" v-bind="scope" />
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>

<script setup>
import { ref } from "vue";

const props = defineProps({
  title: { type: String, default: () => null },
  button_add: { type: Boolean, default: () => false },
  show_select: { type: Boolean, default: () => false },
  headers: { type: Array, default: () => [] },
  items: { type: Array, default: () => [] },
  addBtnRole: { type: Boolean, default: () => false },
  hide_header: { type: Boolean, default: () => false },
  elevation: { type: Number, default: () => 5 },
  showSearch: { type: Boolean, default: () => false },
});

const emit = defineEmits([
  "click-add",
  "showCreateDtRole",
  "changeItems"
]);

const headerprops = ref({
  "sort-icon": "mdi-menu-down",
});

let search = ref("");
let itemsSelect = ref([]);
let filteredItems = ref([]);

const changeItems = (e) => {
  emit('changeItems', e)
};

const clearItemsCheck = () => {
  itemsSelect.value = [];
};
const getFiltered = (e) => {
  filteredItems.value = null;
  filteredItems.value = e;
};

defineExpose({
  clearItemsCheck
});
</script>

<style scoped>
.showSearch {
  width: 300px;
}

.v-data-table :deep(tr:hover) {
  background: #eeeeee;
  cursor: pointer !important;
}

.v-data-table :deep(th) {
  border: none !important;
  vertical-align: middle !important;
  color: #7C7E87 !important;
  font-size: 0.85rem;
  font-weight: 900 !important;
}

.v-data-table tr {
  border-bottom: 1px dashed #f3f3f3 !important;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
    "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji",
    "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji" !important;
}

.v-data-table td {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
    "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji",
    "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji" !important;
  font-size: 0.875rem;
  border-bottom: none !important;
}

.v-data-table {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
    "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji",
    "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji" !important;
  font-size: 0.875rem;
}

.title-datatable-section {
  display: flex;
  flex-direction: column;
  align-items: inherit;
  padding: 0 !important;
}

.title-datatable {
  border-bottom: 1px solid rgba(253, 253, 253, 0.2) !important;
  padding: 1rem !important;
  margin-bottom: -10px;
  display: flex;
  justify-content: space-between;
}

.text-search {
  height: calc(1.5em + 0.75rem + 12px) !important;
  border-radius: 10px !important;
  border: 1px solid #f5f8fa !important;
  background-color: #f5f8fa !important;
  min-width: 230px !important;
}

.theme--light.v-text-field>.v-input__control>.v-input__slot:before {
  border: 0 !important;
}

.text-search .v-label {
  left: 15px !important;
  top: 2px !important;
}

.title-vuely {
  font-size: 23px !important;
  font-weight: 600 !important;
  color: #0f172a !important;
}

.theme--light.v-data-table .v-data-table-header th.sortable.active .v-data-table-header__icon {
  color: #009ef7 !important;
}

th.sortable .v-icon:hover {
  color: #009ef7 !important;
}

.btn-log {
  background-color: #fff !important;
  color: #444 !important;
  border: 1px solid #f5f8fa !important;
  margin-left: 1em;
}

@media (max-width: 1000px) {
  .title-datatable {
    flex-direction: column;
  }

  .title-vuely {
    margin-bottom: 10px;
  }

}

@media (max-width: 830px) {
  .div_botones {
    display: flex;
    flex-direction: column;
    gap: 10px;
    justify-content: start;
    align-items: start;
  }

  .btn-log {
    margin-left: 0;
  }
}

@media (max-width: 768px) {
  .div_botones {
    display: flex;
    flex-direction: row;
    gap: 0;
  }

  .btn-log {
    margin-left: 1em;
  }

}

@media (max-width: 548px) {
  .div_botones {
    display: flex;
    flex-direction: column;
    gap: 10px;
    justify-content: start;
    align-items: start;
  }

  .btn-log {
    margin-left: 0;
  }
}
</style>

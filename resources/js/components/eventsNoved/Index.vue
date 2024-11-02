<template>
    <div id="main-wrapper" class="mini-sidebar pb-16">
        <Loader :loading="loading" />
        <DataTable class="tabla-m" :title="'Eventos'" :showSearch="true" :headers="headers" :items="dataEvent"
            :button_add="true" @click-add="dialogVisible = true, titleModal = 'AGREGAR EVENTO'">
            <template v-slot:[`item.options`]="{ item }">
                <v-container>
                    <v-row align="center" justify="center">
                        <v-col cols="auto">
                            <v-tooltip text="Tooltip">
                                <template v-slot:activator="{ props }">
                                    <v-btn icon size="small" v-bind="props"
                                        @click="titleModal = 'EDITAR EVENTO', dialogVisible = true, getEventId(item.id)">
                                        <v-icon color="#a1a5b7">mdi mdi-file-document-edit</v-icon></v-btn>
                                </template>
                                <span>Editar</span>
                            </v-tooltip>
                        </v-col>
                        <v-col cols="auto">
                            <v-tooltip text="Tooltip">
                                <template v-slot:activator="{ props }">
                                    <v-btn icon size="small" v-bind="props" @click="confirmDeletion(item.id)">
                                        <v-icon color="#a1a5b7">mdi mdi-trash-can</v-icon>
                                    </v-btn>
                                </template>
                                <span>Eliminar</span>
                            </v-tooltip>
                        </v-col>
                    </v-row>
                </v-container>
            </template>
        </Datatable>
    </div>
    <!--Modal-->
    <v-dialog width="1000" v-model="dialogVisible" persistent>
        <v-card :title=titleModal>
            <v-form @submit.prevent="setData">
                <v-container>
                    <v-row>
                        <v-col cols="12">
                            <v-text-field :error-messages="errorMessages.name" v-model="eventNoved.name" label="Nombre *"
                                required></v-text-field>
                        </v-col>

                    </v-row>
                    <v-row>
                        <v-col cols="6">
                            <v-text-field :error-messages="errorMessages.capacity" v-model="eventNoved.capacity"
                                label="Aforo *" @keypress="onlyNumbers" type="number" required></v-text-field>
                        </v-col>
                        <v-col cols="6">
                            <v-text-field :error-messages="errorMessages.date" v-model="eventNoved.date" label="Fecha *"
                                type="date" required></v-text-field>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="6">
                            <v-text-field :error-messages="errorMessages.hour" v-model="eventNoved.hour" label="Hora *"
                                type="time" required></v-text-field>
                        </v-col>
                        <v-col cols="6">
                            <v-text-field v-model="eventNoved.price"
                                @keypress="onlyNumberDecimal" label="Precio" required></v-text-field>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="12">
                            <div v-if="selectedImage" class="relative flex items-center">
                                <label for="" class="text-subtitle-2 mr-2">Logo: *</label>
                                <div class="relative flex items-center">
                                    <img :src="'/support/logoEvent/' + selectedImage" alt="Imagen seleccionada"
                                        class="w-40 h-auto rounded border bg-white p-1 object-cover" />
                                    <v-btn class="absolute top-0 right-0 m-2" color="danger" title="Cambiar logo"
                                        @click="selectedImage = null, eventNoved.logo = null"
                                        style="color: white;">X</v-btn>
                                </div>
                            </div>
                            <upload-image-component v-else :value="eventNoved.logo"
                                @upload-image="handleUploadImage"></upload-image-component>
                            <p v-if="errorMessages.logo" class="ml-16 text-caption text-danger">{{ errorMessages.logo }}</p>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="12">
                            <v-btn color="#6e0958" type="submit" class="mr-2">GUARDAR</v-btn>
                            <v-btn color="light" @click="clearFrm()">CANCELAR</v-btn>
                        </v-col>
                    </v-row>
                </v-container>
            </v-form>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import DataTable from '../utilities/Datatable.vue'
import Swal from 'sweetalert2';
import uploadImageComponent from '../utilities/uploadImage.vue';
import Loader from "../utilities/Loader.vue";
import { useRouter } from "vue-router";
const router = useRouter()
const selectedImage = ref(null);
const dataEvent = ref([]);
const titleModal = ref('AGREGAR EVENTO');
const errorMessages = ref({
    name: null,
    capacity: null,
    date: null,
    hour: null,
    logo: null
});
const loading = ref(true);
const headers = ref([
    { title: "IdEvento", align: "start", sortable: true, key: "id" },
    { title: "Nombre", align: "start", sortable: false, key: "name" },
    { title: "Aforo", align: "center", sortable: false, key: "capacity" },
    { title: "Fecha", align: "center", sortable: false, key: "date" },
    { title: "Hora", align: "center", sortable: false, key: "hour" },
    { title: "Precio", align: "center", sortable: false, key: "price" },
    { title: "Acción", align: "center", sortable: false, key: "options" },
]);

const eventNoved = ref({
    id: null,
    name: "",
    capacity: "",
    date: "",
    hour: "",
    price: "",
    logo: ""
});

const getEventId = (item) => {
    loading.value = true;
    axios
        .get("/getEventId/" + item)
        .then((response) => {
            eventNoved.value.id = response.data.id;
            eventNoved.value.name = response.data.name;
            eventNoved.value.hour = response.data.hour;
            eventNoved.value.capacity = response.data.capacity;
            eventNoved.value.date = response.data.date;
            selectedImage.value = response.data.logo;
            eventNoved.value.logo = response.data.logo;
            eventNoved.value.price = response.data.price;
            loading.value = false;
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        });

};

const handleUploadImage = (imageUrl) => {
    if (imageUrl) {
        errorMessages.value.logo = "";
        eventNoved.value.logo = imageUrl;
    } else {
        eventNoved.value.logo = "";
    }
};

const confirmDeletion = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "¡No podrás revertir esto!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "¡Sí, bórralo!",
    }).then((result) => {
        if (result.isConfirmed) {
            deleteEvent(id);
        }
    });
};
const deleteEvent = async (id) => {
    loading.value = true;
    axios
        .get("/deleteEvent/" + id)
        .then((response) => {
            if (response.data.message === 'Exists') {
                Swal.fire({
                    title: "¡Atención!",
                    text: "No se puede eliminar el evento porque ya tiene participantes relacionados.",
                    icon: "warning",
                });
                loading.value = false;
            } else {
                Swal.fire({
                    title: "¡Borrado!",
                    text: "El evento ha sido eliminado",
                    icon: "success",
                });
                loading.value = true;
                getEventData();
            }
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        });
};
const dialogVisible = ref(false);
const validateFields = () => {
    const event = eventNoved.value;
    if (event.name.trim() === "") {
        errorMessages.value.name = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.value.name = "";
    }
    if (typeof event.capacity === 'string' && (event.capacity.trim() === "" || event.capacity.trim() === '""')) {
        errorMessages.value.capacity = "Este campo es obligatorio.";
        return false;
    }
    else {
        errorMessages.value.capacity = "";
    }
    if (event.date.trim() === "") {
        errorMessages.value.date = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.value.date = "";
    }
    if (event.hour.trim() === "") {
        errorMessages.value.hour = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.value.hour = "";
    }
    if (!event.logo || event.logo.length === 0) {

        errorMessages.value.logo = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.value.logo = "";
    }
    return true;
};

const getEventData = async () => {
    await axios
        .get("/getEventData")
        .then((response) => {
            dataEvent.value = response.data;
            loading.value = false;
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        })
};

const onlyNumberDecimal = ($event) => {
    let keyCode = ($event.keyCode ? $event.keyCode : $event.which);
    // Permitir números del 0 al 9, punto (.) y coma (,)
    if ((keyCode < 48 || keyCode > 57) && keyCode !== 46 && keyCode !== 44) {
        $event.preventDefault();
    }
};
const onlyNumbers = (event) => {
    let keyCode = (event.keyCode ? event.keyCode : event.which);
    // Permitir solo números del 0 al 9
    if (keyCode < 48 || keyCode > 57) {
        event.preventDefault();
    }
};

const setEventNoved = () => {
    if (!validateFields()) {
        return;
    }
    const formData = new FormData();
    formData.append("name", eventNoved.value.name);
    formData.append("capacity", eventNoved.value.capacity);
    formData.append("date", eventNoved.value.date);
    formData.append("hour", eventNoved.value.hour);
    formData.append("price", eventNoved.value.price);
    formData.append("logo", eventNoved.value.logo);
    const config = {
        headers: {
            "content-type": "multipart/form-data"
        },
    };
    loading.value = true;
    axios
        .post('/addEventNoved', formData, config)
        .then((response) => {
            if (response.data.message) {
                Swal.fire("Correcto!", response.data.message, "success");
                clearFrm();
                getEventData();
            } else {
                Swal.fire(
                    "Atención!",
                    response.data.error + eventNoved.value.name,
                    "warning"
                );
                loading.value = false;
            }
        })
        .catch((error) => {
            loading.value = false;
            console.error(error);
        });

};

const setData = () => {
    if (titleModal.value === 'AGREGAR EVENTO') {
        setEventNoved();
    } else {
        updateEventNoved();
    }
};

const updateEventNoved = () => {
    if (!validateFields()) {
        return;
    }
    const formData = new FormData();
    formData.append("id", eventNoved.value.id);
    formData.append("name", eventNoved.value.name);
    formData.append("capacity", eventNoved.value.capacity);
    formData.append("date", eventNoved.value.date);
    formData.append("hour", eventNoved.value.hour);
    formData.append("price", eventNoved.value.price);
    formData.append("logo", eventNoved.value.logo);
    const config = {
        headers: {
            "content-type": "multipart/form-data"
        },
    };
    loading.value = true;
    axios
        .post('/updateEventNoved', formData, config)
        .then((response) => {
            if (response.data.message) {
                Swal.fire("Correcto!", response.data.message, "success");
                clearFrm();
                getEventData();
            } else {
                Swal.fire(
                    "Atención!",
                    response.data.error + eventNoved.value.name,
                    "warning"
                );
                loading.value = false;
            }
        })
        .catch((error) => {
            loading.value = false;
            console.error(error);
        });

};
const clearFrm = () => {
    dialogVisible.value = false;
    eventNoved.value.id = "";
    eventNoved.value.name = "";
    eventNoved.value.capacity = "";
    eventNoved.value.date = "";
    eventNoved.value.hour = "";
    eventNoved.value.price = "";
    eventNoved.value.logo = "";
    selectedImage.value = null;
    loading.value = false;
    errorMessages.value.logo = "";
};

const getRedirection = async () => {
  const response = await axios.get(`/getInitialRedirectPath`);
  if (response.data != 'permission'){
    router.push({ path: response.data});
  }
}
onMounted(async () => {
    await getRedirection();
    await getEventData();
});

</script>
<style>
.card {
    border: none;
    /* Esto elimina el borde de la tarjeta */
}

.card .card-body {
    background: #FFF;
}

.card-header {
    border: none;
}
</style>

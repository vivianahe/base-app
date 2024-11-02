<template>
    <div class="container-fluid">
        <Loader :loading="loading" />
        <div class="row">
            <div class="col">
                <v-card class="mx-auto">
                    <v-card-title class="title_card">
                        <router-link :to="{ path: '/assistance' }" tag="div" class="ml-4" v-if="event_id !== ''">
                            <v-btn icon size="small" title="Eliminar" class="mr-2">
                                <v-icon>mdi mdi-keyboard-backspace</v-icon>
                            </v-btn>
                        </router-link>
                        <span> Lector QR <span class="mdi mdi-qrcode-scan"></span> - {{ eventName }}</span>
                    </v-card-title>
                    <v-card-text class="mb-5">
                        <div class="row justify-center">
                            <h5 class="mt-3 d-flex justify-center">Escanea el código QR del evento</h5>
                            <p class="error">{{ error }}</p>
                            <v-col cols="12" xs="12" sm="12" md="8" lg="4" xl="4" xxl="4" class="py-1">
                                <qrcode-stream @detect="onDetect" @camera-on="onCameraReady"></qrcode-stream>
                            </v-col>
                        </div>
                    </v-card-text>
                </v-card>
                <div id="main-wrapper" class="mini-sidebar pb-16 mt-2" v-if="dataEvent.length > 0">
                    <DataTable class="tabla-m" :title="'Eventos del día'" :headers="headers" :items="dataEvent"
                        :elevation="1" :hide_header="false" :button_add="false" :showHeader="true">
                    </Datatable>
                </div>
            </div>
        </div>
    </div>
    <!--Modal add and edit-->
    <v-dialog width="900" v-model="dialogVisible" persistent>
        <v-card title="Datos participante">
            <v-container>
                <v-row>
                    <v-col cols="6">
                        <v-text-field v-model="participant.dni" label="DNI" type="text"></v-text-field>

                    </v-col>
                    <v-col cols="6">
                        <v-text-field v-model="participant.name" label="Nombres" type="text"></v-text-field>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col cols="6">
                        <v-text-field v-model="participant.lastname" label="Apellidos" type="text"></v-text-field>
                    </v-col>
                    <v-col cols="6">
                        <v-text-field v-model="participant.email" label="Email" type="email"></v-text-field>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col cols="6">
                        <v-text-field v-model="participant.phone" label="Teléfono" type="text"></v-text-field>
                    </v-col>
                    <v-col cols="6">
                        <v-text-field v-model="participant.eventP" label="Evento" type="text"></v-text-field>

                    </v-col>
                </v-row>
                <v-row>
                    <v-col cols="6">
                        <v-text-field v-model="participant.state" label="Estado" type="text"></v-text-field>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col cols="12 d-flex justify-end">
                        <v-btn color="light" @click="clearFrm()">CANCELAR</v-btn>
                    </v-col>
                </v-row>
            </v-container>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import Loader from "../utilities/Loader.vue";
import Swal from 'sweetalert2';
import { useRoute } from 'vue-router';
import DataTable from '../utilities/Datatable.vue'
import { QrcodeStream } from 'vue-qrcode-reader';
const route = useRoute();
const event_id = ref(route.params.id);

const result = ref('')
const loading = ref(false);
const headers = ref([
    { title: "idEvento", align: "start", sortable: true, key: "id" },
    { title: "Nombre", align: "start", sortable: false, key: "name" },
    { title: "Aforo", align: "center", sortable: false, key: "capacity" },
    { title: "Fecha", align: "center", sortable: false, key: "date" },
    { title: "Hora", align: "center", sortable: false, key: "hour" },
    { title: "Precio", align: "center", sortable: false, key: "price" },
    { title: "Asistentes", align: "center", sortable: false, key: "participant_count" },
]);
const dataEvent = ref([]);
async function onDetect(detectedCodes) {
    loading.value = true;
    result.value = JSON.stringify(detectedCodes.map((code) => code.rawValue));
    if (result.value !== "[]") {
        await getParticipantQR(result.value);
        result.value = [];
    }
    loading.value = false;
}
const eventName = ref('')
const error = ref('');

async function onCameraReady() {
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
    } catch (err) {
        error.value = 'Error al acceder a la cámara: ' + err.message;
    }
}

onMounted(async () => {
    await getEvents();
    await getEventId(event_id.value);
    await onCameraReady();
})

const getEvents = async () => {
    try {
        const response = await axios.get("/getEventDate");
        dataEvent.value = response.data;
        loading.value = false;
    } catch (error) {
        console.error(error);
        loading.value = false;
    }
};

const dialogVisible = ref(false);
const participant = reactive({
    id: "",
    name: "",
    lastname: "",
    email: "",
    dni: "",
    phone: "",
    eventP: "",
    state: "",
});
const clearFrm = () => {
    dialogVisible.value = false;
    participant.id = "";
    participant.name = "";
    participant.lastname = "";
    participant.email = "";
    participant.dni = "";
    participant.eventP = "";
    participant.phone = "";
    participant.state = "";
    loading.value = false;
    result.value = [];
};
const getEventId = (item) => {
    loading.value = true;
    axios
        .get("/getEventId/" + item)
        .then((response) => {
            eventName.value = response.data.name;
            loading.value = false;
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        });

};

const getParticipantQR = async (data) => {
    loading.value = true;
    await axios
        .get("/getParticipantQR/" + data + '/' + event_id.value)
        .then((response) => {
            if (response.data.message === 'ok') {
                dialogVisible.value = true;
                participant.id = response.data.participants.id;
                participant.dni = response.data.participants.dni;
                participant.name = response.data.participants.name;
                participant.lastname = response.data.participants.lastname;
                participant.email = response.data.participants.email;
                participant.phone = response.data.participants.phone;
                participant.eventP = response.data.event.name;
                participant.cp = response.data.participants.cp;
                if (response.data.state !== null) {
                    participant.state = response.data.state.state;
                }
                getEvents();
                loading.value = false;
            } else {
                Swal.fire(
                    "Atención!",
                    response.data.message,
                    "warning"
                );
                result.value = [];
                loading.value = false;
            }
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        });
};
</script>
<style scoped>
.title_card {
    background: #6e0958;
    color: #ffff !important;
    padding: 1.10rem !important;
}
</style>

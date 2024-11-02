<template>
    <div class="row">
        <Loader :loading="loading" />
        <DataTable class="tabla-m" :title="'Asistencia a Eventos'" :headers="headers" :items="dataParticipant"
            :button_add="false" :elevation="25" :showSearch="true">
            <template v-slot:autocomplet-header>
                <v-row class="px-5">
                    <v-col cols="12" xs="12" sm="12" md="8" lg="7" xl="7" xxl="7" class="px-1">
                        <div class="d-flex align-center">
                            <v-autocomplete label="Selecciona un evento" v-model="event_id" :items="dataEvent"
                                item-title="text" item-value="value" :loading="loading" loading-text="Cargando eventos..."
                                @update:modelValue="getParticipantData(event_id)" hide-details></v-autocomplete>
                            <router-link :to="{ path: '/qrRead/' + event_id }" tag="div" class="ml-4"
                                v-if="event_id !== ''">
                                <v-btn color="secondary" prepend-icon="mdi mdi-qrcode-scan" class="mr-2">Escanear</v-btn>
                            </router-link>
                        </div>
                    </v-col>
                    <v-spacer></v-spacer>
                    <v-col cols="12" xs="12" sm="12" md="2" lg="2" xl="7" xxl="7" v-if="numberAssist !== ''"
                        class="py-1 d-flex align-center">
                        <v-chip size="large" density="comfortable" prepend-icon="mdi-account-group" variant="flat"
                            color="blue-grey">
                            <b>Asistentes: {{ numberAssist }}</b>
                        </v-chip>
                    </v-col>
                </v-row>
            </template>
            <template v-slot:[`item.options`]="{ item }">
                <v-container>
                    <v-row align="center" justify="center">
                        <v-col cols="auto" v-if="item.qr && $can('email_management')">
                            <v-tooltip text="Tooltip">
                                <template v-slot:activator="{ props }">
                                    <v-btn icon size="small" v-bind="props" title="Generar QR"
                                        @click="qrParticipantEvent(item.qr)">
                                        <v-icon color="#616161">mdi mdi-qrcode</v-icon>
                                    </v-btn>
                                </template>
                                <span>Generar QR</span>
                            </v-tooltip>
                        </v-col>
                        <v-col cols="auto" v-if="item.state !== 'Asistente'">
                            <v-tooltip text="Tooltip">
                                <template v-slot:activator="{ props }">
                                    <v-btn icon size="small" v-bind="props" @click="confirmState(item.id, item.event_id)"
                                        title="Cambiar estado">
                                        <v-icon color="#616161">mdi mdi-update</v-icon>
                                    </v-btn>
                                </template>
                                <span>Cambiar estado</span>
                            </v-tooltip>
                        </v-col>
                    </v-row>
                </v-container>
            </template>
        </Datatable>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import DataTable from '../utilities/Datatable.vue'
import Swal from 'sweetalert2';
import Loader from "../utilities/Loader.vue";
const loading = ref(true);
const dataParticipant = ref([]);
const event_id = ref("");
const numberAssist = ref("");
const dataEvent = ref([]);
const dataDni = ref([]);

const headers = ref([
    { title: "Nombre", align: "start", sortable: false, key: "name" },
    { title: "Apellidos", align: "center", sortable: false, key: "lastname" },
    { title: "Email", align: "center", sortable: false, key: "email" },
    { title: "DNI", align: "center", sortable: false, key: "dni" },
    { title: "Acción", align: "center", sortable: false, key: "options" },
]);

const confirmState = (id, event_id) => {
    Swal.fire({
        title: "Atención!",
        text: "¿Estás seguro de cambiar el estado del participante a Asistente?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Sí",
    }).then((result) => {
        if (result.isConfirmed) {
            stateParticipant(id, event_id);
        }
    });
};
const stateParticipant = async (id, event_id) => {
    loading.value = true;
    axios
        .get("/stateParticipant/" + id + "/" + event_id)
        .then((response) => {
            Swal.fire({
                title: "¡Correcto!",
                text: "El estado ha sido actualizado.",
                icon: "success",
            });
            loading.value = false;
            getParticipantData(event_id);
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        });
}

const getParticipantData = async ($id) => {
    loading.value = true;
    let numberAs = 0;
    localStorage.setItem("event_id", $id);
    await axios
        .get("/getParticipantInscrit/" + $id)
        .then((response) => {
            dataParticipant.value = response.data.map((part, index) => {
                if (part.state === 'Asistente') {
                    numberAs++
                }
                return {
                    ...part,
                    phone: part.phone !== null ? part.phone : 'N/A',
                    charge: part.charge !== null ? part.charge : 'N/A',
                    created_at: part.created_at.substr(0, 10),
                    number: index + 1
                };
            });
            loading.value = false;
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        });
    numberAssist.value = numberAs;
};

const qrParticipantEvent = async (qr) => {
    if (qr) {
        window.open('/support/qrParticipant/' + qr);
    }
};
const getEvents = async () => {
    loading.value = true;
    try {
        const response = await axios.get("/getEventData");
        dataEvent.value = response.data.map(item => ({
            value: item.id,
            text: item.name
        }));
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};
const getParticipants = async () => {
    loading.value = true;
    try {
        const response = await axios.get("/getParticipantData?Q=1");
        dataDni.value = response.data.map(item => ({
            value: item.id,
            text: item.dni + ' - ' + item.name + ' ' + item.lastname,
            dni: item.dni
        }));
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    await getEvents();
    await getParticipants();
    const data = localStorage.getItem("event_id");
    if (data !== null && dataEvent.value.length > 0) {
        event_id.value = parseInt(data);
        if (event_id.value !== null) {
            await getParticipantData(event_id.value);
        }
    }
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

.swal2-container {
    z-index: 2500;
}
</style>

<template>
    <div id="main-wrapper" class="mini-sidebar pb-16">
        <Loader :loading="loading" />
        <DataTable ref="datatableParticipant" class="tabla-m" :title="'Participantes'" :headers="headers"
            :items="dataParticipant" :button_add="true" @changeItems="changeItems" :elevation="25" :showSearch="true"
            @click-add="dialogVisible = true,
                titleModal = 'AGREGAR PARTICIPANTE',
                participant.eventP = event_id" :show_select="true">
            <template v-slot:autocomplet-header>
                <div class="d-flex align-center ml-4">
                    <div class="col-6">
                        <v-autocomplete label="Selecciona un evento" v-model="event_id" :items="dataEvent" item-title="text"
                            item-value="value" :loading="loading" loading-text="Cargando eventos..."
                            @update:modelValue="getParticipantData(event_id)"></v-autocomplete>
                    </div>
                    <div class="ml-4" v-if="event_id !== ''">
                        <v-btn color="secondary" class="mr-2 mt-1 same-size-btn" prepend-icon="mdi-file-excel"
                            @click="getEventExport(event_id)">Exportar </v-btn>
                        <v-btn color="#6e0958" class="mr-2 mt-1 same-size-btn" prepend-icon="mdi-table-arrow-down"
                            @click="getParticipantAirtable()">
                            AIRTABLE
                        </v-btn>
                        <template v-if="select_state.length > 0">
                            <v-btn color="blue-grey-lighten-1" prepend-icon="mdi mdi-update" class="mr-2 mt-1 same-size-btn"
                                @click="dialogState = true">Estado </v-btn>
                            <v-btn color="blue-grey-lighten-5" prepend-icon="mdi-file-certificate"
                                class="mr-2 mt-1 same-size-btn" @click="updateParticipantState('CERTIFICADO')">Certificado
                            </v-btn>
                            <v-btn color="grey-lighten-1" prepend-icon="mdi-card-account-details"
                                class="mr-2 mt-1 same-size-btn" @click="dialogTag = true">Etiqueta </v-btn>
                            <v-btn color="blue-grey-lighten-1" prepend-icon="mdi-card-account-mail"
                                class="mr-2 mt-1 same-size-btn" @click="updateParticipantState('ACREDITACION')">Acreditación
                            </v-btn>
                        </template>
                    </div>
                </div>
            </template>
            <template v-slot:[`item.id`]="{ item, index }">
                <v-container>
                    <v-row align="center" justify="center">
                        <v-col cols="auto">
                            <v-tooltip text="Tooltip">
                                <template v-slot:activator="{ props }">
                                    <v-checkbox v-model="select_state" label="" color="grey-darken-4" :value="item.id"
                                        hide-details></v-checkbox>
                                </template>
                            </v-tooltip>
                        </v-col>
                    </v-row>
                </v-container>
            </template>
            <template v-slot:[`item.options`]="{ item }">
                <v-container>
                    <v-row align="center" justify="center">
                        <v-col cols="auto">
                            <v-tooltip text="Tooltip">
                                <template v-slot:activator="{ props }">
                                    <v-btn icon size="small" v-bind="props"
                                        @click="titleModal = 'EDITAR PARTICIPANTE', dialogVisible = true, getParticipantId(item.id, item.event_id)"
                                        title="Editar">
                                        <v-icon color="#616161">mdi mdi-file-document-edit</v-icon></v-btn>
                                </template>
                                <span>Editar</span>
                            </v-tooltip>
                        </v-col>
                        <v-col cols="auto">
                            <v-tooltip text="Tooltip">
                                <template v-slot:activator="{ props }">
                                    <v-btn icon size="small" v-bind="props"
                                        @click="dialogHistory = true, titleEvent = item, getHistorialEvPa(item.event_id, item.id)"
                                        title="Historial">
                                        <v-icon color="#616161">mdi mdi-history</v-icon>
                                    </v-btn>
                                </template>
                                <span>Historial</span>
                            </v-tooltip>
                        </v-col>
                        <v-col cols="auto" v-if="item.state === 'Inscrito'">
                            <v-tooltip text="Tooltip">
                                <template v-slot:activator="{ props }">
                                    <v-btn icon size="small" v-bind="props" title="Generar QR"
                                        @click="qrParticipantEvent(item.qr)">
                                        <v-icon color="#616161">mdi mdi-qrcode-scan</v-icon>
                                    </v-btn>
                                </template>
                                <span>Generar QR</span>
                            </v-tooltip>
                        </v-col>
                        <v-col cols="auto">
                            <v-tooltip text="Tooltip">
                                <template v-slot:activator="{ props }">
                                    <v-btn icon size="small" v-bind="props" @click="confirmDeletion(item.id, item.event_id)"
                                        title="Eliminar">
                                        <v-icon color="#616161">mdi mdi-trash-can</v-icon>
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
    <!--Modal add and edit-->
    <v-dialog width="1500" v-model="dialogVisible" persistent>
        <v-card :title=titleModal>
            <v-form @submit.prevent="setParticipant">
                <div class="mx-4">
                    <v-row class="mt-1">
                        <v-col cols="4">
                            <v-text-field :error-messages="errorMessages.name" v-model="participant.name" label="Nombre *"
                                type="text" required></v-text-field>
                        </v-col>
                        <v-col cols="4">
                            <v-text-field :error-messages="errorMessages.lastname" v-model="participant.lastname"
                                label="Apellidos *" type="text" required></v-text-field>
                        </v-col>
                        <v-col cols="4">
                            <v-text-field :error-messages="errorMessages.dni" v-model="participant.dni"
                                @keyup="searchParticipant" label="NIF/Pasaporte *" type="text" required></v-text-field>
                            <div v-if="participantResult.length > 0" class="input-search">
                                <table id="table_responsable_hallazgo" class="table table-hover table-bordered" width="100%"
                                    style="background: #fff;" cellspacing="0" cellpadding="0">
                                    <tbody>
                                        <tr class="m-1" v-for="(participant, index) in participantResult" :key="index"
                                            style="cursor: pointer;" @click="getParticipantId(participant.id, event_id)">
                                            <td> {{ participant.dni + ' - ' + participant.name + ' ' + participant.lastname
                                            }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="4">
                            <v-text-field v-model="participant.country" label="País" type="text" required></v-text-field>
                        </v-col>
                        <v-col cols="4">
                            <v-text-field v-model="participant.province" label="Provincia" type="text"
                                required></v-text-field>
                        </v-col>
                        <v-col cols="4">
                            <v-text-field :error-messages="errorMessages.email" v-model="participant.email" label="Email *"
                                type="email" required=""></v-text-field>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="4">
                            <v-text-field v-model="participant.phone" label="Teléfono" type="text" @keypress="onlyNumbers"
                                required></v-text-field>
                        </v-col>
                        <v-col cols="4">
                            <v-text-field v-model="participant.visitor_typology" label="Tipología del visitante" type="text"
                                required></v-text-field>
                        </v-col>
                        <v-col cols="4" class="mx-0">
                            <v-autocomplete label="Selecciona un evento *" :items="dataEvent" item-title="text"
                                item-value="value" v-model="participant.eventP" :loading="loading"
                                :error-messages="errorMessages.eventP" loading-text="Cargando eventos..."
                                :disabled="eventDisabled"></v-autocomplete>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="4">
                            <v-autocomplete label="Estado *" v-model="participant.state" :error-messages="errorMessages.state"
                                :items="['Preinscrito', 'Inscrito', 'Asistente', 'Cancelado']"></v-autocomplete>
                        </v-col>
                    </v-row>
                    <p><b>DATOS DE FACTURACIÓN</b></p>
                    <v-row>
                        <v-col cols="4">
                            <v-text-field v-model="participant.company_name" label="Nombre completo o Razón social"
                                type="text" required></v-text-field>
                        </v-col>
                        <v-col cols="4">
                            <v-text-field v-model="participant.cif_nif" label="CIF/NIF (facturación)" type="text"
                                required></v-text-field>
                        </v-col>
                        <v-col cols="4">
                            <v-text-field v-model="participant.billing_email" label="Correo electrónico (facturación)"
                                type="text" required></v-text-field>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="4">
                            <v-text-field v-model="participant.billing_address" label="Domicilio (facturación)" type="text"
                                required></v-text-field>
                        </v-col>
                        <v-col cols="4">
                            <v-text-field v-model="participant.billing_cp" label="Código Postal (facturación)" type="text"
                                required></v-text-field>
                        </v-col>
                        <v-col cols="4">
                            <v-text-field v-model="participant.billing_locality" label="Localidad (facturación)" type="text"
                                required></v-text-field>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="4">
                            <v-text-field v-model="participant.billing_province" label="Provincia (facturación)" type="text"
                                required></v-text-field>
                        </v-col>
                        <v-col cols="4">
                            <v-text-field v-model="participant.billing_country" label="País (facturación)" type="text"
                                required></v-text-field>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="12">
                            <v-btn color="#6e0958" type="submit" class="mr-2 ">GUARDAR</v-btn>
                            <v-btn color="light" @click="clearFrm()">CANCELAR</v-btn>
                        </v-col>
                    </v-row>
                </div>
            </v-form>
        </v-card>
    </v-dialog>
    <!--Modal history-->
    <v-dialog width="900" v-model="dialogHistory" persistent>
        <v-card :title="'Historial de ' + titleEvent.name + ' ' + titleEvent.lastname + ' en ' + titleEvent.event">
            <v-container>
                <DataTable class="tabla-m" :title="'Historial'" :headers="headersHis" :items="dataHistory" :elevation="0"
                    :hide_header="true" :button_add="true" :showHeader="false">
                </Datatable>
                <v-row class="justify-end mt-2">
                    <v-col cols="12" class="d-flex justify-end">
                        <v-btn color="light" @click="dialogHistory = false">CANCELAR</v-btn>
                    </v-col>
                </v-row>
            </v-container>
        </v-card>
    </v-dialog>
    <!--Modal State-->
    <v-dialog width="500" v-model="dialogState" persistent>
        <v-card title="Actualizar estado">
            <v-container>
                <v-row>
                    <v-col cols="12">
                        <v-autocomplete label="Estado" v-model="participant_state"
                            :items="['Invitado', 'Confirmado', 'Inscrito', 'Asistente', 'Cancelado']"></v-autocomplete>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col cols="12">
                        <v-btn color="#6e0958" type="button" class="mr-2" @click="stateParticipant">GUARDAR</v-btn>
                        <v-btn color="light" @click="dialogState = false">CANCELAR</v-btn>
                    </v-col>
                </v-row>
            </v-container>
        </v-card>
    </v-dialog>
    <!--Modal Tag-->
    <v-dialog width="280" v-model="dialogTag" persistent>
        <v-card title="Generar etiquetas">
            <v-container>
                <v-row class="d-flex justify-center">
                    <v-col cols="auto">
                        <v-btn color="secondary" prepend-icon="mdi mdi-qrcode-remove" class="mr-2"
                            @click="qr = false, updateParticipantState('ETIQUETA')">Sin QR </v-btn>
                        <v-btn color="blue-grey-lighten-1" prepend-icon="mdi mdi-qrcode-scan" class="mr-2"
                            @click="qr = true, updateParticipantState('ETIQUETA')">Con QR </v-btn>
                    </v-col>
                </v-row>
                <v-row class="d-flex justify-end">
                    <v-col cols="auto">
                        <v-btn color="light" @click="dialogTag = false">CANCELAR</v-btn>
                    </v-col>
                </v-row>
            </v-container>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { ref, onMounted, reactive } from "vue";
import axios from "axios";
import DataTable from '../utilities/Datatable.vue'
import Swal from 'sweetalert2';
import Loader from "../utilities/Loader.vue";
const loading = ref(true);
const eventDisabled = ref(false);
const dataParticipant = ref([]);
const event_id = ref("");
const participant_state = ref("");
const participantResult = ref([]);
const dataEvent = ref([]);
const dataHistory = ref([]);
const dataDni = ref([]);
const titleModal = ref('AGREGAR PARTICIPANTE');
const titleEvent = ref("");
const datatableParticipant = ref(null);
const errorMessages = reactive({
    name: null,
    lastname: null,
    email: null,
    dni: null,
    eventP: null,
    state: null,
});
const intolerance = ref(false);
const breakfast = ref(false);
const food_1 = ref(false);
const food_2 = ref(false);
const day_1 = ref(false);
const day_2 = ref(false);
const qr = ref(false);
const select_state = ref([]);
const headers = ref([
    { title: "Nombre", align: "start", sortable: false, key: "name" },
    { title: "Apellido", align: "center", sortable: false, key: "lastname" },
    { title: "Email", align: "center", sortable: false, key: "email" },
    { title: "NIF/Pasaporte", align: "center", sortable: false, key: "dni" },
    { title: "Teléfono", align: "center", sortable: false, key: "phone" },
    { title: "Estado", align: "center", sortable: false, key: "state" },
    { title: "Creación", align: "center", sortable: false, key: "created_at" },
    { title: "Acción", align: "center", sortable: false, key: "options" },
]);

const headersHis = ref([
    { title: "Estado", align: "center", sortable: false, key: "state" },
    { title: "Creación", align: "center", sortable: false, key: "created_at" },
    { title: "Usuario", align: "center", sortable: false, key: "name" }
]);

const participant = reactive({
    id: "",
    name: "",
    lastname: "",
    email: "",
    country: "",
    province: "",
    visitor_typology: "",
    company_name: "",
    dni: "",
    phone: "",
    eventP: "",
    state: "",
    cif_nif: "",
    billing_email: "",
    billing_address: "",
    billing_cp: "",
    billing_locality: "",
    billing_province: "",
    billing_country: ""
});


const getParticipantId = (participant_id, event_id) => {
    loading.value = true;
    participantResult.value = [];
    axios
        .get("/getParticipantId/" + participant_id + "/" + event_id)
        .then((response) => {
            participant.id = response.data.participants.id;
            participant.dni = response.data.participants.dni;
            participant.name = response.data.participants.name;
            participant.lastname = response.data.participants.lastname;
            participant.email = response.data.participants.email;
            participant.visitor_typology = response.data.participants.visitor_typology;
            participant.phone = response.data.participants.phone;
            participant.country = response.data.participants.country;
            participant.province = response.data.participants.province;
            if (response.data.event_part !== null) {
                participant.eventP = response.data.event_part.event_id;
                eventDisabled.value = true;
            } else {
                participant.eventP = event_id;
            }
            if (response.data.state !== null) {
                participant.state = response.data.state.state;
            }
            if (response.data.billing !== null) {
                participant.company_name = response.data.billing.company_name;
                participant.billing_email = response.data.billing.billing_email;
                participant.cif_nif = response.data.billing.cif_nif;
                participant.billing_address = response.data.billing.billing_address;
                participant.billing_cp = response.data.billing.billing_cp;
                participant.billing_locality = response.data.billing.billing_locality;
                participant.billing_province = response.data.billing.billing_province;
                participant.billing_country = response.data.billing.billing_country;
            }
            loading.value = false;
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        });
};

const confirmDeletion = (id, event_id) => {
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
            deleteParticipant(id, event_id);
        }
    });
};
const deleteParticipant = async (id, event_id) => {
    loading.value = true;
    axios
        .get("/deleteParticipant/" + id + "/" + event_id)
        .then((response) => {
            Swal.fire({
                title: "¡Borrado!",
                text: "El participante ha sido eliminado del evento.",
                icon: "success",
            });
            loading.value = false;
            getParticipantData(event_id);
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        });
};
const dialogVisible = ref(false);
const dialogHistory = ref(false);
const dialogState = ref(false);
const dialogTag = ref(false);
const validateFields = () => {
    if (participant.name.trim() === "") {
        errorMessages.name = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.name = "";
    }
    if (participant.lastname.trim() === "") {
        errorMessages.lastname = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.lastname = "";
    }
    if (participant.email.trim() === "") {
        errorMessages.email = "Este campo es obligatorio.";
        return false;
    } else if (!validarCorreoElectronico(participant.email.trim())) {
        errorMessages.email = "Por favor, ingresa un correo electrónico válido.";
        return false;
    } else {
        errorMessages.email = "";
    }

    if (participant.dni.trim() === "") {
        errorMessages.dni = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.dni = "";
    }
    if (typeof participant.eventP === 'string' && (participant.eventP.trim() === "" || participant.eventP.trim() === '""')) {
        errorMessages.eventP = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.eventP = "";
    }
    if (typeof participant.state === 'string' && (participant.state.trim() === "" || participant.state.trim() === '""')) {
        errorMessages.state = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.state = "";
    }
    return true;

};

const validarCorreoElectronico = (email) => {
    // Expresión regular para validar el formato del correo electrónico
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    return emailRegex.test(email);
}
const onlyNumbers = (event) => {
    let keyCode = (event.keyCode ? event.keyCode : event.which);
    // Permitir solo números del 0 al 9
    if (keyCode < 48 || keyCode > 57) {
        event.preventDefault();
    }
};
const getParticipantData = async ($id) => {
    loading.value = true;
    await axios
        .get("/getParticipantData?id=" + $id + "&Q=0")
        .then((response) => {
            dataParticipant.value = response.data.map((part, index) => ({
                ...part,
                phone: part.phone !== null ? part.phone : 'N/A',
                created_at: part.created_at.substr(0, 10),
                number: index + 1
            }));
            loading.value = false;
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        })
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

const searchParticipant = () => {
    if (participant.dni.length > 3) {
        axios
            .get("/getParticipantData?param=" + participant.dni + "&Q=2")
            .then((response) => {
                if (response.data.length > 0) {
                    participantResult.value = response.data;
                } else {
                    participantResult.value = [];
                    if (event_id.value !== "") {
                        participant.eventP = event_id.value;
                    } else {
                        participant.eventP = "";
                    }
                }
            })
            .catch((error) => {
                console.error(error);
            });
    }
};
const getHistorialEvPa = async ($event_id, $participant_id) => {
    loading.value = true;
    await axios
        .get("/getParticipantData?event_id=" + $event_id + '&Q=3 & participant_id=' + $participant_id)
        .then((response) => {
            dataHistory.value = response.data.map((part) => {
                const fecha = new Date(part.created_at);
                const fechaFormateada = fecha.toLocaleDateString();
                const horaFormateada = fecha.toLocaleTimeString();
                return {
                    ...part,
                    name: (part.name !== null) ? part.name : 'API',
                    created_at: fechaFormateada + ' ' + horaFormateada,
                };
            });
            loading.value = false;
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        });
};

const setParticipant = () => {
    if (!validateFields()) {
        return;
    }
    loading.value = true;
    const formData = {
        participant: participant,
        title: titleModal.value
    };
    axios
        .post('/addParticipant', formData)
        .then((response) => {
            if (response.data.message) {
                Swal.fire("Correcto!", response.data.message, "success");
                if (event_id.value !== "") {
                    getParticipantData(event_id.value)
                }
                clearFrm();
            } else {
                Swal.fire(
                    "Atención!",
                    response.data.error + participant.dni,
                    "warning"
                );
                loading.value = false;
            }
        })
        .catch((error) => {
            loading.value = false;
        });
};
const qrParticipantEvent = async (qr) => {
    if (qr) {
        window.open('/support/qrParticipant/' + qr);
    }
};

const stateParticipant = async () => {
    loading.value = true;
    const formData = {
        event_id: event_id.value,
        participant: select_state.value,
        state: participant_state.value,
    };
    if (participant_state.value === "") {
        Swal.fire("Atención!", "Debe seleccionar un estado", "warning");
        loading.value = false;
    } else {
        await axios
            .post("/stateParticipantManual", formData)
            .then((response) => {
                Swal.fire("Correcto!", "Estados actualizados correctamente.", "success");
                loading.value = false;
                dialogState.value = false;
                select_state.value = [];
                datatableParticipant.value.clearItemsCheck()
                participant_state.value = "";
                getParticipantData(event_id.value);
            })
            .catch((error) => {
                console.error(error);
                loading.value = false;
            })
    }
};
const getEventExport = async (id) => {
    window.open('/getEventExport/' + id);
};
const changeItems = (data) => {
    select_state.value = [];
    data.forEach(item => {
        const id = item.id;
        const state = item.state;
        select_state.value.push({ id, state });
    });
};
const clearFrm = () => {
    dialogVisible.value = false;
    breakfast.value = false;
    intolerance.value = false;
    food_1.value = false;
    food_2.value = false;
    day_1.value = false;
    day_2.value = false;
    participant.id = "";
    participant.name = "";
    participant.lastname = "";
    participant.company_name = "";
    participant.email = "";
    participant.cif_nif = "";
    participant.dni = "";
    participant.visitor_typology = "";
    participant.eventP = "";
    participant.country = "";
    participant.province = "";
    participant.phone = "";
    participant.state = "";
    participant.billing_email = "";
    participant.cif_nif = "";
    participant.billing_address = "";
    participant.billing_cp = "";
    participant.billing_locality = "";
    participant.billing_province = "";
    participant.billing_country = "";
    loading.value = false;
    eventDisabled.value = false;
    participantResult.value = [];
};
const updateParticipantState = async ($action) => {
    loading.value = true;
    const formData = {
        event_id: event_id.value,
        participant: select_state.value,
        qr: qr.value
    };
    let existAsis = false;
    for (let i = 0; i < select_state.value.length; i++) {
        if ($action === 'CERTIFICADO') {
            if (select_state.value[i].state === 'Asistente') {
                existAsis = true;
                break;
            }
        } else {
            if (select_state.value[i].state === 'Inscrito') {
                existAsis = true;
                break;
            }
        }
    }
    if ($action === 'CERTIFICADO') {
        if (existAsis) {
            try {
                const response = await axios.get("/certificateParticipant/" + btoa(JSON.stringify(formData)));
                Swal.fire({
                    title: "¡Correcto!",
                    text: response.data.message,
                    icon: "success",
                });
                getParticipantData(event_id.value);
            } catch (error) {
                Swal.fire({
                    title: "Atención!",
                    text: error.response.data.message,
                    icon: "warning",
                });
                loading.value = false;
                console.error(error);
            } finally {
                loading.value = false;
            }
        } else {
            Swal.fire("Atención!", "Solo se generan certificados para participantes con estado 'Asistente'", "warning");
        }
    }
    if ($action === 'ETIQUETA') {
        if (existAsis) {
            window.open('/tagParticipant/' + btoa(JSON.stringify(formData)));
            dialogTag.value = false;
            qr.value = false;
        } else {
            Swal.fire("Atención!", "Solo se generan etiquetas para participantes con estado 'Inscrito'", "warning");
        }
    }
    if ($action === 'ACREDITACION') {
        if (existAsis) {
            try {
                const response = await axios.get("/sendAccreditation/" + btoa(JSON.stringify(formData)));
                Swal.fire({
                    title: "¡Correcto!",
                    text: response.data.message,
                    icon: "success",
                });
                getParticipantData(event_id.value);
            } catch (error) {
                Swal.fire({
                    title: "Atención!",
                    text: error.response.data.message,
                    icon: "warning",
                });
                loading.value = false;
                console.error(error);
            } finally {
                loading.value = false;
            }
        } else {
            Swal.fire("Atención!", "Solo se envía acreditación  para participantes con estado 'Inscrito'", "warning");
        }
    }
    loading.value = false;
    select_state.value = [];
    datatableParticipant.value.clearItemsCheck()
};
const getParticipantAirtable = async () => {
    loading.value = true;
    try {
        const response = await axios.get("/getParticipantAirtable");
        Swal.fire({
            title: "¡Correcto!",
            text: response.data.message,
            icon: "success",
        });
        getParticipantData(event_id.value);
    } catch (error) {
        Swal.fire({
            title: "Atención!",
            text: error.response.data.message,
            icon: "warning",
        });
        loading.value = false;
        console.error(error);
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    await getEvents();
    await getParticipants();
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

.custom-control-label {
    font-size: 14px !important;
}

.orderrow {
    margin: 0 0 0 5px !important;
}

.same-size-btn {
    min-width: 170px !important;
    /* Ajusta este valor según sea necesario */
}
.v-col-4, .v-col-3, .v-col-2, .v-col-1 {
  width: 100%;
  padding: 5px !important;
}
</style>

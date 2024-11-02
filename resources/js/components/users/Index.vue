<template>
    <div id="main-wrapper" class="mini-sidebar pb-16">
        <Loader :loading="loading" />
        <DataTable class="tabla-m" :title="'Usuarios'" :button_export="false" :headers="headers" :items="dataUser"
            :emit-click-row="true" sort-by="id" :sort-desc="true" :filtros="filtros" :paginacion="null" :button_add="true"
            @click-add="dialogVisible = true, titleModal = 'AGREGAR USUARIO', getRol()">
            <template v-slot:[`item.status`]="{ item, index }">
                <v-container>
                    <v-row align="center" justify="center">
                        <v-col cols="auto">
                            <v-tooltip text="Tooltip">
                                <template v-slot:activator="{ props }">
                                    <v-switch v-if="item.deleted_at === 'activo'" v-model="checkboxes[index]"
                                        @change="updateState(item)" color="dark" label="Activo" :value="true"
                                        hide-details></v-switch>
                                    <v-switch v-else v-model="checkboxes[index]" @change="updateState(item)" color="dark"
                                        label="Inactivo" value="false" hide-details></v-switch>
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
                                        @click="dialogHistory = true, getAccessHistory(item.id)">
                                        <v-icon color="#a1a5b7">mdi mdi-clipboard-text-clock-outline</v-icon></v-btn>
                                </template>
                                <span>Historial </span>
                            </v-tooltip>
                        </v-col>
                        <v-col cols="auto">
                            <v-tooltip text="Tooltip">
                                <template v-slot:activator="{ props }">
                                    <v-btn icon size="small" v-bind="props"
                                        @click="titleModal = 'EDITAR USUARIO', dialogVisible = true, getUserId(item.id)">
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
        <!--Modal add and edit-->
        <v-dialog width="600" v-model="dialogVisible" persistent>
            <v-card :title=titleModal>
                <v-form @submit.prevent="setData">
                    <v-container>
                        <v-row>
                            <v-col cols="12">
                                <v-text-field :error-messages="errorMessages.name" v-model="user.name"
                                    label="Nombre Completo" type="text" required></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-text-field :error-messages="errorMessages.email" v-model="user.email" label="Email"
                                    type="email" required=""></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-text-field :error-messages="errorMessages.password" v-model="user.password"
                                    label="Contraseña" :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                                    :type="showPassword ? 'text' : 'password'" @click:append="showPassword = !showPassword"
                                    required></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-text-field :error-messages="errorMessages.confirmPassword" v-model="user.confirmPassword"
                                    label="Confirmar contraseña"
                                    :append-icon="showConfirmPassword ? 'mdi-eye' : 'mdi-eye-off'"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    @click:append="showConfirmPassword = !showConfirmPassword" required></v-text-field>
                            </v-col>
                            <v-col cols="12">
                                <v-autocomplete label="Selecciona un rol" :items="dataRol" item-title="text" :error-messages="errorMessages.rol_id"
                                    item-value="value" v-model="user.rol_id" :loading="loading"
                                    loading-text="Cargando roles..."></v-autocomplete>
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
        <!--Modal history-->
        <v-dialog width="1200" v-model="dialogHistory" persistent>
            <v-card :title="'HISTORIAL DE USUARIO'">
                <v-card-text>
                    <DataTable class="tabla-m" :items="historyData" :elevation="0" :headers="headers_history"
                        :hide_header="true" :button_add="true" :showHeader="false">
                        <template v-slot:[`item.session_data`]="{ item }">
                            <span><b>ID:</b> {{ JSON.parse(item.session_data).id }}</span><br />
                            <span><b>Nombre: </b> {{ JSON.parse(item.session_data).name }}</span><br />
                            <span><b> Rol:</b> {{ JSON.parse(item.session_data).rol }}</span><br />
                        </template>
                    </Datatable>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn variant="tonal" text="Cancelar" class="black-close" @click="dialogHistory = false"></v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import Loader from "../utilities/Loader.vue";
import { ref, onMounted, reactive } from "vue";
import axios from "axios";
import DataTable from '../utilities/Datatable.vue'
import Swal from 'sweetalert2';
import { formatDateTime } from "@/helpers";
const headers = ref([
    { title: "Usuario", align: "start", sortable: false, key: "name" },
    { title: "Email", align: "center", sortable: false, key: "email" },
    { title: "Rol", align: "center", sortable: false, key: "rol" },
    { title: "Estado", align: "center", sortable: false, key: "status" },
    { title: "Creación", align: "center", sortable: false, key: "created_at" },
    { title: "Acción", align: "center", sortable: false, key: "options" },
]);
const headers_history = ref([
    { title: "Datos de sesión", align: "start", sortable: true, key: "session_data", },
    { title: "Dirección IP", align: "center", sortable: true, key: "ip" },
    { title: "Navegador", align: "center", sortable: false, key: "browser" },
    { title: "Plataforma", align: "center", sortable: false, key: "platform" },
    { title: "Fecha y hora", align: "center", sortable: false, key: "created_at", },
]);
const titleModal = ref('AGREGAR USUARIO');
const checkboxes = ref([]);
const dataRol = ref([]);
const filtros = {
    search: null,
    order: {
        by: ["f_solicitud"],
        desc: [true],
    },
    paginacion: {
        current_page: 1,
        items_per_page: 10,
    },
    fecha: {
        desde: null,
        hasta: null,
        tipo: "Fecha de gestión",
    },
    ajustes: [],
};
const dataUser = ref([]);
const dialogVisible = ref(false);
const loading = ref(false);
const user = reactive({
    id: "",
    name: "",
    email: "",
    password: "",
    confirmPassword: "",
    rol_id: ""
});
const historyData = ref([]);
const dialogHistory = ref(false);
const showPassword = ref(false);
const showConfirmPassword = ref(false);
const errorMessages = reactive({
    name: null,
    email: null,
    password: "",
    confirmPassword: "",
    rol_id: ""
});
const validateFields = () => {
    if (user.name.trim() === "") {
        errorMessages.name = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.name = "";
    }
    if (user.email.trim() === "") {
        errorMessages.email = "Este campo es obligatorio.";
        return false;
    } else if (!validarCorreoElectronico(user.email.trim())) {
        errorMessages.email = "Por favor, ingresa un correo electrónico válido.";
        return false;
    } else {
        errorMessages.email = "";
    }
    if (user.password.trim() === "") {
        errorMessages.password = "Este campo es obligatorio.";
        return false;
    } else if (user.password.length < 8) {
        errorMessages.password = "La contraseña debe tener al menos 8 caracteres.";
        return false;
    } else {
        errorMessages.password = "";
    }

    if (user.confirmPassword.trim() === "") {
        errorMessages.confirmPassword = "Este campo es obligatorio.";
        return false;
    } else if (user.password !== user.confirmPassword) {
        errorMessages.confirmPassword = "Las contraseñas no coinciden.";
        return false;
    } else {
        errorMessages.confirmPassword = "";
    }
    if (typeof user.rol_id === 'string' && (user.rol_id.trim() === "" || user.rol_id.trim() === '""')) {
        errorMessages.rol_id = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.rol_id = "";
    }
    return true;

};
const clearFrm = () => {
    dialogVisible.value = false;
    user.id = "";
    user.name = "";
    user.email = "";
    user.password = "";
    user.confirmPassword = "";
    user.rol_id = "";
    loading.value = false;
};
onMounted(async () => {
    await getUserData();
});
const getRol = async () => {
    loading.value = true;
    try {
        const response = await axios.get("/getRol");
        dataRol.value = response.data.map(item => ({
            value: item.id,
            text: item.name
        }));
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};
const validarCorreoElectronico = (email) => {
    // Expresión regular para validar el formato del correo electrónico
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    return emailRegex.test(email);
}
const setData = () => {
    if (titleModal.value === 'AGREGAR USUARIO') {
        setUser();
    } else {
        updateUser();
    }
};
const setUser = () => {
    if (!validateFields()) {
        return;
    }
    loading.value = true;
    const formData = {
        user: user,
        title: titleModal.value
    };
    axios
        .post('/addUser', formData)
        .then((response) => {
            if (response.data.message === 'Ok') {
                Swal.fire("Correcto!", 'Usuario guardado exitosamente.', "success");
                getUserData()
                clearFrm();
            } else {
                Swal.fire(
                    "Atención!",
                    response.data.message + user.email,
                    "warning"
                );
                loading.value = false;
            }
        })
        .catch((error) => {
            loading.value = false;
        });
};
const updateUser = () => {
    if (user.name === '') {
        Swal.fire("Atención!", 'Debes digitar el nombre', "warning");
        return;
    } if (user.email === '') {
        Swal.fire("Atención!", 'Debes digitar el email', "warning");
        return;
    }
    if (!validarCorreoElectronico(user.email.trim())) {
        Swal.fire("Atención!", 'Por favor, ingresa un correo electrónico válido.', "warning");
        return;
    }
    loading.value = true;
    axios
        .post('/updateUser', user)
        .then((response) => {
            if (response.data.message === 'Ok') {
                Swal.fire("Correcto!", 'Usuario guardado exitosamente.', "success");
                clearFrm();
                getUserData()
            } else {
                Swal.fire("Atención!", response.data.message + user.email, "warning");
                loading.value = false;
            }
        })
        .catch((error) => {
            loading.value = false;
        });
};
const getUserData = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get("/getUserData");
        const checkboxes_aux = [];
        for (const i in data) {
            const status = data[i].deleted_at === null ? "activo" : "inactivo";
            data[i].deleted_at = status;
            data[i].created_at = data[i].created_at.substr(0, 10);
            checkboxes_aux.push(status == "activo" ? true : false);
        }
        dataUser.value = data;
        checkboxes.value = checkboxes_aux;
    } catch (error) {
        console.error(error);
    }
    loading.value = false;
};
const getAccessHistory = async (id) => {
    loading.value = true;
    await axios
        .get("/getAccessHistory/" + id)
        .then((response) => {
            historyData.value = response.data.data.map((intention) => ({
                ...intention,
                created_at: formatDateTime(intention.created_at),
            }));
        })
        .catch((error) => {
            console.error(error);
        })
        .finally(() => {
            loading.value = false;
        });
};
const updateState = (param) => {
    loading.value = true;
    const formData = {
        id: param.id,
        state: param.deleted_at,
    };
    axios
        .post("/updateState", formData)
        .then((response) => {
            Swal.fire({
                title: "Excelente",
                text: "Cambios realizados!",
                icon: "success",
            });
            getUserData();
            loading.value = false;
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        });
};
const getUserId = async (id) => {
    getRol();
    loading.value = true;
    await axios
        .get("/getUserId/" + id)
        .then((response) => {
            dialogVisible.value = true;
            user.id = response.data.data.user.id;
            user.name = response.data.data.user.name;
            user.email = response.data.data.user.email;
            user.rol_id = response.data.data.roles.id;
        })
        .catch((error) => {
            console.error(error);
        })
        .finally(() => {
            loading.value = false;
        });
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
            deleteUser(id);
        }
    });
};
const deleteUser = async (id) => {
    loading.value = true;
    axios
        .delete("/deleteUser/" + id)
        .then((response) => {
            Swal.fire({
                title: "Excelente",
                text: "Cambios realizados!",
                icon: "success",
            });
            getUserData();
            loading.value = false;
        })
        .catch((error) => {
            loading.value = false;
            console.error(error);
        });
};
</script>

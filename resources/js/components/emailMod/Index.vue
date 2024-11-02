<template>
    <Loader :loading="loading" />
    <div class="row">
        <div class="col">
            <v-card class="mx-auto">
                <v-card-title class="title_card">
                    <span style="font-size: 24px !important; font-weight: 600 !important"> Configuración </span>
                </v-card-title>
                <v-card-text class="mt-2">
                    <v-form @submit.prevent="setEmail">

                        <v-row>
                            <v-col cols="6">
                                <label>SMTPAuth</label>
                                <v-radio-group v-model="SMTPAuth" inline :error-messages="errorMessages.SMTPAuth">
                                    <v-radio label="True" value="true"></v-radio>
                                    <v-radio label="False" value="false"></v-radio>
                                </v-radio-group>
                            </v-col>
                            <v-col cols="6">
                                <v-text-field :error-messages="errorMessages.SMTPSecure" v-model="SMTPSecure"
                                    label="SMTPSecure" type="text" required=""></v-text-field>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="6">
                                <v-text-field :error-messages="errorMessages.host" v-model="host" label="Host" type="text"
                                    required=""></v-text-field>
                            </v-col>
                            <v-col cols="6">
                                <v-text-field :error-messages="errorMessages.port" v-model="port" label="Port" type="number"
                                    required=""></v-text-field>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="6">
                                <v-text-field :error-messages="errorMessages.email" v-model="email" label="Username"
                                    type="text" required=""></v-text-field>
                            </v-col>
                            <v-col cols="6">
                                <v-text-field :error-messages="errorMessages.password" v-model="password" label="Password"
                                    type="text" required=""></v-text-field>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="6">
                                <v-text-field :error-messages="errorMessages.fromE" v-model="fromE" label="DE" type="text"
                                    required=""></v-text-field>
                            </v-col>
                            <v-col cols="6">
                                <v-text-field :error-messages="errorMessages.api" v-model="api" label="Api" type="text"
                                    required=""></v-text-field>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="6">
                                <v-text-field :error-messages="errorMessages.token" v-model="token" label="Token"
                                    type="text" required=""></v-text-field>
                            </v-col>
                            <v-col cols="6">
                                <v-text-field :error-messages="errorMessages.view" v-model="view" label="Vista"
                                    type="text" required=""></v-text-field>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="12">
                                <v-btn color="#6e0958" type="submit" class="mr-2">GUARDAR</v-btn>
                            </v-col>
                        </v-row>
                    </v-form>
                </v-card-text>
            </v-card>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import Loader from "../utilities/Loader.vue";
import Swal from 'sweetalert2';
const loading = ref(false);
const email = ref('');
const SMTPAuth = ref('true');
const SMTPSecure = ref('');
const host = ref('');
const port = ref('');
const password = ref('');
const fromE = ref('');
const api = ref('');
const token = ref('');
const view = ref('');
onMounted(async () => {
    getEmailMod();
})
const errorMessages = reactive({
    email: null,
    SMTPAuth: null,
    SMTPSecure: null,
    host: null,
    port: null,
    password: null,
    fromE: null,
    api: null,
    token: null,
    view: null,
});
const setEmail = () => {
    if (!validateFields()) {
        return;
    }
    const formData = {
        email: email.value,
        SMTPAuth: SMTPAuth.value,
        SMTPSecure: SMTPSecure.value,
        host: host.value,
        port: port.value,
        password: password.value,
        fromE: fromE.value,
        api: api.value,
        token: token.value,
        view: view.value
    };
    loading.value = true;
    axios
        .post('/setEmailSend', formData)
        .then((response) => {
            Swal.fire("Correcto!", response.data.message, "success");
            getEmailMod
            loading.value = false;
        })
        .catch((error) => {
            loading.value = false;
            console.error(error);
        });
};
const getEmailMod = () => {
    loading.value = true;
    axios
        .get("/getEmailSend")
        .then((response) => {
            if (Object.keys(response.data).length > 0) {
                email.value = response.data.email;
                SMTPAuth.value = response.data.SMTPAuth === 1 ? 'true' : 'false';
                SMTPSecure.value = response.data.SMTPSecure;
                host.value = response.data.host;
                port.value = response.data.port;
                password.value = response.data.password;
                fromE.value = response.data.from;
                api.value = response.data.api;
                token.value = response.data.token;
                view.value = response.data.view;
            }
            loading.value = false;
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
        });
};
const validateFields = () => {
    if (!SMTPSecure.value || SMTPSecure.value.trim() === "") {
        errorMessages.SMTPSecure = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.SMTPSecure = "";
    }
    if (!host.value || host.value.trim() === "") {
        errorMessages.host = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.host = "";
    }
    if (!port.value || port.value.trim() === "") {
        errorMessages.port = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.port = "";
    }
    if (!password.value || password.value.trim() === "") {
        errorMessages.password = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.password = "";
    }
    if (!email.value || email.value.trim() === "") {
        errorMessages.email = "Este campo es obligatorio.";
        return false;
    } else if (!validarCorreoElectronico(email.value.trim())) {
        errorMessages.email = "Por favor, ingresa un correo electrónico válido.";
        return false;
    } else {
        errorMessages.email = "";
    }
    if (!fromE.value || fromE.value.trim() === "") {
        errorMessages.fromE = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.fromE = "";
    }
    if (!api.value || api.value.trim() === "") {
        errorMessages.api = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.api = "";
    }
    if (!token.value || token.value.trim() === "") {
        errorMessages.token = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.token = "";
    }
    if (!view.value || view.value.trim() === "") {
        errorMessages.view = "Este campo es obligatorio.";
        return false;
    } else {
        errorMessages.view = "";
    }
    return true;
};
const validarCorreoElectronico = (email) => {
    // Expresión regular para validar el formato del correo electrónico
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    return emailRegex.test(email);
}
</script>
<style scoped>
.title_card {
    background: #6e0958;
    color: #ffff !important;
    padding: 1.10rem !important;
}
</style>

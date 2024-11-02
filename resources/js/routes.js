import { createRouter, createWebHistory } from "vue-router";

import Index from "./components/eventsNoved/Index.vue";
import Participant from "./components/participants/Index.vue";
import User from "./components/users/Index.vue";
import EmailMod from "./components/emailMod/Index.vue";
import QrRead from "./components/assistanceQR/QrRead.vue";
import Assistance from "./components/assistanceQR/Assistance.vue";

const routes = [
    {
        path: "/",
        component: Index,
    },
    {
        path: "/participants",
        name: "participants",
        component: Participant,
    },
    {
        path: "/users",
        name: "users",
        component: User,
    },
    {
        path: "/qrRead/:id",
        name: "qrRead",
        component: QrRead,
    },

    {
        path: "/assistance",
        name: "assistance",
        component: Assistance,
    },
    {
        path: "/emailMod",
        name: "emailMod",
        component: EmailMod,
    },
];
const router = createRouter({
    history: createWebHistory(),
    routes,
});
export default router;

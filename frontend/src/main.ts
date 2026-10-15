import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import { router } from './router';
import vuetify from './plugins/vuetify';
import '@/scss/style.scss';
import { PerfectScrollbar } from 'vue3-perfect-scrollbar';
import VueApexCharts from 'vue3-apexcharts';
import VueTablerIcons from 'vue-tabler-icons';
import { fakeBackend } from '@/utils/helpers/fake-backend';
import 'vue3-carousel/dist/carousel.css';
//Mock Api data
import './_mockApis';
import { vCan } from '@/directives/can';

import { vMaska } from 'maska/vue';

//i18
import { createI18n } from 'vue-i18n';
import messages from '@/utils/locales/messages';

//ScrollTop
import VueScrollTo from 'vue-scrollto';

//LightBox
import VueEasyLightbox from 'vue-easy-lightbox';



const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: messages,
    silentTranslationWarn: true,
    silentFallbackWarn: true
});

const app = createApp(App);
if (import.meta.env.VITE_USE_FAKE_BACKEND === 'true') {
    fakeBackend();
}
app.use(router);

app.component('PerfectScrollbar', PerfectScrollbar);
// app.use(VueDragscroll);
app.use(createPinia());

app.use(VueTablerIcons);

app.use(i18n);
app.directive('maska', vMaska);
app.directive('can', vCan);
app.component('apexchart', VueApexCharts);
app.use(vuetify);

//ScrollTop Use
// app.use(VueScrollTo);
app.use(VueScrollTo, {
    duration: 1000,
    easing: "ease",
})
//Lightbox
app.use(VueEasyLightbox)

// La rehidratación de sesión y carga de permisos ahora corre DESPUÉS de
// montar (ver App.vue), que muestra un loader mientras tanto en vez de
// dejar la pantalla en blanco.
app.mount('#app');

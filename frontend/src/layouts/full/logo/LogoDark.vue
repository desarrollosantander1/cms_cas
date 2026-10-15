<template>
    <div class="logo">
        <RouterLink to="/">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                xmlns:xlink="http://www.w3.org/1999/xlink"
                width="257"
                height="50"
                viewBox="0 0 257 50"
            >

                <image
                    id="homeelements-logo"
                    x="0"
                    y="-9"
                    width="45"
                    height="45"
                    preserveAspectRatio="xMidYMid meet"
                    href="/assets/images/home-elements-logo.svg"
                    xlink:href="/assets/images/home-elements-logo.svg"/>

                <text
                    id="brand-name"
                    x="75"
                    y="25"
                    font-family="Plus Jakarta Sans"
                    font-size="26"
                    letter-spacing="-0.5"><tspan font-weight="700" fill="#2E9BC6">HEOS</tspan><tspan font-weight="500" fill="#11182F" fill-opacity="0.55" dx="7">5.0</tspan></text>
                <text
                    id="brand-tag"
                    x="76"
                    y="38"
                    fill="#7C8AA0"
                    font-family="Plus Jakarta Sans"
                    font-size="9.5"
                    letter-spacing="1.5">
                    HOME ELEMENTS
                </text>

            </svg>
        </RouterLink>
    </div>
</template>
<script setup lang="ts">
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
const primary = ref('rgb(var(--v-theme-primary))');
const secondary = ref('rgb(var(--v-theme-secondary))');
</script>

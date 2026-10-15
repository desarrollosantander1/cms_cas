<template>
    <Transition name="fade" @after-leave="$emit('after-leave')">
        <div v-if="show" class="loader" role="status" aria-live="polite" aria-label="Cargando">
            <div class="scene">
                <div class="art">
                    <img class="art__img" src="./assets/coffee-pour.png" alt="" aria-hidden="true" />

                    <svg class="art__fx" viewBox="0 0 1219 1290" preserveAspectRatio="xMidYMid meet" aria-hidden="true">
                        <defs>
                            <filter id="streamGlow" x="-50%" y="-50%" width="200%" height="200%">
                                <feGaussianBlur stdDeviation="3" result="blur" />
                                <feMerge>
                                    <feMergeNode in="blur" />
                                    <feMergeNode in="SourceGraphic" />
                                </feMerge>
                            </filter>
                        </defs>

                        <!-- refuerzo del chorro: flujo continuo sobre el trazo ya presente en la foto -->
                        <path
                            id="streamPath"
                            class="stream-path"
                            d="M515,462 C 503,520 478,580 468,630 C 459,680 464,722 469,758 C 472,780 471,802 470,824"
                            fill="none"
                            stroke="#6b2f14"
                            stroke-width="14"
                            stroke-linecap="round"
                            filter="url(#streamGlow)"
                        />

                        <!-- gotas cayendo, siguen la trayectoria del chorro -->
                        <circle class="drip drip--a" r="5" fill="#5c2a12">
                            <animateMotion dur="1.1s" repeatCount="indefinite" begin="0s">
                                <mpath href="#streamPath" />
                            </animateMotion>
                        </circle>
                        <circle class="drip drip--b" r="4" fill="#5c2a12">
                            <animateMotion dur="1.1s" repeatCount="indefinite" begin="0.37s">
                                <mpath href="#streamPath" />
                            </animateMotion>
                        </circle>
                        <circle class="drip drip--c" r="3.5" fill="#5c2a12">
                            <animateMotion dur="1.1s" repeatCount="indefinite" begin="0.74s">
                                <mpath href="#streamPath" />
                            </animateMotion>
                        </circle>

                        <!-- salpicaduras junto al punto de impacto -->
                        <circle class="splash splash--1" cx="560" cy="700" r="6" fill="#5c2a12" />
                        <circle class="splash splash--2" cx="596" cy="726" r="4" fill="#5c2a12" />
                        <circle class="splash splash--3" cx="540" cy="662" r="4.5" fill="#5c2a12" />

                        <!-- ondas de impacto en la taza -->
                        <circle class="ripple ripple--1" cx="470" cy="824" r="10" />
                        <circle class="ripple ripple--2" cx="470" cy="824" r="10" />
                        <circle class="ripple ripple--3" cx="470" cy="824" r="10" />

                        <!-- vapor -->
                        <g class="steam steam--1">
                            <path
                                d="M330,760 C 300,700 360,660 335,600 C 312,545 365,510 345,460"
                                fill="none"
                                stroke="#ffffff"
                                stroke-width="14"
                                stroke-linecap="round"
                                opacity="0.55"
                            />
                        </g>
                        <g class="steam steam--2">
                            <path
                                d="M400,770 C 372,715 428,672 405,615 C 385,565 432,528 415,480"
                                fill="none"
                                stroke="#ffffff"
                                stroke-width="10"
                                stroke-linecap="round"
                                opacity="0.45"
                            />
                        </g>
                    </svg>
                </div>

                <!-- <div class="brand">
                    <span class="brand__name">Home elements</span>
                </div> -->

                <div class="status">
                    <!-- <span class="status__text">Preparando tu sesión</span> -->
                    <span class="dots" aria-hidden="true">
                        <span class="dot" />
                        <span class="dot" />
                        <span class="dot" />
                    </span>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ show?: boolean }>(), { show: true });

defineEmits<{ 'after-leave': [] }>();
</script>

<style scoped>
.loader {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    /* background: radial-gradient(circle at 50% 30%, #f4efe8 0%, #e7e0d6 55%, #d9d0c2 100%); */
}

.scene {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.25rem;
    width: min(80vw, 320px);
}

.art {
    position: relative;
    width: 100%;
    aspect-ratio: 1219 / 1290;
}

.art__img,
.art__fx {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}

.art__img {
    object-fit: contain;
    animation: bob 3.2s ease-in-out infinite;
    filter: drop-shadow(0 18px 24px rgba(30, 20, 10, 0.18));
}

.art__fx {
    pointer-events: none;
}

.stream-path {
    opacity: 0.8;
    stroke-dasharray: 6 10;
    animation: flow 0.6s linear infinite;
}

.drip {
    opacity: 0.9;
}

.splash {
    transform-origin: center;
    animation: splash 1.1s ease-in-out infinite;
}
.splash--1 {
    animation-delay: 0s;
}
.splash--2 {
    animation-delay: 0.25s;
}
.splash--3 {
    animation-delay: 0.5s;
}

.ripple {
    fill: none;
    stroke: #7a4a2a;
    stroke-width: 3;
    opacity: 0;
    transform-origin: 470px 824px;
    animation: ripple 1.6s ease-out infinite;
}
.ripple--1 {
    animation-delay: 0s;
}
.ripple--2 {
    animation-delay: 0.53s;
}
.ripple--3 {
    animation-delay: 1.06s;
}

.steam {
    transform-origin: center;
    animation: rise 4.5s ease-in-out infinite;
}
.steam--2 {
    animation-duration: 5.2s;
    animation-delay: 0.8s;
}

.brand__name {
    font-family: 'Poppins', 'Segoe UI', system-ui, sans-serif;
    font-weight: 700;
    font-size: 1.05rem;
    letter-spacing: 0.02em;
    color: #2b2620;
}

.status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b5f4f;
    font-family: system-ui, sans-serif;
    font-size: 0.9rem;
}

.dots {
    display: inline-flex;
    gap: 0.25rem;
}

.dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #a9764a;
    animation: dotPulse 1.2s ease-in-out infinite;
}
.dot:nth-child(2) {
    animation-delay: 0.15s;
}
.dot:nth-child(3) {
    animation-delay: 0.3s;
}

@keyframes bob {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-6px);
    }
}

@keyframes flow {
    to {
        stroke-dashoffset: -32;
    }
}

@keyframes splash {
    0% {
        transform: translateY(0) scale(1);
        opacity: 0.9;
    }
    50% {
        transform: translateY(-14px) scale(0.7);
        opacity: 0.4;
    }
    100% {
        transform: translateY(4px) scale(0.4);
        opacity: 0;
    }
}

@keyframes ripple {
    0% {
        r: 8;
        stroke-width: 4;
        opacity: 0.6;
    }
    100% {
        r: 46;
        stroke-width: 0.5;
        opacity: 0;
    }
}

@keyframes rise {
    0% {
        transform: translateY(0);
        opacity: 0;
    }
    15% {
        opacity: 1;
    }
    85% {
        opacity: 0.3;
    }
    100% {
        transform: translateY(-40px);
        opacity: 0;
    }
}

@keyframes dotPulse {
    0%,
    80%,
    100% {
        opacity: 0.25;
        transform: scale(0.85);
    }
    40% {
        opacity: 1;
        transform: scale(1);
    }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.35s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .art__img,
    .stream-path,
    .drip,
    .splash,
    .ripple,
    .steam,
    .dot {
        animation: none !important;
    }
}
</style>

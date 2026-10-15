<script setup lang="ts">
import { ref, computed } from "vue";

/*Chart*/
const chartOptions = computed(() => {
    return {
        series: [60,25,15],
        labels: ["", "", "", ""],
        chart: {
            height: 120,
            type: "donut",
            foreColor: "#adb0bb",
            fontFamily: "inherit",
        },
        colors: [ 'rgba(var(--v-theme-primary))','rgba(var(--v-theme-secondary))', "#FFAE1F"],
        dataLabels: { enabled: false },
        legend: { show: false },
        grid: {
            show: false,
            borderColor: "transparent",
            padding: { top: 0, right: 0 },
        },
        stroke: { colors: ["transparent"] },
        plotOptions: {
            pie: {
                donut: {
                    size: "70%",
                    background: "transparent",
                    labels: {
                        show: false,
                        name: {
                            show: true,
                            fontSize: "18px",
                            color: undefined,
                            offsetY: -10,
                        },
                        value: { show: false, color: "#98aab4" },
                        total: { show: false, label: "Our Visitors", color: "#98aab4" },
                    },
                },
            },
        },
        tooltip: { theme: "dark", fillSeriesColor: false },
        responsive: [{ breakpoint: 1025, options: { chart: { height:185 } } }],

    }
});

const select = ref("March");
const items = ref(["March", "April", "May", "June"]);
</script>
<template>
    <v-card elevation="10">
        <v-card-item>
            <h4 class="text-h4 text-no-wrap">$10,230</h4>
            <h6 class="text-subtitle-1 textSecondary mt-1">Expense</h6>
            <apexchart type="donut" height="100" class="mt-3 ml-n2" :options="chartOptions" :series="chartOptions.series"></apexchart>
        </v-card-item>
    </v-card>
</template>

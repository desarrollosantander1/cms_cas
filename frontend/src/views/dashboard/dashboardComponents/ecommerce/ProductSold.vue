<script setup lang="ts">
import { computed } from 'vue';

/* Chart */
const chartOptions = computed(() => {
    return {
        chart: {
            type: 'bar',
            height: 80,
            fontFamily: `inherit`,
            offsetX: -5,
            resize: true,
            toolbar: {
                show: false
            },
            sparkline: {
                enabled: true
            }
        },
        colors: ['rgba(var(--v-theme-primary))', 'rgba(var(--v-theme-secondary))'],
        grid: {
            show: false
        },
        plotOptions: {
            bar: {
                horizontal: false,
                startingShape: 'flat',
                endingShape: 'flat',
                columnWidth: '60%',
                barHeight: '20%',
                borderRadius: 2
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: true,
            width: 2.5,
            colors: ['rgba(0,0,0,0.01)']
        },
        xaxis: {
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            },
            labels: {
                show: false
            }
        },
        yaxis: {
            labels: {
                show: false
            }
        },
        axisBorder: {
            show: false
        },
        fill: {
            opacity: 1
        },
        tooltip: {
            theme: 'dark',
            x: {
                show: false
            }
        }
    };
});

const seriescolumnchart = [
    {
        name: '',
        data: [25, 35, 20, 25, 40, 25]
    }
];
</script>
<template>
    <v-card elevation="10">
        <v-card-item class="pb-7">
            <h4 class="text-h4 text-no-wrap">$65,432</h4>
            <h6 class="text-subtitle-1 textSecondary mt-1">Sales</h6>
            <div class="ml-n2">
                <apexchart class="mt-5 ps-1 salechart" type="bar" height="85" :options="chartOptions"
                    :series="seriescolumnchart">
                </apexchart>
            </div>
        </v-card-item>
    </v-card>
</template>
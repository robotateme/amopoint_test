<template>
    <section class="dashboard-panel">
        <div class="panel-frame">
            <div class="panel-header">
                <div class="panel-title">
                    <span class="panel-index">{{ indexLabel }}</span>
                    <div>
                        <h2>{{ title }}</h2>
                        <p>{{ subtitle }}</p>
                    </div>
                </div>
                <div class="panel-meta">
                    <span :class="['panel-signal', metaTone]" />
                    <slot name="meta" />
                </div>
            </div>
            <div class="chart-shell">
                <span class="chart-corner top-left" />
                <span class="chart-corner top-right" />
                <span class="chart-corner bottom-left" />
                <span class="chart-corner bottom-right" />
                <div ref="chartElement" class="chart-surface" />
            </div>
        </div>
    </section>
</template>

<script setup>
import * as echarts from 'echarts/core';
import { BarChart, PieChart } from 'echarts/charts';
import {
    GridComponent,
    LegendComponent,
    TitleComponent,
    TooltipComponent,
} from 'echarts/components';
import { CanvasRenderer } from 'echarts/renderers';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

echarts.use([
    BarChart,
    CanvasRenderer,
    GridComponent,
    LegendComponent,
    PieChart,
    TitleComponent,
    TooltipComponent,
]);

const props = defineProps({
    option: {
        type: Object,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        required: true,
    },
    indexLabel: {
        type: String,
        default: 'SEC 01',
    },
    metaTone: {
        type: String,
        default: '',
    },
});

const chartElement = ref(null);
let chart = null;
let resizeObserver = null;

function renderChart() {
    if (!chartElement.value) {
        return;
    }

    if (!chart) {
        chart = echarts.init(chartElement.value);
    }

    chart.setOption(props.option, true);
}

onMounted(() => {
    renderChart();
    resizeObserver = new ResizeObserver(() => chart?.resize());
    resizeObserver.observe(chartElement.value);
});

watch(() => props.option, renderChart, { deep: true });

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    chart?.dispose();
});
</script>

<template>
    <section class="dashboard-panel">
        <div class="panel-header">
            <div>
                <h2>{{ title }}</h2>
                <p>{{ subtitle }}</p>
            </div>
            <slot name="meta" />
        </div>
        <div ref="chartElement" class="chart-surface" />
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
});

const chartElement = ref(null);
let chart = null;
let resizeObserver = null;

function renderChart() {
    if (!chartElement.value) {
        return;
    }

    if (!chart) {
        chart = echarts.init(chartElement.value, 'amopoint');
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

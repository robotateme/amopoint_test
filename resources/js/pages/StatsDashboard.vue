<template>
    <main class="stats-shell">
        <header class="stats-hero">
            <div>
                <p class="eyebrow">Live analytics</p>
                <h1>Статистика посещений</h1>
                <p class="hero-copy">
                    Уникальные визиты, города и активность за последние {{ hours }} ч.
                </p>
            </div>
            <div class="hero-metrics" aria-label="Visit summary">
                <div>
                    <span>{{ totalVisits }}</span>
                    <small>уникальных</small>
                </div>
                <div>
                    <span>{{ topCity }}</span>
                    <small>топ город</small>
                </div>
                <div>
                    <span>{{ peakHour }}</span>
                    <small>пик</small>
                </div>
            </div>
        </header>

        <section class="stat-strip" aria-label="Key metrics">
            <article>
                <span>Города</span>
                <strong>{{ cityRows.length }}</strong>
            </article>
            <article>
                <span>Часов с визитами</span>
                <strong>{{ hourRows.length }}</strong>
            </article>
            <article>
                <span>Среднее в час</span>
                <strong>{{ averageByHour }}</strong>
            </article>
        </section>

        <section class="charts-grid">
            <EChartPanel
                title="Посещения по часам"
                subtitle="Горизонтальная диаграмма уникальных визитов"
                :option="hourChartOption"
            >
                <template #meta>
                    <span class="panel-badge">{{ hours }}h</span>
                </template>
            </EChartPanel>

            <EChartPanel
                title="Города"
                subtitle="Распределение уникальных посетителей"
                :option="cityChartOption"
            >
                <template #meta>
                    <span class="panel-badge accent">{{ cityRows.length }}</span>
                </template>
            </EChartPanel>
        </section>
    </main>
</template>

<script setup>
import { computed } from 'vue';
import EChartPanel from '../components/EChartPanel.vue';

const payload = window.__VISIT_STATS__ || { stats: { hours: [], cities: [] }, hours: 24 };
const hours = payload.hours || 24;
const hourRows = payload.stats?.hours || [];
const cityRows = payload.stats?.cities || [];

const palette = ['#2563eb', '#0f766e', '#dc2626', '#ca8a04', '#7c3aed', '#0891b2', '#db2777'];

const totalVisits = computed(() => cityRows.reduce((sum, row) => sum + Number(row.visits || 0), 0));

const topCity = computed(() => cityRows[0]?.city || 'Нет данных');

const peakHour = computed(() => {
    const row = [...hourRows].sort((left, right) => Number(right.visits) - Number(left.visits))[0];

    return row ? row.hour.slice(11, 16) : 'Нет данных';
});

const averageByHour = computed(() => {
    if (hourRows.length === 0) {
        return 0;
    }

    return Math.round(totalVisits.value / hourRows.length);
});

const hourChartOption = computed(() => ({
    color: ['#2563eb'],
    tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'shadow' },
        backgroundColor: '#111827',
        borderWidth: 0,
        textStyle: { color: '#f8fafc' },
    },
    grid: {
        left: 110,
        right: 24,
        top: 18,
        bottom: 24,
    },
    xAxis: {
        type: 'value',
        minInterval: 1,
        axisLine: { show: false },
        splitLine: { lineStyle: { color: '#dbe4ef' } },
    },
    yAxis: {
        type: 'category',
        data: hourRows.map((row) => row.hour),
        axisLine: { show: false },
        axisTick: { show: false },
    },
    series: [{
        name: 'Уникальные посещения',
        type: 'bar',
        data: hourRows.map((row) => row.visits),
        barWidth: 18,
        itemStyle: {
            borderRadius: [0, 8, 8, 0],
            color: {
                type: 'linear',
                x: 0,
                y: 0,
                x2: 1,
                y2: 0,
                colorStops: [
                    { offset: 0, color: '#0f766e' },
                    { offset: 1, color: '#2563eb' },
                ],
            },
        },
    }],
}));

const cityChartOption = computed(() => ({
    color: palette,
    tooltip: {
        trigger: 'item',
        backgroundColor: '#111827',
        borderWidth: 0,
        textStyle: { color: '#f8fafc' },
    },
    legend: {
        bottom: 0,
        left: 'center',
        icon: 'circle',
    },
    series: [{
        name: 'Город',
        type: 'pie',
        radius: ['46%', '72%'],
        center: ['50%', '45%'],
        avoidLabelOverlap: true,
        itemStyle: {
            borderColor: '#ffffff',
            borderWidth: 3,
            borderRadius: 6,
        },
        label: {
            color: '#334155',
            formatter: '{b}: {c}',
        },
        data: cityRows.map((row) => ({
            name: row.city,
            value: row.visits,
        })),
    }],
}));
</script>

<template>
    <main class="stats-shell">
        <section class="stats-topline" aria-label="System status">
            <div class="topline-block">sector koprulu / visit telemetry</div>
            <div class="topline-block">window {{ hours }}h / uplink stable</div>
        </section>

        <header class="stats-hero">
            <div class="hero-copy-block">
                <p class="eyebrow">Command relay // brood telemetry</p>
                <h1>Статистика посещений</h1>
                <p class="hero-copy">
                    Тактическая сводка по уникальным визитам, активным городам и часовым пикам
                    за последние {{ hours }} часов.
                </p>

                <div class="hero-diagnostics" aria-label="Diagnostic status">
                    <article class="diagnostic-cell">
                        <strong>{{ totalVisits }}</strong>
                        <span>signal traces</span>
                    </article>
                    <article class="diagnostic-cell">
                        <strong>{{ peakHour }}</strong>
                        <span>peak vector</span>
                    </article>
                    <article class="diagnostic-cell">
                        <strong>{{ topCity }}</strong>
                        <span>dominant sector</span>
                    </article>
                </div>
            </div>
            <div class="hero-side">
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
                <div class="hero-radar" aria-hidden="true" />
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
                index-label="SEC 01"
                :option="hourChartOption"
            >
                <template #meta>
                    <span class="panel-badge">{{ hours }}h</span>
                </template>
            </EChartPanel>

            <EChartPanel
                title="Города"
                subtitle="Распределение уникальных посетителей"
                index-label="SEC 02"
                meta-tone="warning"
                :option="cityChartOption"
            >
                <template #meta>
                    <span class="panel-badge accent">{{ cityRows.length }}</span>
                </template>
            </EChartPanel>
        </section>

        <section class="sectors-grid" aria-label="Additional telemetry">
            <article class="sector-card">
                <span class="sector-label">telemetry window</span>
                <strong class="sector-value">{{ hours }} часов</strong>
            </article>
            <article class="sector-card">
                <span class="sector-label">coverage sectors</span>
                <strong class="sector-value">{{ cityRows.length }} городов</strong>
            </article>
            <article class="sector-card">
                <span class="sector-label">uplink density</span>
                <strong class="sector-value">{{ averageByHour }} / час</strong>
            </article>
        </section>
    </main>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { io } from 'socket.io-client';
import EChartPanel from '../components/EChartPanel.vue';

const payload = window.__VISIT_STATS__ || { stats: { total: 0, hours: [], cities: [] }, hours: 24 };
const hours = payload.hours || 24;
const total = ref(Number(payload.stats?.total || 0));
const hourRows = ref(payload.stats?.hours || []);
const cityRows = ref(payload.stats?.cities || []);
let refreshTimer = null;
let statsSocket = null;

const palette = ['#6ce5cf', '#ffd166', '#78f0d6', '#3e90ff', '#ff8f70', '#8f7dff', '#5ae6ff'];

const totalVisits = computed(() => total.value);

const topCity = computed(() => cityRows.value[0]?.city || 'Нет данных');

const peakHour = computed(() => {
    const row = [...hourRows.value].sort((left, right) => Number(right.visits) - Number(left.visits))[0];

    return row ? row.hour.slice(11, 16) : 'Нет данных';
});

const averageByHour = computed(() => {
    if (hourRows.value.length === 0) {
        return 0;
    }

    return Math.round(totalVisits.value / hourRows.value.length);
});

async function refreshStats() {
    const response = await fetch(`${window.location.pathname}?hours=${hours}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        return;
    }

    const freshPayload = await response.json();

    total.value = Number(freshPayload.stats?.total || 0);
    hourRows.value = freshPayload.stats?.hours || [];
    cityRows.value = freshPayload.stats?.cities || [];
}

onMounted(() => {
    requestStatsRefresh();
    connectStatsSocket();

    refreshTimer = window.setInterval(() => {
        requestStatsRefresh();
    }, 30000);

    window.addEventListener('focus', requestStatsRefresh);
    window.addEventListener('storage', refreshStatsAfterVisitRecord);
    document.addEventListener('visibilitychange', refreshStatsWhenVisible);
});

onBeforeUnmount(() => {
    window.clearInterval(refreshTimer);
    statsSocket?.disconnect();
    window.removeEventListener('focus', requestStatsRefresh);
    window.removeEventListener('storage', refreshStatsAfterVisitRecord);
    document.removeEventListener('visibilitychange', refreshStatsWhenVisible);
});

function requestStatsRefresh() {
    refreshStats().catch(() => {});
}

function connectStatsSocket() {
    const socketConfig = window.__SOCKET_IO__ || {};

    if (socketConfig.enabled !== true) {
        return;
    }

    statsSocket = io(socketConfig.url || undefined, {
        path: socketConfig.path || '/socket.io',
        transports: ['websocket', 'polling'],
    });

    statsSocket.on('visit-statistics:changed', requestStatsRefresh);
}

function refreshStatsWhenVisible() {
    if (document.visibilityState === 'visible') {
        requestStatsRefresh();
    }
}

function refreshStatsAfterVisitRecord(event) {
    if (event.key === 'amopoint_visit_recorded_at') {
        requestStatsRefresh();
    }
}

const hourChartOption = computed(() => ({
    color: ['#6ce5cf'],
    tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'shadow' },
        backgroundColor: 'rgba(4, 14, 22, .96)',
        borderColor: 'rgba(108, 229, 207, .24)',
        borderWidth: 1,
        textStyle: { color: '#ecfffb' },
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
        axisLabel: { color: '#7c9f9c' },
        splitLine: { lineStyle: { color: 'rgba(108, 229, 207, .12)' } },
    },
    yAxis: {
        type: 'category',
        data: hourRows.value.map((row) => row.hour),
        axisLine: { show: false },
        axisTick: { show: false },
        axisLabel: { color: '#cbe1dd' },
    },
    series: [{
        name: 'Уникальные посещения',
        type: 'bar',
        data: hourRows.value.map((row) => row.visits),
        barWidth: 18,
        itemStyle: {
            borderRadius: [0, 4, 4, 0],
            borderColor: 'rgba(199, 255, 245, .42)',
            borderWidth: 1,
            color: {
                type: 'linear',
                x: 0,
                y: 0,
                x2: 1,
                y2: 0,
                colorStops: [
                    { offset: 0, color: '#168c7a' },
                    { offset: 0.55, color: '#6ce5cf' },
                    { offset: 1, color: '#ffd166' },
                ],
            },
        },
    }],
}));

const cityChartOption = computed(() => ({
    color: palette,
    tooltip: {
        trigger: 'item',
        backgroundColor: 'rgba(4, 14, 22, .96)',
        borderColor: 'rgba(108, 229, 207, .24)',
        borderWidth: 1,
        textStyle: { color: '#ecfffb' },
    },
    legend: {
        bottom: 0,
        left: 'center',
        icon: 'circle',
        textStyle: {
            color: '#8eb0ad',
        },
    },
    series: [{
        name: 'Город',
        type: 'pie',
        radius: ['46%', '72%'],
        center: ['50%', '45%'],
        avoidLabelOverlap: true,
        itemStyle: {
            borderColor: '#041119',
            borderWidth: 3,
            borderRadius: 4,
        },
        label: {
            color: '#d6fff7',
            formatter: '{b}: {c}',
        },
        data: cityRows.value.map((row) => ({
            name: row.city,
            value: row.visits,
        })),
    }],
}));
</script>

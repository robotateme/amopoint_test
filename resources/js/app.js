import { createApp } from 'vue';

const statsApp = document.getElementById('stats-app');

if (statsApp) {
    import('./pages/StatsDashboard.vue').then(({ default: StatsDashboard }) => {
        createApp(StatsDashboard).mount(statsApp);
    });
}

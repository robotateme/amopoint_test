import { browser } from 'k6/browser';
import { check, fail } from 'k6';

export const options = {
    scenarios: {
        ui: {
            executor: 'shared-iterations',
            vus: 1,
            iterations: 1,
            options: {
                browser: {
                    type: 'chromium',
                },
            },
        },
    },
    thresholds: {
        checks: ['rate==1.0'],
        browser_web_vital_lcp: ['p(95)<2500'],
    },
};

const BASE_URL = (__ENV.BASE_URL || 'http://127.0.0.1').replace(/\/$/, '');
const STATS_LOGIN = __ENV.STATS_LOGIN || 'admin';
const STATS_PASSWORD = __ENV.STATS_PASSWORD || 'secret';

export default async function () {
    const page = await browser.newPage();

    try {
        await login(page);
        await assertDashboard(page, 'initial dashboard');

        const initialTotal = await metricTotal(page);
        await recordVisitFromBrowser(page);
        await page.goto(`${BASE_URL}/stats`, { waitUntil: 'networkidle' });
        await assertDashboard(page, 'refreshed dashboard');

        const refreshedTotal = await metricTotal(page);

        check({ initialTotal, refreshedTotal }, {
            'browser sees stats total after synthetic visit': ({ initialTotal, refreshedTotal }) => refreshedTotal >= initialTotal + 1,
        });
    } catch (error) {
        fail(`Browser stats scenario failed: ${error.message}`);
    } finally {
        await page.close();
    }
}

async function login(page) {
    await page.goto(`${BASE_URL}/stats/login`, { waitUntil: 'networkidle' });

    check(await page.locator('h1').textContent(), {
        'browser login page has expected title': (text) => text.includes('Центр статистики'),
    });

    await page.locator('input[name="login"]').fill(STATS_LOGIN);
    await page.locator('input[name="password"]').fill(STATS_PASSWORD);

    await page.locator('button[type="submit"]').click();
    await page.locator('main.stats-shell').waitFor({ state: 'visible' });
}

async function assertDashboard(page, label) {
    await page.locator('main.stats-shell').waitFor({ state: 'visible' });
    await page.locator('.chart-surface canvas').waitFor({ state: 'visible' });

    const title = await page.locator('h1').textContent();
    const chartsText = await page.locator('.charts-grid').textContent();

    check({ title, chartsText }, {
        [`${label} shows stats title`]: ({ title }) => title.includes('Статистика посещений'),
        [`${label} shows hourly chart label`]: ({ chartsText }) => chartsText.includes('Посещения по часам'),
        [`${label} shows city chart label`]: ({ chartsText }) => chartsText.includes('Города'),
    });
}

async function metricTotal(page) {
    const rawValue = await page.locator('.diagnostic-cell:first-child strong').textContent();
    const total = Number(String(rawValue).trim());

    if (!Number.isFinite(total)) {
        fail(`Stats total is not numeric: ${rawValue}`);
    }

    return total;
}

async function recordVisitFromBrowser(page) {
    const fingerprint = `k6-browser-${Date.now()}-${Math.random().toString(16).slice(2)}`;
    const result = await page.evaluate(async ({ baseUrl, fingerprint }) => {
        const response = await fetch(`${baseUrl}/api/visits`, {
            method: 'POST',
            mode: 'cors',
            credentials: 'omit',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                fingerprint,
                device: 'desktop',
                page_url: `${baseUrl}/k6/browser/${fingerprint}`,
                referrer: `${baseUrl}/stats`,
            }),
        });

        return {
            ok: response.ok,
            status: response.status,
        };
    }, { baseUrl: BASE_URL, fingerprint });

    check(result, {
        'browser synthetic visit returns 201': (response) => response.ok === true && response.status === 201,
    });
}

import { browser } from 'k6/browser';
import { check, fail } from 'k6';

const BASE_URL = (__ENV.BASE_URL || 'http://127.0.0.1').replace(/\/$/, '');
const STATS_LOGIN = __ENV.STATS_LOGIN || 'admin';
const STATS_PASSWORD = __ENV.STATS_PASSWORD || 'secret';
const BROWSER_VISITORS = Number(__ENV.BROWSER_VISITORS || 3);

export const options = {
    scenarios: {
        socket_browsers: {
            executor: 'shared-iterations',
            vus: BROWSER_VISITORS,
            iterations: BROWSER_VISITORS,
            options: {
                browser: {
                    type: 'chromium',
                },
            },
        },
    },
    thresholds: {
        checks: ['rate==1.0'],
    },
};

export default async function () {
    const page = await browser.newPage();

    try {
        await login(page);
        await waitForDashboard(page);
        await waitForSocketConnection(page);

        const initialTotal = await metricTotal(page);
        const initialSocketEvents = await socketEventsCount(page);

        await recordUniqueVisitFromBrowser(page);

        const result = await waitForSocketDrivenStats(
            page,
            initialTotal + 1,
            initialSocketEvents + 1,
        );

        check(result, {
            'browser received Socket.IO event after its visit': ({ socketEvents }) => socketEvents >= initialSocketEvents + 1,
            'browser stats total updated after socket event': ({ total }) => total >= initialTotal + 1,
        });
    } catch (error) {
        check(null, {
            [`socket browser scenario completed: ${errorMessage(error)}`]: () => false,
        });
        fail(`Socket browser scenario failed: ${errorMessage(error)}`);
    } finally {
        await page.close();
    }
}

async function login(page) {
    await page.goto(`${BASE_URL}/stats/login`, { waitUntil: 'networkidle' });
    await page.locator('input[name="login"]').fill(STATS_LOGIN);
    await page.locator('input[name="password"]').fill(STATS_PASSWORD);
    await page.locator('button[type="submit"]').click();
    await waitForDashboard(page);
}

async function recordUniqueVisitFromBrowser(page) {
    const fingerprint = `k6-socket-browser-${__VU}-${Date.now()}-${Math.random().toString(16).slice(2)}`;
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
                page_url: `${baseUrl}/k6/socket-browser/${fingerprint}`,
                referrer: `${baseUrl}/stats`,
            }),
        });

        return {
            ok: response.ok,
            status: response.status,
        };
    }, { baseUrl: BASE_URL, fingerprint });

    check(result, {
        'browser recorded unique visit': (response) => response.ok === true && response.status === 201,
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

async function waitForSocketDrivenStats(page, expectedTotal, expectedSocketEvents) {
    for (let attempt = 0; attempt < 20; attempt += 1) {
        const result = {
            socketEvents: await socketEventsCount(page),
            total: await metricTotal(page),
        };

        if (result.socketEvents >= expectedSocketEvents && result.total >= expectedTotal) {
            return result;
        }

        await page.waitForTimeout(1000);
    }

    return {
        socketEvents: await socketEventsCount(page),
        total: await metricTotal(page),
    };
}

async function waitForDashboard(page) {
    await page.locator('[data-k6-stats-probe]').waitFor({ state: 'attached' });
    await page.locator('.diagnostic-cell:first-child strong').waitFor({ state: 'visible' });
}

async function waitForSocketConnection(page) {
    for (let attempt = 0; attempt < 30; attempt += 1) {
        const diagnostics = await socketDiagnostics(page);

        if (diagnostics.enabled !== 'true') {
            fail([
                'Socket.IO is disabled by the application',
                'set SOCKET_IO_ENABLED=true in the tested app environment',
                `status=${diagnostics.status}`,
                `url=${diagnostics.url}`,
                `path=${diagnostics.path}`,
            ].join('; '));
        }

        if (diagnostics.connected === 'true') {
            return;
        }

        await page.waitForTimeout(1000);
    }

    const diagnostics = await socketDiagnostics(page);

    fail([
        'Socket.IO did not connect',
        `enabled=${diagnostics.enabled}`,
        `status=${diagnostics.status}`,
        `url=${diagnostics.url}`,
        `path=${diagnostics.path}`,
        `error=${diagnostics.error || 'none'}`,
    ].join('; '));
}

async function socketEventsCount(page) {
    const rawValue = await page.locator('[data-k6-stats-probe]').textContent();

    return Number(String(rawValue).trim() || 0);
}

async function socketDiagnostics(page) {
    return page.evaluate(() => {
        const probe = document.querySelector('[data-k6-stats-probe]');

        return {
            enabled: probe?.getAttribute('data-socket-enabled') || 'false',
            connected: probe?.getAttribute('data-socket-connected') || 'false',
            events: probe?.getAttribute('data-socket-events') || '0',
            status: probe?.getAttribute('data-socket-status') || 'unknown',
            url: probe?.getAttribute('data-socket-url') || 'unknown',
            path: probe?.getAttribute('data-socket-path') || 'unknown',
            error: probe?.getAttribute('data-socket-error') || '',
        };
    });
}

function errorMessage(error) {
    if (error instanceof Error && error.message) {
        return error.message;
    }

    return String(error);
}

import http from 'k6/http';
import { check, fail, sleep } from 'k6';

export const options = {
    vus: 1,
    iterations: 1,
    thresholds: {
        checks: ['rate==1.0'],
        http_req_failed: ['rate<0.01'],
        http_req_duration: ['p(95)<1000'],
    },
};

const BASE_URL = (__ENV.BASE_URL || 'http://127.0.0.1').replace(/\/$/, '');
const STATS_LOGIN = __ENV.STATS_LOGIN || 'admin';
const STATS_PASSWORD = __ENV.STATS_PASSWORD || 'secret';
const VISIT_COUNT = Number(__ENV.VISIT_COUNT || 3);
const HOURS = Number(__ENV.STATS_HOURS || 1);

export default function () {
    const jar = new http.CookieJar();

    login(jar);

    const baseline = getStats(jar, 'baseline stats');
    const baselineTotal = Number(baseline.stats.total || 0);
    const fingerprints = recordVisits();
    const expectedTotal = baselineTotal + fingerprints.length;
    const refreshed = waitForStatsTotal(jar, expectedTotal);

    check(refreshed, {
        'stats total includes new unique visits': (payload) => Number(payload.stats.total || 0) >= expectedTotal,
        'stats has hourly rows': (payload) => Array.isArray(payload.stats.hours) && payload.stats.hours.length > 0,
        'stats has city rows': (payload) => Array.isArray(payload.stats.cities) && payload.stats.cities.length > 0,
    });
}

function login(jar) {
    const loginPage = http.get(`${BASE_URL}/stats/login`, { jar });

    check(loginPage, {
        'login page returns 200': (response) => response.status === 200,
    });

    const token = csrfToken(loginPage.body);

    if (token === '') {
        fail('CSRF token was not found on /stats/login.');
    }

    const response = http.post(`${BASE_URL}/stats/login`, {
        _token: token,
        login: STATS_LOGIN,
        password: STATS_PASSWORD,
    }, {
        jar,
        redirects: 0,
    });

    check(response, {
        'stats login redirects after success': (result) => result.status === 302,
        'stats login sets jwt cookie': () => jar.cookiesForURL(BASE_URL).stats_token?.length > 0,
    });
}

function csrfToken(html) {
    const match = String(html).match(/name="_token"\s+value="([^"]+)"/);

    return match ? match[1] : '';
}

function getStats(jar, label) {
    const response = http.get(`${BASE_URL}/stats?hours=${HOURS}`, {
        jar,
        headers: {
            Accept: 'application/json',
        },
    });

    check(response, {
        [`${label} returns 200`]: (result) => result.status === 200,
        [`${label} has stats payload`]: (result) => {
            const payload = parseJson(result);

            return payload !== null
                && typeof payload.stats === 'object'
                && typeof payload.stats.total !== 'undefined'
                && Array.isArray(payload.stats.hours)
                && Array.isArray(payload.stats.cities);
        },
    });

    const payload = parseJson(response);

    if (payload === null) {
        fail(`${label} response is not valid JSON.`);
    }

    return payload;
}

function parseJson(response) {
    try {
        return response.json();
    } catch {
        return null;
    }
}

function recordVisits() {
    const fingerprints = [];
    const runId = `${Date.now()}-${Math.random().toString(16).slice(2)}`;

    for (let index = 0; index < VISIT_COUNT; index += 1) {
        const fingerprint = `k6-stats-${runId}-${index}`;
        const response = http.post(`${BASE_URL}/api/visits`, JSON.stringify({
            fingerprint,
            device: index % 2 === 0 ? 'desktop' : 'mobile',
            page_url: `${BASE_URL}/k6/stats/${runId}/${index}`,
            referrer: `${BASE_URL}/k6`,
        }), {
            headers: {
                'Content-Type': 'application/json',
            },
        });

        check(response, {
            'visit endpoint returns 201': (result) => result.status === 201,
        });

        fingerprints.push(fingerprint);
    }

    return fingerprints;
}

function waitForStatsTotal(jar, expectedTotal) {
    let payload = null;

    for (let attempt = 0; attempt < 10; attempt += 1) {
        payload = getStats(jar, `refreshed stats attempt ${attempt + 1}`);

        if (Number(payload.stats.total || 0) >= expectedTotal) {
            return payload;
        }

        sleep(1);
    }

    return payload;
}

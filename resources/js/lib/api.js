/**
 * Schlanker JSON-Aufruf mit Laravel-CSRF-Schutz (XSRF-TOKEN-Cookie).
 * Wirft bei Fehlern ein Error-Objekt mit status und message (deutsche Meldung, falls vorhanden).
 */
export async function sendJson(method, url, data) {
    const token = document.cookie
        .split('; ')
        .find((c) => c.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(token ? { 'X-XSRF-TOKEN': decodeURIComponent(token) } : {}),
        },
        body: JSON.stringify(data),
    });

    const body = await response.json().catch(() => ({}));

    if (!response.ok) {
        const firstError = body.errors ? Object.values(body.errors).flat()[0] : null;
        const error = new Error(firstError ?? body.message ?? `HTTP ${response.status}`);
        error.status = response.status;
        throw error;
    }

    return body;
}

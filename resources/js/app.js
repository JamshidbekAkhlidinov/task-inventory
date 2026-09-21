/**
 * Small fetch wrapper + alert helper shared by every inventory page.
 * The API routes live under /api and carry no CSRF protection (they
 * sit in the `api` middleware group), so a plain fetch is enough.
 */
async function apiRequest(url, { method = 'GET', body = null } = {}) {
    const response = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            ...(body ? { 'Content-Type': 'application/json' } : {}),
        },
        body: body ? JSON.stringify(body) : null,
    });

    const contentType = response.headers.get('content-type') || '';
    const data = contentType.includes('application/json') ? await response.json() : null;

    if (!response.ok) {
        const error = new Error((data && data.message) || `Request failed (${response.status})`);
        error.status = response.status;
        error.errors = data && data.errors;
        throw error;
    }

    return data;
}

function showAlert(message, type = 'success') {
    const el = document.getElementById('app-alert');

    if (!el) {
        return;
    }

    const palettes = {
        success: 'border-green-300 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-300',
        error: 'border-red-300 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-300',
    };

    el.className = `mb-6 rounded-md border px-4 py-3 text-sm ${palettes[type] ?? palettes.success}`;
    el.textContent = message;
    el.classList.remove('hidden');
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function formErrorMessage(error) {
    if (error.errors) {
        return Object.values(error.errors).flat().join(' ');
    }

    return error.message;
}

function formatMoney(value) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(Number(value ?? 0));
}

window.Inventory = { apiRequest, showAlert, formErrorMessage, formatMoney };

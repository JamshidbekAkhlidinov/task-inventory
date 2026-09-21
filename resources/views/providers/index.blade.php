@extends('layouts.app')

@section('title', 'Providers')
@section('subtitle', 'Suppliers you purchase stock from.')

@section('content')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <form id="provider-form" class="space-y-4 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 lg:col-span-1">
            <h2 id="form-title" class="text-sm font-semibold">New Provider</h2>

            <div>
                <label for="name" class="mb-1 block text-sm font-medium">Name</label>
                <input type="text" id="name" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
            </div>

            <div>
                <label for="phone" class="mb-1 block text-sm font-medium">Phone (optional)</label>
                <input type="text" id="phone" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email (optional)</label>
                <input type="email" id="email" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
            </div>

            <div>
                <label for="address" class="mb-1 block text-sm font-medium">Address (optional)</label>
                <textarea id="address" rows="2" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800"></textarea>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" id="is_active" checked class="rounded border-gray-300 dark:border-gray-700">
                Active
            </label>

            <div class="flex gap-2">
                <button type="submit" id="submit-button" class="flex-1 rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
                    Create Provider
                </button>
                <button type="button" id="cancel-edit" class="hidden rounded-md border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800">
                    Cancel
                </button>
            </div>
        </form>

        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 lg:col-span-2">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody id="rows">
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-400">Loading&hellip;</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        const { apiRequest, showAlert, formErrorMessage } = window.Inventory;

        let providers = [];
        let editingId = null;

        function renderRows() {
            const rows = document.getElementById('rows');

            rows.innerHTML = providers.length
                ? providers.map((provider) => `
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="px-4 py-2 font-medium">${provider.name}</td>
                        <td class="px-4 py-2">${provider.phone ?? '—'}</td>
                        <td class="px-4 py-2">${provider.email ?? '—'}</td>
                        <td class="px-4 py-2">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium ${provider.is_active ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'}">
                                ${provider.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button type="button" data-edit="${provider.id}" class="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">Edit</button>
                            <button type="button" data-delete="${provider.id}" class="ml-3 text-xs font-medium text-red-600 hover:underline dark:text-red-400">Delete</button>
                        </td>
                    </tr>
                `).join('')
                : '<tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No providers yet.</td></tr>';

            rows.querySelectorAll('[data-edit]').forEach((button) => {
                button.addEventListener('click', () => startEdit(Number(button.dataset.edit)));
            });

            rows.querySelectorAll('[data-delete]').forEach((button) => {
                button.addEventListener('click', () => deleteProvider(Number(button.dataset.delete)));
            });
        }

        async function loadProviders() {
            try {
                providers = await apiRequest('/api/providers');
                renderRows();
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        }

        function startEdit(id) {
            const provider = providers.find((p) => p.id === id);
            if (!provider) return;

            editingId = id;
            document.getElementById('name').value = provider.name;
            document.getElementById('phone').value = provider.phone ?? '';
            document.getElementById('email').value = provider.email ?? '';
            document.getElementById('address').value = provider.address ?? '';
            document.getElementById('is_active').checked = provider.is_active;
            document.getElementById('form-title').textContent = `Edit Provider #${id}`;
            document.getElementById('submit-button').textContent = 'Update Provider';
            document.getElementById('cancel-edit').classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        async function deleteProvider(id) {
            if (!confirm('Delete this provider? This can be undone in the database if needed.')) {
                return;
            }

            try {
                await apiRequest(`/api/providers/${id}`, { method: 'DELETE' });
                showAlert('Provider deleted.');

                if (editingId === id) {
                    resetForm(document.getElementById('provider-form'));
                }

                await loadProviders();
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        }

        function resetForm(form) {
            editingId = null;
            form.reset();
            document.getElementById('is_active').checked = true;
            document.getElementById('form-title').textContent = 'New Provider';
            document.getElementById('submit-button').textContent = 'Create Provider';
            document.getElementById('cancel-edit').classList.add('hidden');
        }

        document.getElementById('cancel-edit').addEventListener('click', () => {
            resetForm(document.getElementById('provider-form'));
        });

        document.getElementById('provider-form').addEventListener('submit', async (event) => {
            event.preventDefault();

            const payload = {
                name: document.getElementById('name').value,
                phone: document.getElementById('phone').value || null,
                email: document.getElementById('email').value || null,
                address: document.getElementById('address').value || null,
                is_active: document.getElementById('is_active').checked,
            };

            try {
                if (editingId) {
                    await apiRequest(`/api/providers/${editingId}`, { method: 'PATCH', body: payload });
                    showAlert('Provider updated.');
                } else {
                    await apiRequest('/api/providers', { method: 'POST', body: payload });
                    showAlert('Provider created.');
                }

                resetForm(event.target);
                await loadProviders();
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        });

        loadProviders();
    </script>
@endpush

@extends('layouts.app')

@section('title', 'Storages')
@section('subtitle', 'Warehouses and shops that hold stock.')

@section('content')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <form id="storage-form" class="space-y-4 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 lg:col-span-1">
            <h2 id="form-title" class="text-sm font-semibold">New Storage</h2>

            <div>
                <label for="name" class="mb-1 block text-sm font-medium">Name</label>
                <input type="text" id="name" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
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
                    Create Storage
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
                        <th class="px-4 py-3">Address</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody id="rows">
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-400">Loading&hellip;</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        const { apiRequest, showAlert, formErrorMessage } = window.Inventory;

        let storages = [];
        let editingId = null;

        function renderRows() {
            const rows = document.getElementById('rows');

            rows.innerHTML = storages.length
                ? storages.map((storage) => `
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="px-4 py-2 font-medium">${storage.name}</td>
                        <td class="px-4 py-2">${storage.address ?? '—'}</td>
                        <td class="px-4 py-2">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium ${storage.is_active ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'}">
                                ${storage.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button type="button" data-edit="${storage.id}" class="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">Edit</button>
                            <button type="button" data-delete="${storage.id}" class="ml-3 text-xs font-medium text-red-600 hover:underline dark:text-red-400">Delete</button>
                        </td>
                    </tr>
                `).join('')
                : '<tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No storages yet.</td></tr>';

            rows.querySelectorAll('[data-edit]').forEach((button) => {
                button.addEventListener('click', () => startEdit(Number(button.dataset.edit)));
            });

            rows.querySelectorAll('[data-delete]').forEach((button) => {
                button.addEventListener('click', () => deleteStorage(Number(button.dataset.delete)));
            });
        }

        async function loadStorages() {
            try {
                storages = await apiRequest('/api/storages');
                renderRows();
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        }

        function startEdit(id) {
            const storage = storages.find((s) => s.id === id);
            if (!storage) return;

            editingId = id;
            document.getElementById('name').value = storage.name;
            document.getElementById('address').value = storage.address ?? '';
            document.getElementById('is_active').checked = storage.is_active;
            document.getElementById('form-title').textContent = `Edit Storage #${id}`;
            document.getElementById('submit-button').textContent = 'Update Storage';
            document.getElementById('cancel-edit').classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        async function deleteStorage(id) {
            if (!confirm('Delete this storage? This can be undone in the database if needed.')) {
                return;
            }

            try {
                await apiRequest(`/api/storages/${id}`, { method: 'DELETE' });
                showAlert('Storage deleted.');

                if (editingId === id) {
                    resetForm(document.getElementById('storage-form'));
                }

                await loadStorages();
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        }

        function resetForm(form) {
            editingId = null;
            form.reset();
            document.getElementById('is_active').checked = true;
            document.getElementById('form-title').textContent = 'New Storage';
            document.getElementById('submit-button').textContent = 'Create Storage';
            document.getElementById('cancel-edit').classList.add('hidden');
        }

        document.getElementById('cancel-edit').addEventListener('click', () => {
            resetForm(document.getElementById('storage-form'));
        });

        document.getElementById('storage-form').addEventListener('submit', async (event) => {
            event.preventDefault();

            const payload = {
                name: document.getElementById('name').value,
                address: document.getElementById('address').value || null,
                is_active: document.getElementById('is_active').checked,
            };

            try {
                if (editingId) {
                    await apiRequest(`/api/storages/${editingId}`, { method: 'PATCH', body: payload });
                    showAlert('Storage updated.');
                } else {
                    await apiRequest('/api/storages', { method: 'POST', body: payload });
                    showAlert('Storage created.');
                }

                resetForm(event.target);
                await loadStorages();
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        });

        loadStorages();
    </script>
@endpush

@extends('layouts.app')

@section('title', 'Categories')
@section('subtitle', 'Organize products under a provider, with optional sub-categories.')

@section('content')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <form id="category-form" class="space-y-4 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 lg:col-span-1">
            <h2 id="form-title" class="text-sm font-semibold">New Category</h2>

            <div>
                <label for="provider_id" class="mb-1 block text-sm font-medium">Provider</label>
                <select id="provider_id" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                    <option value="">Select provider&hellip;</option>
                    @foreach ($providers as $provider)
                        <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="parent_id" class="mb-1 block text-sm font-medium">Parent category (optional)</label>
                <select id="parent_id" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                    <option value="">None</option>
                </select>
            </div>

            <div>
                <label for="name" class="mb-1 block text-sm font-medium">Name</label>
                <input type="text" id="name" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
            </div>

            <div class="flex gap-2">
                <button type="submit" id="submit-button" class="flex-1 rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
                    Create Category
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
                        <th class="px-4 py-3">Provider</th>
                        <th class="px-4 py-3">Parent</th>
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

        let categories = [];
        let editingId = null;

        function renderParentOptions() {
            const select = document.getElementById('parent_id');
            const current = select.value;

            select.innerHTML = '<option value="">None</option>' + categories
                .filter((category) => category.id !== editingId)
                .map((category) => `<option value="${category.id}">${category.name}</option>`)
                .join('');

            select.value = current;
        }

        function renderRows() {
            const rows = document.getElementById('rows');

            rows.innerHTML = categories.length
                ? categories.map((category) => `
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="px-4 py-2 font-medium">${category.name}</td>
                        <td class="px-4 py-2">${category.provider_name ?? '—'}</td>
                        <td class="px-4 py-2">${category.parent_name ?? '—'}</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button type="button" data-edit="${category.id}" class="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">Edit</button>
                            <button type="button" data-delete="${category.id}" class="ml-3 text-xs font-medium text-red-600 hover:underline dark:text-red-400">Delete</button>
                        </td>
                    </tr>
                `).join('')
                : '<tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No categories yet.</td></tr>';

            rows.querySelectorAll('[data-edit]').forEach((button) => {
                button.addEventListener('click', () => startEdit(Number(button.dataset.edit)));
            });

            rows.querySelectorAll('[data-delete]').forEach((button) => {
                button.addEventListener('click', () => deleteCategory(Number(button.dataset.delete)));
            });
        }

        async function loadCategories() {
            try {
                categories = await apiRequest('/api/categories');
                renderRows();
                renderParentOptions();
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        }

        function startEdit(id) {
            const category = categories.find((c) => c.id === id);
            if (!category) return;

            editingId = id;
            renderParentOptions();
            document.getElementById('provider_id').value = category.provider_id;
            document.getElementById('parent_id').value = category.parent_id ?? '';
            document.getElementById('name').value = category.name;
            document.getElementById('form-title').textContent = `Edit Category #${id}`;
            document.getElementById('submit-button').textContent = 'Update Category';
            document.getElementById('cancel-edit').classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        async function deleteCategory(id) {
            if (!confirm('Delete this category? This can be undone in the database if needed.')) {
                return;
            }

            try {
                await apiRequest(`/api/categories/${id}`, { method: 'DELETE' });
                showAlert('Category deleted.');

                if (editingId === id) {
                    resetForm(document.getElementById('category-form'));
                }

                await loadCategories();
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        }

        function resetForm(form) {
            editingId = null;
            form.reset();
            renderParentOptions();
            document.getElementById('form-title').textContent = 'New Category';
            document.getElementById('submit-button').textContent = 'Create Category';
            document.getElementById('cancel-edit').classList.add('hidden');
        }

        document.getElementById('cancel-edit').addEventListener('click', () => {
            resetForm(document.getElementById('category-form'));
        });

        document.getElementById('category-form').addEventListener('submit', async (event) => {
            event.preventDefault();

            const providerId = document.getElementById('provider_id').value;
            const parentId = document.getElementById('parent_id').value;

            const payload = {
                provider_id: Number(providerId),
                parent_id: parentId ? Number(parentId) : null,
                name: document.getElementById('name').value,
            };

            try {
                if (editingId) {
                    await apiRequest(`/api/categories/${editingId}`, { method: 'PATCH', body: payload });
                    showAlert('Category updated.');
                } else {
                    await apiRequest('/api/categories', { method: 'POST', body: payload });
                    showAlert('Category created.');
                }

                resetForm(event.target);
                await loadCategories();
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        });

        loadCategories();
    </script>
@endpush

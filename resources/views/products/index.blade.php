@extends('layouts.app')

@section('title', 'Products')
@section('subtitle', 'Products belong to a category and carry the sale price used for new orders.')

@section('content')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <form id="product-form" class="space-y-4 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 lg:col-span-1">
            <h2 id="form-title" class="text-sm font-semibold">New Product</h2>

            <div>
                <label for="category_id" class="mb-1 block text-sm font-medium">Category</label>
                <select id="category_id" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                    <option value="">Select category&hellip;</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="name" class="mb-1 block text-sm font-medium">Name</label>
                <input type="text" id="name" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
            </div>

            <div>
                <label for="sale_price" class="mb-1 block text-sm font-medium">Sale price</label>
                <input type="number" id="sale_price" min="0" step="0.01" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" id="is_active" checked class="rounded border-gray-300 dark:border-gray-700">
                Active
            </label>

            <div class="flex gap-2">
                <button type="submit" id="submit-button" class="flex-1 rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
                    Create Product
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
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3 text-right">Sale Price</th>
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
        const { apiRequest, showAlert, formErrorMessage, formatMoney } = window.Inventory;

        let products = [];
        let editingId = null;

        function renderRows() {
            const rows = document.getElementById('rows');

            rows.innerHTML = products.length
                ? products.map((product) => `
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="px-4 py-2 font-medium">${product.name}</td>
                        <td class="px-4 py-2">${product.category_name ?? '—'}</td>
                        <td class="px-4 py-2 text-right">${formatMoney(product.sale_price)}</td>
                        <td class="px-4 py-2">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium ${product.is_active ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'}">
                                ${product.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button type="button" data-edit="${product.id}" class="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">Edit</button>
                            <button type="button" data-delete="${product.id}" class="ml-3 text-xs font-medium text-red-600 hover:underline dark:text-red-400">Delete</button>
                        </td>
                    </tr>
                `).join('')
                : '<tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No products yet.</td></tr>';

            rows.querySelectorAll('[data-edit]').forEach((button) => {
                button.addEventListener('click', () => startEdit(Number(button.dataset.edit)));
            });

            rows.querySelectorAll('[data-delete]').forEach((button) => {
                button.addEventListener('click', () => deleteProduct(Number(button.dataset.delete)));
            });
        }

        async function loadProducts() {
            try {
                products = await apiRequest('/api/products');
                renderRows();
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        }

        function startEdit(id) {
            const product = products.find((p) => p.id === id);
            if (!product) return;

            editingId = id;
            document.getElementById('category_id').value = product.category_id;
            document.getElementById('name').value = product.name;
            document.getElementById('sale_price').value = product.sale_price;
            document.getElementById('is_active').checked = product.is_active;
            document.getElementById('form-title').textContent = `Edit Product #${id}`;
            document.getElementById('submit-button').textContent = 'Update Product';
            document.getElementById('cancel-edit').classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        async function deleteProduct(id) {
            if (!confirm('Delete this product? This can be undone in the database if needed.')) {
                return;
            }

            try {
                await apiRequest(`/api/products/${id}`, { method: 'DELETE' });
                showAlert('Product deleted.');

                if (editingId === id) {
                    resetForm(document.getElementById('product-form'));
                }

                await loadProducts();
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        }

        function resetForm(form) {
            editingId = null;
            form.reset();
            document.getElementById('is_active').checked = true;
            document.getElementById('form-title').textContent = 'New Product';
            document.getElementById('submit-button').textContent = 'Create Product';
            document.getElementById('cancel-edit').classList.add('hidden');
        }

        document.getElementById('cancel-edit').addEventListener('click', () => {
            resetForm(document.getElementById('product-form'));
        });

        document.getElementById('product-form').addEventListener('submit', async (event) => {
            event.preventDefault();

            const payload = {
                category_id: Number(document.getElementById('category_id').value),
                name: document.getElementById('name').value,
                sale_price: Number(document.getElementById('sale_price').value),
                is_active: document.getElementById('is_active').checked,
            };

            try {
                if (editingId) {
                    await apiRequest(`/api/products/${editingId}`, { method: 'PATCH', body: payload });
                    showAlert('Product updated.');
                } else {
                    await apiRequest('/api/products', { method: 'POST', body: payload });
                    showAlert('Product created.');
                }

                resetForm(event.target);
                await loadProducts();
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        });

        loadProducts();
    </script>
@endpush

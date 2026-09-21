@extends('layouts.app')

@section('title', 'Clients')
@section('subtitle', 'Customers you sell products to.')

@section('content')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <form id="client-form" class="space-y-4 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 lg:col-span-1">
            <h2 id="form-title" class="text-sm font-semibold">New Client</h2>

            <div>
                <label for="name" class="mb-1 block text-sm font-medium">Name</label>
                <input type="text" id="name" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
            </div>

            <div class="flex gap-2">
                <button type="submit" id="submit-button" class="flex-1 rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
                    Create Client
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
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody id="rows">
                    <tr>
                        <td colspan="2" class="px-4 py-6 text-center text-gray-400">Loading&hellip;</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div id="detail-panel" class="mt-6 hidden rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-4 flex items-center justify-between">
            <h2 id="detail-title" class="text-base font-semibold"></h2>
            <button type="button" id="close-detail" class="text-xs font-medium text-gray-500 hover:underline dark:text-gray-400">Close</button>
        </div>

        <div id="detail-body"></div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        const { apiRequest, showAlert, formErrorMessage, formatMoney } = window.Inventory;

        let clients = [];
        let editingId = null;

        function renderRows() {
            const rows = document.getElementById('rows');

            rows.innerHTML = clients.length
                ? clients.map((client) => `
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="px-4 py-2 font-medium">${client.name}</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button type="button" data-view="${client.id}" class="text-xs font-medium text-gray-700 hover:underline dark:text-gray-300">View</button>
                            <button type="button" data-edit="${client.id}" class="ml-3 text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">Edit</button>
                            <button type="button" data-delete="${client.id}" class="ml-3 text-xs font-medium text-red-600 hover:underline dark:text-red-400">Delete</button>
                        </td>
                    </tr>
                `).join('')
                : '<tr><td colspan="2" class="px-4 py-6 text-center text-gray-400">No clients yet.</td></tr>';

            rows.querySelectorAll('[data-view]').forEach((button) => {
                button.addEventListener('click', () => showDetail(Number(button.dataset.view)));
            });

            rows.querySelectorAll('[data-edit]').forEach((button) => {
                button.addEventListener('click', () => startEdit(Number(button.dataset.edit)));
            });

            rows.querySelectorAll('[data-delete]').forEach((button) => {
                button.addEventListener('click', () => deleteClient(Number(button.dataset.delete)));
            });
        }

        async function loadClients() {
            try {
                clients = await apiRequest('/api/clients');
                renderRows();
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        }

        async function showDetail(id) {
            const client = clients.find((c) => c.id === id);
            if (!client) return;

            const panel = document.getElementById('detail-panel');
            const body = document.getElementById('detail-body');

            document.getElementById('detail-title').textContent = `${client.name} — order history`;
            body.innerHTML = '<p class="text-sm text-gray-400">Loading&hellip;</p>';
            panel.classList.remove('hidden');
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });

            try {
                const { data: orders } = await apiRequest(`/api/clients/${id}/orders`);
                body.innerHTML = renderDetail(orders);
            } catch (error) {
                body.innerHTML = `<p class="text-sm text-red-500">${formErrorMessage(error)}</p>`;
            }
        }

        function renderDetail(orders) {
            if (!orders.length) {
                return '<p class="text-sm text-gray-400">This client has no orders yet.</p>';
            }

            const products = new Map();

            orders.forEach((order) => {
                order.items.forEach((item) => {
                    const row = products.get(item.product_id) ?? {
                        name: item.product_name,
                        ordered: 0,
                        refunded: 0,
                        kept: 0,
                    };

                    row.ordered += item.quantity;
                    row.refunded += item.refunded_quantity;
                    row.kept += item.kept_quantity;
                    products.set(item.product_id, row);
                });
            });

            const summary = `
                <h3 class="mb-2 text-sm font-semibold">Products (all orders combined)</h3>
                <table class="mb-6 w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-gray-800 dark:text-gray-400">
                            <th class="py-2">Product</th>
                            <th class="py-2 text-right">Ordered</th>
                            <th class="py-2 text-right">Refunded</th>
                            <th class="py-2 text-right">Kept</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${[...products.values()].map((row) => `
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2">${row.name}</td>
                                <td class="py-2 text-right">${row.ordered}</td>
                                <td class="py-2 text-right ${row.refunded > 0 ? 'text-red-600 dark:text-red-400' : ''}">${row.refunded}</td>
                                <td class="py-2 text-right font-medium">${row.kept}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;

            const history = `
                <h3 class="mb-2 text-sm font-semibold">Orders</h3>
                <div class="space-y-4">
                    ${orders.map((order) => `
                        <div class="rounded-md border border-gray-200 p-3 dark:border-gray-800">
                            <div class="mb-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                <span>Order #${order.id} &middot; ${order.ordered_at ? order.ordered_at.slice(0, 10) : ''} &middot; ${order.status}</span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">${formatMoney(order.total_amount)}</span>
                            </div>
                            <table class="w-full text-left text-sm">
                                <tbody>
                                    ${order.items.map((item) => `
                                        <tr class="border-t border-gray-100 dark:border-gray-800">
                                            <td class="py-1.5">${item.product_name}</td>
                                            <td class="py-1.5 text-right text-gray-500 dark:text-gray-400">${item.quantity} &times; ${formatMoney(item.unit_price)}</td>
                                            <td class="py-1.5 text-right ${item.refunded_quantity > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400'}">
                                                ${item.refunded_quantity > 0 ? `${item.refunded_quantity} refunded` : '—'}
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `).join('')}
                </div>
            `;

            return summary + history;
        }

        document.getElementById('close-detail').addEventListener('click', () => {
            document.getElementById('detail-panel').classList.add('hidden');
        });

        function startEdit(id) {
            const client = clients.find((c) => c.id === id);
            if (!client) return;

            editingId = id;
            document.getElementById('name').value = client.name;
            document.getElementById('form-title').textContent = `Edit Client #${id}`;
            document.getElementById('submit-button').textContent = 'Update Client';
            document.getElementById('cancel-edit').classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function resetForm(form) {
            editingId = null;
            form.reset();
            document.getElementById('form-title').textContent = 'New Client';
            document.getElementById('submit-button').textContent = 'Create Client';
            document.getElementById('cancel-edit').classList.add('hidden');
        }

        async function deleteClient(id) {
            if (!confirm('Delete this client? This can be undone in the database if needed.')) {
                return;
            }

            try {
                await apiRequest(`/api/clients/${id}`, { method: 'DELETE' });
                showAlert('Client deleted.');

                if (editingId === id) {
                    resetForm(document.getElementById('client-form'));
                }

                await loadClients();
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        }

        document.getElementById('cancel-edit').addEventListener('click', () => {
            resetForm(document.getElementById('client-form'));
        });

        document.getElementById('client-form').addEventListener('submit', async (event) => {
            event.preventDefault();

            const payload = {
                name: document.getElementById('name').value,
            };

            try {
                if (editingId) {
                    await apiRequest(`/api/clients/${editingId}`, { method: 'PATCH', body: payload });
                    showAlert('Client updated.');
                } else {
                    await apiRequest('/api/clients', { method: 'POST', body: payload });
                    showAlert('Client created.');
                }

                resetForm(event.target);
                await loadClients();
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        });

        loadClients();
    </script>
@endpush

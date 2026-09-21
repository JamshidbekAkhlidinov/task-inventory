@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Current or historical stock across all storages, and a quick profit snapshot.')

@section('content')
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <section class="lg:col-span-2 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="mb-4 text-base font-semibold">Storage Stock</h2>

            <form id="stock-form" class="mb-5 flex flex-wrap items-end gap-3">
                <div>
                    <label for="storage_id" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Storage</label>
                    <select id="storage_id" name="storage_id" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                        <option value="">All storages</option>
                        @foreach ($storages as $storage)
                            <option value="{{ $storage->id }}">{{ $storage->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="date" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">As of date (optional)</label>
                    <input type="date" id="date" name="date" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                </div>

                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
                    Load
                </button>
            </form>

            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        <th class="py-2">Storage</th>
                        <th class="py-2">Product</th>
                        <th class="py-2 text-right">Quantity</th>
                    </tr>
                </thead>
                <tbody id="stock-rows">
                    <tr>
                        <td colspan="3" class="py-6 text-center text-gray-400">Loading&hellip;</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold">Profit by Batch</h2>
                <a href="{{ route('batches.profit') }}" class="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">View all</a>
            </div>

            <ul id="profit-list" class="space-y-3 text-sm">
                <li class="text-gray-400">Loading&hellip;</li>
            </ul>
        </section>
    </div>
@endsection

@push('scripts')
    <script type="module">
        const { apiRequest, showAlert, formErrorMessage, formatMoney } = window.Inventory;

        async function loadStock() {
            const select = document.getElementById('storage_id');
            const storageId = select.value;
            const storageName = select.options[select.selectedIndex]?.text ?? '';
            const date = document.getElementById('date').value;
            const rows = document.getElementById('stock-rows');

            const params = new URLSearchParams();
            if (storageId) {
                params.set('storage_id', storageId);
            }
            if (date) {
                params.set('date', date);
            }

            try {
                const data = await apiRequest(`/api/storage/stock?${params.toString()}`);
                const items = Array.isArray(data) ? data : data.stock;

                rows.innerHTML = items.length
                    ? items.map((item) => `
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 text-gray-500 dark:text-gray-400">${item.storage_name ?? storageName}</td>
                            <td class="py-2">${item.product_name ?? `#${item.product_id}`}</td>
                            <td class="py-2 text-right">${item.quantity}</td>
                        </tr>
                    `).join('')
                    : '<tr><td colspan="3" class="py-6 text-center text-gray-400">No stock found.</td></tr>';
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        }

        document.getElementById('stock-form').addEventListener('submit', (event) => {
            event.preventDefault();
            loadStock();
        });

        document.getElementById('storage_id').addEventListener('change', loadStock);

        loadStock();

        (async () => {
            const list = document.getElementById('profit-list');

            try {
                const { data } = await apiRequest('/api/batches/profit');

                list.innerHTML = data.length
                    ? data.slice(0, 6).map((row) => `
                        <li class="flex items-center justify-between border-b border-gray-100 pb-2 dark:border-gray-800">
                            <span>Batch #${row.batch_id}</span>
                            <span class="font-medium ${row.profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">
                                ${formatMoney(row.profit)}
                            </span>
                        </li>
                    `).join('')
                    : '<li class="text-gray-400">No sales yet.</li>';
            } catch (error) {
                list.innerHTML = `<li class="text-red-500">${formErrorMessage(error)}</li>`;
            }
        })();
    </script>
@endpush

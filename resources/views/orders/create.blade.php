@extends('layouts.app')

@section('title', 'New Order')
@section('subtitle', 'Pick a client, a storage and quantities — FIFO batch allocation and cost are resolved automatically.')

@section('content')
    <form id="order-form" class="max-w-2xl space-y-6 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="client_id" class="mb-1 block text-sm font-medium">Client</label>
                <select id="client_id" name="client_id" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                    <option value="">Select client&hellip;</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="storage_id" class="mb-1 block text-sm font-medium">Storage</label>
                <select id="storage_id" name="storage_id" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                    <option value="">Select storage&hellip;</option>
                    @foreach ($storages as $storage)
                        <option value="{{ $storage->id }}">{{ $storage->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <h2 class="mb-2 text-sm font-semibold">Available products</h2>
            <div id="products" class="space-y-2 text-sm text-gray-400">Select a storage first.</div>
        </div>

        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
            Create Order
        </button>
    </form>
@endsection

@push('scripts')
    <script type="module">
        const { apiRequest, showAlert, formErrorMessage, formatMoney } = window.Inventory;

        document.getElementById('storage_id').addEventListener('change', async (event) => {
            const container = document.getElementById('products');
            const storageId = event.target.value;

            if (!storageId) {
                container.innerHTML = '<p class="text-gray-400">Select a storage first.</p>';
                return;
            }

            container.innerHTML = '<p class="text-gray-400">Loading&hellip;</p>';

            try {
                const products = await apiRequest(`/api/products/available?storage_id=${storageId}`);

                container.innerHTML = products.length
                    ? products.map((product) => `
                        <div class="flex items-center justify-between gap-3 rounded-md border border-gray-200 px-3 py-2 dark:border-gray-800">
                            <div>
                                <p class="font-medium">${product.name}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    ${formatMoney(product.sale_price)} &middot; Available: ${product.available_quantity}
                                </p>
                            </div>
                            <input
                                type="number" min="0" max="${product.available_quantity}" step="1" value="0"
                                data-product-id="${product.id}"
                                class="product-quantity w-24 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800"
                            >
                        </div>
                    `).join('')
                    : '<p class="text-gray-400">No stock available in this storage.</p>';
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        });

        document.getElementById('order-form').addEventListener('submit', async (event) => {
            event.preventDefault();

            const clientId = document.getElementById('client_id').value;
            const storageId = document.getElementById('storage_id').value;

            const products = [...document.querySelectorAll('.product-quantity')]
                .map((input) => ({ id: Number(input.dataset.productId), qty: Number(input.value) }))
                .filter((product) => product.qty > 0);

            if (!clientId || !storageId || products.length === 0) {
                showAlert('Pick a client, a storage and at least one quantity.', 'error');
                return;
            }

            try {
                const order = await apiRequest('/api/orders', {
                    method: 'POST',
                    body: { client_id: Number(clientId), storage_id: Number(storageId), products },
                });

                showAlert(`Order #${order.id} created — total ${window.Inventory.formatMoney(order.total_amount)}.`);
                event.target.reset();
                document.getElementById('products').innerHTML = '<p class="text-gray-400">Select a storage first.</p>';
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        });
    </script>
@endpush

@extends('layouts.app')

@section('title', 'New Purchase')
@section('subtitle', 'Create a purchase batch. Stock and a purchase movement are created automatically.')

@section('content')
    <form id="purchase-form" class="max-w-3xl space-y-6 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="provider_id" class="mb-1 block text-sm font-medium">Provider</label>
                <select id="provider_id" name="provider_id" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                    <option value="">Select provider&hellip;</option>
                    @foreach ($providers as $provider)
                        <option value="{{ $provider->id }}">{{ $provider->name }}</option>
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

            <div>
                <label for="reference" class="mb-1 block text-sm font-medium">Reference (optional)</label>
                <input type="text" id="reference" name="reference" placeholder="PO-0001" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
            </div>

            <div>
                <label for="purchased_at" class="mb-1 block text-sm font-medium">Purchased at (optional)</label>
                <input type="datetime-local" id="purchased_at" name="purchased_at" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
            </div>
        </div>

        <div>
            <div class="mb-2 flex items-center justify-between">
                <h2 class="text-sm font-semibold">Items</h2>
                <button type="button" id="add-item" class="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">+ Add item</button>
            </div>

            <div id="items" class="space-y-2"></div>
        </div>

        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
            Create Purchase
        </button>
    </form>
@endsection

@push('scripts')
    <script type="module">
        const products = @json($products);
        const { apiRequest, showAlert, formErrorMessage } = window.Inventory;

        function productOptions(selected = '') {
            return '<option value="">Product&hellip;</option>' + products.map((product) =>
                `<option value="${product.id}" ${String(product.id) === String(selected) ? 'selected' : ''}>${product.name}</option>`
            ).join('');
        }

        function addItemRow() {
            const row = document.createElement('div');
            row.className = 'grid grid-cols-12 gap-2';
            row.innerHTML = `
                <select class="item-product col-span-6 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800" required>
                    ${productOptions()}
                </select>
                <input type="number" min="1" step="1" placeholder="Qty" class="item-quantity col-span-3 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800" required>
                <input type="number" min="0" step="0.01" placeholder="Unit cost" class="item-cost col-span-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800" required>
                <button type="button" class="remove-item col-span-1 rounded-md border border-gray-300 text-sm text-gray-500 hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800">&times;</button>
            `;

            row.querySelector('.remove-item').addEventListener('click', () => row.remove());
            document.getElementById('items').appendChild(row);
        }

        document.getElementById('add-item').addEventListener('click', addItemRow);
        addItemRow();

        document.getElementById('purchase-form').addEventListener('submit', async (event) => {
            event.preventDefault();

            const items = [...document.querySelectorAll('#items > div')].map((row) => ({
                product_id: Number(row.querySelector('.item-product').value),
                quantity: Number(row.querySelector('.item-quantity').value),
                unit_cost: Number(row.querySelector('.item-cost').value),
            }));

            const purchasedAt = document.getElementById('purchased_at').value;

            try {
                const batch = await apiRequest('/api/purchases', {
                    method: 'POST',
                    body: {
                        provider_id: Number(document.getElementById('provider_id').value),
                        storage_id: Number(document.getElementById('storage_id').value),
                        reference: document.getElementById('reference').value || null,
                        purchased_at: purchasedAt || null,
                        items,
                    },
                });

                showAlert(`Batch #${batch.id} created with ${batch.items.length} item(s).`);
                event.target.reset();
                document.getElementById('items').innerHTML = '';
                addItemRow();
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        });
    </script>
@endpush

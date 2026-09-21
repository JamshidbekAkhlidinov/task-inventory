@extends('layouts.app')

@section('title', 'Client Refund')
@section('subtitle', 'Return sold units — each unit is restored to the exact batch it originally came from.')

@section('content')
    <form id="refund-form" class="max-w-2xl space-y-6 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        <div>
            <label for="order_id" class="mb-1 block text-sm font-medium">Order</label>
            <select id="order_id" name="order_id" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                <option value="">Select order&hellip;</option>
                @foreach ($orders as $order)
                    <option value="{{ $order->id }}">
                        #{{ $order->id }} &middot; {{ $order->client?->name ?? 'Unknown client' }} &middot; {{ $order->ordered_at->format('Y-m-d') }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <h2 class="mb-2 text-sm font-semibold">Refundable items</h2>
            <div id="items" class="space-y-2 text-sm text-gray-400">Select an order first.</div>
        </div>

        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
            Refund from Client
        </button>
    </form>
@endsection

@push('scripts')
    <script type="module">
        const orders = @json($orders);
        const { apiRequest, showAlert, formErrorMessage } = window.Inventory;

        document.getElementById('order_id').addEventListener('change', (event) => {
            const order = orders.find((o) => String(o.id) === event.target.value);
            const container = document.getElementById('items');

            const refundableItems = order ? order.items.filter((item) => item.refundable_quantity > 0) : [];

            if (!refundableItems.length) {
                container.innerHTML = '<p class="text-gray-400">Nothing left to refund on this order.</p>';
                return;
            }

            container.innerHTML = refundableItems.map((item) => `
                <div class="flex items-center justify-between gap-3 rounded-md border border-gray-200 px-3 py-2 dark:border-gray-800">
                    <div>
                        <p class="font-medium">${item.product?.name ?? `Product #${item.product_id}`}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Refundable: ${item.refundable_quantity} of ${item.quantity}</p>
                    </div>
                    <input
                        type="number" min="0" max="${item.refundable_quantity}" step="1" value="0"
                        data-order-item-id="${item.id}"
                        class="item-quantity w-24 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800"
                    >
                </div>
            `).join('');
        });

        document.getElementById('refund-form').addEventListener('submit', async (event) => {
            event.preventDefault();

            const orderId = document.getElementById('order_id').value;

            const items = [...document.querySelectorAll('.item-quantity')]
                .map((input) => ({ order_item_id: Number(input.dataset.orderItemId), quantity: Number(input.value) }))
                .filter((item) => item.quantity > 0);

            if (!orderId || items.length === 0) {
                showAlert('Pick an order and at least one quantity to refund.', 'error');
                return;
            }

            try {
                const refund = await apiRequest('/api/client-refunds', {
                    method: 'POST',
                    body: { order_id: Number(orderId), items },
                });

                showAlert(`Refund #${refund.id} created for ${refund.items.length} item(s).`);
                event.target.reset();
                document.getElementById('items').innerHTML = '<p class="text-gray-400">Select an order first.</p>';
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        });
    </script>
@endpush

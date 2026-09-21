@extends('layouts.app')

@section('title', 'Provider Refund')
@section('subtitle', 'Return purchased units from a single batch back to its provider.')

@section('content')
    <form id="refund-form" class="max-w-2xl space-y-6 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        <div>
            <label for="batch_id" class="mb-1 block text-sm font-medium">Batch</label>
            <select id="batch_id" name="batch_id" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                <option value="">Select batch&hellip;</option>
                @foreach ($batches as $batch)
                    <option value="{{ $batch->id }}">
                        #{{ $batch->id }} &middot; {{ $batch->provider?->name ?? 'Unknown provider' }} &middot; {{ $batch->storage?->name ?? 'Unknown storage' }} &middot; {{ $batch->purchased_at->format('Y-m-d') }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <h2 class="mb-2 text-sm font-semibold">Items available in this batch</h2>
            <div id="items" class="space-y-2 text-sm text-gray-400">Select a batch first.</div>
        </div>

        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
            Refund to Provider
        </button>
    </form>
@endsection

@push('scripts')
    <script type="module">
        const batches = @json($batches);
        const { apiRequest, showAlert, formErrorMessage } = window.Inventory;

        document.getElementById('batch_id').addEventListener('change', (event) => {
            const batch = batches.find((b) => String(b.id) === event.target.value);
            const container = document.getElementById('items');

            if (!batch || !batch.items.length) {
                container.innerHTML = '<p class="text-gray-400">No refundable items in this batch.</p>';
                return;
            }

            container.innerHTML = batch.items.map((item) => `
                <div class="flex items-center justify-between gap-3 rounded-md border border-gray-200 px-3 py-2 dark:border-gray-800">
                    <div>
                        <p class="font-medium">${item.product?.name ?? `Product #${item.product_id}`}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Available: ${item.available_quantity}</p>
                    </div>
                    <input
                        type="number" min="0" max="${item.available_quantity}" step="1" value="0"
                        data-product-id="${item.product_id}"
                        class="item-quantity w-24 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800"
                    >
                </div>
            `).join('');
        });

        document.getElementById('refund-form').addEventListener('submit', async (event) => {
            event.preventDefault();

            const batchId = document.getElementById('batch_id').value;

            const items = [...document.querySelectorAll('.item-quantity')]
                .map((input) => ({ product_id: Number(input.dataset.productId), quantity: Number(input.value) }))
                .filter((item) => item.quantity > 0);

            if (!batchId || items.length === 0) {
                showAlert('Pick a batch and at least one quantity to refund.', 'error');
                return;
            }

            try {
                const refund = await apiRequest('/api/provider-refunds', {
                    method: 'POST',
                    body: { batch_id: Number(batchId), items },
                });

                showAlert(`Refund #${refund.id} created for ${refund.items.length} item(s).`);
                event.target.reset();
                document.getElementById('items').innerHTML = '<p class="text-gray-400">Select a batch first.</p>';
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        });
    </script>
@endpush

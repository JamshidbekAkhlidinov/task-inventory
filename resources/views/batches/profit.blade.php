@extends('layouts.app')

@section('title', 'Batch Profit')
@section('subtitle', 'Profit per batch, computed from actual FIFO allocations and net of client refunds.')

@section('content')
    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    <th class="px-4 py-3">Batch</th>
                    <th class="px-4 py-3 text-right">Qty Sold</th>
                    <th class="px-4 py-3 text-right">Revenue</th>
                    <th class="px-4 py-3 text-right">Cost</th>
                    <th class="px-4 py-3 text-right">Profit</th>
                </tr>
            </thead>
            <tbody id="rows">
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-400">Loading&hellip;</td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script type="module">
        const { apiRequest, showAlert, formErrorMessage, formatMoney } = window.Inventory;

        (async () => {
            const rows = document.getElementById('rows');

            try {
                const { data } = await apiRequest('/api/batches/profit');

                rows.innerHTML = data.length
                    ? data.map((row) => `
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-4 py-2">#${row.batch_id}</td>
                            <td class="px-4 py-2 text-right">${row.quantity_sold}</td>
                            <td class="px-4 py-2 text-right">${formatMoney(row.revenue)}</td>
                            <td class="px-4 py-2 text-right">${formatMoney(row.cost)}</td>
                            <td class="px-4 py-2 text-right font-medium ${row.profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">
                                ${formatMoney(row.profit)}
                            </td>
                        </tr>
                    `).join('')
                    : '<tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No sales yet.</td></tr>';
            } catch (error) {
                showAlert(formErrorMessage(error), 'error');
            }
        })();
    </script>
@endpush

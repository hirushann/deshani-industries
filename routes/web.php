<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/test-email', function () {
    $message = (new \Illuminate\Notifications\Messages\MailMessage)
        ->subject('Welcome to ' . config('app.name', 'Deshani Industries'))
        ->greeting('Hello, John Doe!')
        ->line('This is a test of the modernized email template. We hope you like the new look and feel.')
        ->line('Your account has been created successfully. You can now log in and start using the system.')
        ->action('View Dashboard', url('/admin'))
        ->line('Thank you for using our application!');
        
    return $message->render();
});

Route::get('/test-invoice-print', function () {
    $invoice = \App\Models\Invoice::latest()->first();
    if (!$invoice) {
        return 'No invoices found in the database. Please create one in the admin panel first.';
    }
    return redirect()->route('invoices.print', $invoice);
});

Route::get('/admin/invoices/{invoice}/print', [App\Http\Controllers\InvoiceController::class, 'print'])->name('invoices.print');
Route::get('/price-list', [\App\Http\Controllers\PriceListController::class, 'index'])->name('price-list');
Route::get('/admin/payments/{payment}/print', [App\Http\Controllers\PaymentController::class, 'print'])->name('payments.print');
Route::get('/admin/commissions/{commission}/print', [App\Http\Controllers\CommissionController::class, 'print'])->name('commissions.print');

Route::get('/admin/reports/outstanding-payments/print', function (\Illuminate\Http\Request $request) {
    $query = App\Models\Invoice::query()
        ->where('balance_due', '>', 0)
        ->latest('issued_date');

    if ($from = $request->input('from')) {
        $query->whereDate('issued_date', '>=', $from);
    }
    if ($until = $request->input('until')) {
        $query->whereDate('issued_date', '<=', $until);
    }
    if ($place = $request->input('place')) {
        $query->whereHas('order.customer', function ($q) use ($place) {
            $q->where('address', 'like', "%{$place}%");
        });
    }

    $invoices = $query->with(['order.customer', 'payments'])->get();

    return view('reports.print-outstanding-payments', compact('invoices'));
})->name('reports.outstanding-payments.print');

Route::get('/admin/reports/income/print', function (\Illuminate\Http\Request $request) {
    $query = App\Models\Payment::query()->latest('transaction_date');

    if ($from = $request->input('from')) {
        $query->whereDate('transaction_date', '>=', $from);
    }
    if ($until = $request->input('until')) {
        $query->whereDate('transaction_date', '<=', $until);
    }

    $payments = $query->with(['invoice.order.customer'])->get();

    return view('reports.print-income-report', compact('payments'));
})->name('reports.income.print');

Route::get('/admin/reports/expenses/print', function (\Illuminate\Http\Request $request) {
    $query = App\Models\Expense::query()->latest('date');

    if ($from = $request->input('from')) {
        $query->whereDate('date', '>=', $from);
    }
    if ($until = $request->input('until')) {
        $query->whereDate('date', '<=', $until);
    }
    if ($categoryId = $request->input('category')) {
        $query->where('expense_category_id', $categoryId);
    }

    $expenses = $query->with(['category', 'user'])->get();

    return view('reports.print-expense-report', compact('expenses'));
})->name('reports.expenses.print');

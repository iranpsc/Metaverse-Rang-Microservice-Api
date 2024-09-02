<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Resources\LatestTransactionResource;

class TransactionController extends Controller
{
    /**
     * Display a listing of the transactions.
     *
     * @return TransactionResource
     */
    public function index()
    {
        $query = Transaction::whereBelongsTo(auth()->user());

        $query = $this->applyFilters($query);

        $transactions = $query->latest()->simplePaginate();

        return TransactionResource::collection($transactions);
    }

    /**
     * Apply filters to the query.
     *
     * @param $query
     * @return mixed
     */
    private function applyFilters($query)
    {
        $search = request()->query('search');
        if ($search) {
            $query->where('id', $search);
        }

        $startDateTime = request()->query('start_date_time');
        if ($startDateTime) {
            $query->whereDate('created_at', '>=', jalali_date_time_to_carbon($startDateTime));
        }

        $endDateTime = request()->query('end_date_time');
        if ($endDateTime) {
            $query->whereDate('created_at', '<=', jalali_date_time_to_carbon($endDateTime));
        }

        $status = request()->query('status');
        if ($status) {
            $query->whereIn('status', $status);
        }

        $action = request()->query('action');
        if ($action) {
            $query->where('action', $action);
        }

        $asset = request()->query('asset');
        if ($asset) {
            $query->where('asset', $asset);
        }

        $type = request()->query('type');
        if ($type) {
            $type = ucfirst('App\Models\\' . $type);
            $query->where('payable_type', $type);
        }

        return $query;
    }

    /**
     * Get the latest transaction of the current user.
     *
     * @param Request $request
     * @return LatestTransactionResource
     */
    public function latestTransaction(Request $request)
    {
        $user = $request->user()->load(['latestPayment', 'latestTransaction', 'latestOrder']);
        return new LatestTransactionResource($user);
    }
}

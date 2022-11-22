<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'reports' => Auth::user()->reports
        ]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param ReportRequest $request
     * @return JsonResponse
     */
    public function store(ReportRequest $request): JsonResponse
    {
        $report = $request->user()->reports()->create([
            'subject' => $request->subject,
            'title'   => $request->title,
            'content' => $request->content,
            'url'     => $request->url
        ]);
        return response()->json([
            'success' => 'گزارش شما ثبت شد و در حال بررسی می باشد',
            'report' => $report,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param Report $report
     * @return JsonResponse
     */
    public function show(Report $report): JsonResponse
    {
        return response()->json([
            'report' => $report,
        ]);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param ReportRequest $request
     * @param Report $report
     * @return JsonResponse
     */
    public function update(ReportRequest $request, Report $report): JsonResponse
    {
        $report->update([
            'subject' => $request->subject,
            'title'   => $request->title,
            'content' => $request->content,
            'url'     => $request->url
        ]);
        return response()->json([
            'success' => 'گزارش بروزرسانی شد',
            'report' => $report,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Report $report
     * @return JsonResponse
     */
    public function destroy(Report $report): JsonResponse
    {
        $report->delete();
        return response()->json([
            'success' => 'گزارش حذف شد'
        ]);
    }
}

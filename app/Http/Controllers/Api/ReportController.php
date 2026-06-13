<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ReportOverviewResource;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Display the sales overview report.
     */
    public function __invoke(Request $request, ReportService $reportService): ReportOverviewResource
    {
        return new ReportOverviewResource($reportService->overview($request->only([
            'branch_id',
            'payment_method',
            'date_from',
            'date_to',
        ]), $request->user()));
    }
}

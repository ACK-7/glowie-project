<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseApiController;
use App\Repositories\Contracts\AnalyticsRepositoryInterface;
use App\Services\AnalyticsService;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Analytics Controller for reporting and charts
 * 
 * Provides comprehensive analytics endpoints including:
 * - Analytics data aggregation for charts and reports
 * - Trend analysis and pattern identification algorithms
 * - Export functionality for multiple formats (PDF, Excel, CSV)
 * 
 * Requirements: 1.3, 9.1, 9.2, 9.3, 9.4, 9.5
 */
class AnalyticsController extends BaseApiController
{
    public function __construct(
        private AnalyticsRepositoryInterface $analyticsRepository,
        private AnalyticsService $analyticsService,
        private ExportService $exportService
    ) {}

    /**
     * Get comprehensive analytics dashboard data
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getDashboardAnalytics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'period' => 'sometimes|string|in:7_days,30_days,90_days,current_month,last_month,ytd,custom',
                'start_date' => 'sometimes|date|required_if:period,custom',
                'end_date' => 'sometimes|date|required_if:period,custom|after_or_equal:start_date',
                'metrics' => 'sometimes|array',
                'metrics.*' => 'string|in:revenue,bookings,customers,shipments,quotes,conversion'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $period = $request->get('period', '30_days');
            $dates = $this->getPeriodDates($period, $request->get('start_date'), $request->get('end_date'));
            $metrics = $request->get('metrics', ['revenue', 'bookings', 'customers', 'shipments']);

            $cacheKey = 'analytics_dashboard_' . md5(serialize([$period, $dates, $metrics, auth()->id()]));
            
            $analytics = Cache::remember($cacheKey, 300, function () use ($dates, $metrics) {
                return $this->generateDashboardAnalytics($dates['start'], $dates['end'], $metrics);
            });

            $this->logActivity('analytics_dashboard_viewed', null, null, [
                'period' => $period,
                'metrics' => $metrics
            ]);

            return $this->successResponse($analytics, 'Analytics dashboard data retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve analytics dashboard data', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);
            
            return $this->serverErrorResponse('Failed to retrieve analytics dashboard data', $e);
        }
    }

    /**
     * Get revenue analytics with detailed breakdowns
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getRevenueAnalytics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'period' => 'sometimes|string|in:7_days,30_days,90_days,current_month,last_month,ytd,custom',
                'start_date' => 'sometimes|date|required_if:period,custom',
                'end_date' => 'sometimes|date|required_if:period,custom|after_or_equal:start_date',
                'breakdown' => 'sometimes|string|in:daily,weekly,monthly,route,method,customer_tier',
                'include_forecast' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $period = $request->get('period', '30_days');
            $dates = $this->getPeriodDates($period, $request->get('start_date'), $request->get('end_date'));
            $breakdown = $request->get('breakdown', 'daily');
            $includeForecast = $request->boolean('include_forecast', false);

            $analytics = $this->analyticsRepository->getRevenueAnalytics($dates['start'], $dates['end']);
            
            // Add trend analysis
            $analytics['trends'] = $this->analyticsService->analyzeTrends('revenue', $dates['start'], $dates['end']);
            
            // Add breakdown analysis
            $analytics['breakdown'] = $this->analyticsService->getRevenueBreakdown($breakdown, $dates['start'], $dates['end']);
            
            // Add comparative analysis
            $previousDates = $this->getPreviousPeriodDates($dates['start'], $dates['end']);
            $analytics['comparison'] = $this->analyticsRepository->getComparativeAnalysis(
                $dates['start'], $dates['end'],
                $previousDates['start'], $previousDates['end']
            );

            // Add forecast if requested
            if ($includeForecast) {
                $analytics['forecast'] = $this->analyticsService->generateRevenueForecast($dates['start'], $dates['end']);
            }

            // Add pattern identification
            $analytics['patterns'] = $this->analyticsService->identifyRevenuePatterns($dates['start'], $dates['end']);

            $this->logActivity('revenue_analytics_viewed', null, null, [
                'period' => $period,
                'breakdown' => $breakdown,
                'include_forecast' => $includeForecast
            ]);

            return $this->successResponse($analytics, 'Revenue analytics retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve revenue analytics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);
            
            return $this->serverErrorResponse('Failed to retrieve revenue analytics', $e);
        }
    }

    /**
     * Get booking analytics with performance metrics
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getBookingAnalytics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'period' => 'sometimes|string|in:7_days,30_days,90_days,current_month,last_month,ytd,custom',
                'start_date' => 'sometimes|date|required_if:period,custom',
                'end_date' => 'sometimes|date|required_if:period,custom|after_or_equal:start_date',
                'group_by' => 'sometimes|string|in:status,route,vehicle_type,customer_tier,date'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $period = $request->get('period', '30_days');
            $dates = $this->getPeriodDates($period, $request->get('start_date'), $request->get('end_date'));
            $groupBy = $request->get('group_by', 'status');

            $analytics = $this->analyticsRepository->getBookingAnalytics($dates['start'], $dates['end']);
            
            // Add trend analysis
            $analytics['trends'] = $this->analyticsService->analyzeTrends('bookings', $dates['start'], $dates['end']);
            
            // Add performance metrics
            $analytics['performance'] = $this->analyticsService->getBookingPerformanceMetrics($dates['start'], $dates['end']);
            
            // Add conversion funnel
            $analytics['conversion_funnel'] = $this->analyticsRepository->getConversionFunnelAnalytics($dates['start'], $dates['end']);
            
            // Add route performance
            $analytics['route_performance'] = $this->analyticsRepository->getRoutePerformanceAnalytics();

            // Add pattern identification
            $analytics['patterns'] = $this->analyticsService->identifyBookingPatterns($dates['start'], $dates['end']);

            $this->logActivity('booking_analytics_viewed', null, null, [
                'period' => $period,
                'group_by' => $groupBy
            ]);

            return $this->successResponse($analytics, 'Booking analytics retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve booking analytics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);
            
            return $this->serverErrorResponse('Failed to retrieve booking analytics', $e);
        }
    }

    /**
     * Get customer analytics and insights
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getCustomerAnalytics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'period' => 'sometimes|string|in:7_days,30_days,90_days,current_month,last_month,ytd,custom',
                'start_date' => 'sometimes|date|required_if:period,custom',
                'end_date' => 'sometimes|date|required_if:period,custom|after_or_equal:start_date',
                'segment' => 'sometimes|string|in:all,new,returning,high_value,at_risk'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $period = $request->get('period', '30_days');
            $dates = $this->getPeriodDates($period, $request->get('start_date'), $request->get('end_date'));
            $segment = $request->get('segment', 'all');

            $analytics = $this->analyticsRepository->getCustomerAnalytics($dates['start'], $dates['end']);
            
            // Add customer segmentation
            $analytics['segmentation'] = $this->analyticsService->getCustomerSegmentation($dates['start'], $dates['end']);
            
            // Add lifetime value analysis
            $analytics['ltv_analysis'] = $this->analyticsService->getCustomerLTVAnalysis($dates['start'], $dates['end']);
            
            // Add churn analysis
            $analytics['churn_analysis'] = $this->analyticsService->getCustomerChurnAnalysis($dates['start'], $dates['end']);
            
            // Add acquisition channels
            $analytics['acquisition_channels'] = $this->analyticsService->getAcquisitionChannelAnalysis($dates['start'], $dates['end']);

            // Add behavioral patterns
            $analytics['behavioral_patterns'] = $this->analyticsService->identifyCustomerBehaviorPatterns($dates['start'], $dates['end']);

            $this->logActivity('customer_analytics_viewed', null, null, [
                'period' => $period,
                'segment' => $segment
            ]);

            return $this->successResponse($analytics, 'Customer analytics retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve customer analytics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);
            
            return $this->serverErrorResponse('Failed to retrieve customer analytics', $e);
        }
    }

    /**
     * Get operational analytics and performance metrics
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getOperationalAnalytics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'period' => 'sometimes|string|in:7_days,30_days,90_days,current_month,last_month,ytd,custom',
                'start_date' => 'sometimes|date|required_if:period,custom',
                'end_date' => 'sometimes|date|required_if:period,custom|after_or_equal:start_date',
                'metrics' => 'sometimes|array',
                'metrics.*' => 'string|in:efficiency,quality,capacity,performance'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $period = $request->get('period', '30_days');
            $dates = $this->getPeriodDates($period, $request->get('start_date'), $request->get('end_date'));
            $metrics = $request->get('metrics', ['efficiency', 'quality', 'capacity', 'performance']);

            $analytics = $this->analyticsRepository->getOperationalMetrics();
            
            // Add shipment analytics
            $analytics['shipment_analytics'] = $this->analyticsRepository->getShipmentAnalytics($dates['start'], $dates['end']);
            
            // Add efficiency metrics
            if (in_array('efficiency', $metrics)) {
                $analytics['efficiency_metrics'] = $this->analyticsService->getEfficiencyMetrics($dates['start'], $dates['end']);
            }
            
            // Add quality metrics
            if (in_array('quality', $metrics)) {
                $analytics['quality_metrics'] = $this->analyticsService->getQualityMetrics($dates['start'], $dates['end']);
            }
            
            // Add capacity utilization
            if (in_array('capacity', $metrics)) {
                $analytics['capacity_metrics'] = $this->analyticsService->getCapacityMetrics($dates['start'], $dates['end']);
            }
            
            // Add performance benchmarks
            if (in_array('performance', $metrics)) {
                $analytics['performance_benchmarks'] = $this->analyticsService->getPerformanceBenchmarks($dates['start'], $dates['end']);
            }

            // Add bottleneck identification
            $analytics['bottlenecks'] = $this->analyticsService->identifyBottlenecks($dates['start'], $dates['end']);

            $this->logActivity('operational_analytics_viewed', null, null, [
                'period' => $period,
                'metrics' => $metrics
            ]);

            return $this->successResponse($analytics, 'Operational analytics retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve operational analytics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);
            
            return $this->serverErrorResponse('Failed to retrieve operational analytics', $e);
        }
    }

    /**
     * Get trend analysis for specific metrics
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTrendAnalysis(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'metric' => 'required|string|in:revenue,bookings,customers,quotes,shipments,conversion_rate',
                'period' => 'sometimes|string|in:7_days,30_days,90_days,6_months,1_year',
                'granularity' => 'sometimes|string|in:daily,weekly,monthly',
                'include_forecast' => 'sometimes|boolean',
                'include_seasonality' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $metric = $request->get('metric');
            $period = $request->get('period', '30_days');
            $granularity = $request->get('granularity', 'daily');
            $includeForecast = $request->boolean('include_forecast', false);
            $includeSeasonality = $request->boolean('include_seasonality', false);

            $dates = $this->getPeriodDates($period);
            
            $trendData = $this->analyticsService->analyzeTrends($metric, $dates['start'], $dates['end'], $granularity);
            
            // Add statistical analysis
            $trendData['statistics'] = $this->analyticsService->calculateTrendStatistics($trendData['data']);
            
            // Add pattern recognition
            $trendData['patterns'] = $this->analyticsService->identifyTrendPatterns($trendData['data']);
            
            // Add forecast if requested
            if ($includeForecast) {
                $trendData['forecast'] = $this->analyticsService->generateTrendForecast($metric, $trendData['data']);
            }
            
            // Add seasonality analysis if requested
            if ($includeSeasonality) {
                $trendData['seasonality'] = $this->analyticsService->analyzeSeasonality($metric, $dates['start'], $dates['end']);
            }

            $this->logActivity('trend_analysis_viewed', null, null, [
                'metric' => $metric,
                'period' => $period,
                'granularity' => $granularity
            ]);

            return $this->successResponse($trendData, 'Trend analysis retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve trend analysis', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);
            
            return $this->serverErrorResponse('Failed to retrieve trend analysis', $e);
        }
    }

    /**
     * Export analytics report in specified format
     * 
     * @param Request $request
     * @return Response|JsonResponse
     */
    public function exportReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'report_type' => 'required|string|in:revenue,bookings,customers,operational,comprehensive',
                'format' => 'required|string|in:pdf,excel,csv',
                'period' => 'sometimes|string|in:7_days,30_days,90_days,current_month,last_month,ytd,custom',
                'start_date' => 'sometimes|date|required_if:period,custom',
                'end_date' => 'sometimes|date|required_if:period,custom|after_or_equal:start_date',
                'include_charts' => 'sometimes|boolean',
                'include_raw_data' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $reportType = $request->get('report_type');
            $format = $request->get('format');
            $period = $request->get('period', '30_days');
            $dates = $this->getPeriodDates($period, $request->get('start_date'), $request->get('end_date'));
            $includeCharts = $request->boolean('include_charts', true);
            $includeRawData = $request->boolean('include_raw_data', false);

            // Generate report data
            $reportData = $this->generateReportData($reportType, $dates['start'], $dates['end'], $includeRawData);
            
            // Export report
            $exportResult = $this->exportService->exportAnalyticsReport(
                $reportType,
                $format,
                $reportData,
                [
                    'period' => $period,
                    'start_date' => $dates['start'],
                    'end_date' => $dates['end'],
                    'include_charts' => $includeCharts,
                    'include_raw_data' => $includeRawData,
                    'generated_by' => auth()->user()->name ?? 'System',
                    'generated_at' => now()
                ]
            );

            $this->logActivity('analytics_report_exported', null, null, [
                'report_type' => $reportType,
                'format' => $format,
                'period' => $period,
                'file_size' => $exportResult['file_size'] ?? null
            ]);

            // Return file download response
            return response()->download(
                $exportResult['file_path'],
                $exportResult['filename'],
                $exportResult['headers']
            )->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Failed to export analytics report', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);
            
            return $this->serverErrorResponse('Failed to export analytics report', $e);
        }
    }

    /**
     * Get comparative analysis between periods
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getComparativeAnalysis(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_start' => 'required|date',
                'current_end' => 'required|date|after_or_equal:current_start',
                'previous_start' => 'required|date',
                'previous_end' => 'required|date|after_or_equal:previous_start',
                'metrics' => 'sometimes|array',
                'metrics.*' => 'string|in:revenue,bookings,customers,quotes,shipments,conversion'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $currentStart = Carbon::parse($request->get('current_start'));
            $currentEnd = Carbon::parse($request->get('current_end'));
            $previousStart = Carbon::parse($request->get('previous_start'));
            $previousEnd = Carbon::parse($request->get('previous_end'));
            $metrics = $request->get('metrics', ['revenue', 'bookings', 'customers']);

            $analysis = $this->analyticsRepository->getComparativeAnalysis(
                $currentStart, $currentEnd,
                $previousStart, $previousEnd
            );

            // Add detailed variance analysis
            $analysis['variance_analysis'] = $this->analyticsService->calculateVarianceAnalysis(
                $analysis['current_period'],
                $analysis['previous_period']
            );

            // Add significance testing
            $analysis['significance_tests'] = $this->analyticsService->performSignificanceTests(
                $analysis['current_period'],
                $analysis['previous_period']
            );

            $this->logActivity('comparative_analysis_viewed', null, null, [
                'current_period' => [$currentStart->toDateString(), $currentEnd->toDateString()],
                'previous_period' => [$previousStart->toDateString(), $previousEnd->toDateString()],
                'metrics' => $metrics
            ]);

            return $this->successResponse($analysis, 'Comparative analysis retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve comparative analysis', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);
            
            return $this->serverErrorResponse('Failed to retrieve comparative analysis', $e);
        }
    }

    /**
     * Get predictive analytics and forecasts
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getPredictiveAnalytics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'metric' => 'required|string|in:revenue,bookings,customers,demand',
                'forecast_period' => 'sometimes|integer|min:1|max:365',
                'confidence_level' => 'sometimes|numeric|min:0.5|max:0.99',
                'include_scenarios' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $metric = $request->get('metric');
            $forecastPeriod = $request->get('forecast_period', 30);
            $confidenceLevel = $request->get('confidence_level', 0.95);
            $includeScenarios = $request->boolean('include_scenarios', false);

            $predictions = $this->analyticsService->generatePredictiveAnalytics(
                $metric,
                $forecastPeriod,
                $confidenceLevel
            );

            // Add scenario analysis if requested
            if ($includeScenarios) {
                $predictions['scenarios'] = $this->analyticsService->generateScenarioAnalysis($metric, $forecastPeriod);
            }

            // Add model accuracy metrics
            $predictions['model_accuracy'] = $this->analyticsService->calculateModelAccuracy($metric);

            $this->logActivity('predictive_analytics_viewed', null, null, [
                'metric' => $metric,
                'forecast_period' => $forecastPeriod,
                'confidence_level' => $confidenceLevel
            ]);

            return $this->successResponse($predictions, 'Predictive analytics retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve predictive analytics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);
            
            return $this->serverErrorResponse('Failed to retrieve predictive analytics', $e);
        }
    }

    /**
     * Helper method to get period dates
     */
    private function getPeriodDates(string $period, ?string $startDate = null, ?string $endDate = null): array
    {
        if ($period === 'custom' && $startDate && $endDate) {
            return [
                'start' => Carbon::parse($startDate)->startOfDay(),
                'end' => Carbon::parse($endDate)->endOfDay()
            ];
        }

        return match ($period) {
            '7_days' => [
                'start' => now()->subDays(7)->startOfDay(),
                'end' => now()->endOfDay()
            ],
            '30_days' => [
                'start' => now()->subDays(30)->startOfDay(),
                'end' => now()->endOfDay()
            ],
            '90_days' => [
                'start' => now()->subDays(90)->startOfDay(),
                'end' => now()->endOfDay()
            ],
            'current_month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth()
            ],
            'last_month' => [
                'start' => now()->subMonth()->startOfMonth(),
                'end' => now()->subMonth()->endOfMonth()
            ],
            'ytd' => [
                'start' => now()->startOfYear(),
                'end' => now()->endOfDay()
            ],
            '6_months' => [
                'start' => now()->subMonths(6)->startOfDay(),
                'end' => now()->endOfDay()
            ],
            '1_year' => [
                'start' => now()->subYear()->startOfDay(),
                'end' => now()->endOfDay()
            ],
            default => [
                'start' => now()->subDays(30)->startOfDay(),
                'end' => now()->endOfDay()
            ]
        };
    }

    /**
     * Helper method to get previous period dates for comparison
     */
    private function getPreviousPeriodDates(Carbon $start, Carbon $end): array
    {
        $duration = $start->diffInDays($end);
        
        return [
            'start' => $start->copy()->subDays($duration + 1)->startOfDay(),
            'end' => $start->copy()->subDay()->endOfDay()
        ];
    }

    /**
     * Generate comprehensive dashboard analytics
     */
    private function generateDashboardAnalytics(Carbon $start, Carbon $end, array $metrics): array
    {
        $analytics = [];

        foreach ($metrics as $metric) {
            switch ($metric) {
                case 'revenue':
                    $analytics['revenue'] = $this->analyticsRepository->getRevenueAnalytics($start, $end);
                    break;
                case 'bookings':
                    $analytics['bookings'] = $this->analyticsRepository->getBookingAnalytics($start, $end);
                    break;
                case 'customers':
                    $analytics['customers'] = $this->analyticsRepository->getCustomerAnalytics($start, $end);
                    break;
                case 'shipments':
                    $analytics['shipments'] = $this->analyticsRepository->getShipmentAnalytics($start, $end);
                    break;
            }
        }

        // Add cross-metric insights
        $analytics['insights'] = $this->analyticsService->generateCrossMetricInsights($analytics, $start, $end);

        return $analytics;
    }

    /**
     * Generate report data based on report type
     */
    private function generateReportData(string $reportType, Carbon $start, Carbon $end, bool $includeRawData): array
    {
        $data = match ($reportType) {
            'revenue' => [
                'analytics' => $this->analyticsRepository->getRevenueAnalytics($start, $end),
                'financial_summary' => $this->analyticsRepository->getFinancialSummary($start, $end)
            ],
            'bookings' => [
                'analytics' => $this->analyticsRepository->getBookingAnalytics($start, $end),
                'conversion_funnel' => $this->analyticsRepository->getConversionFunnelAnalytics($start, $end)
            ],
            'customers' => [
                'analytics' => $this->analyticsRepository->getCustomerAnalytics($start, $end),
                'segmentation' => $this->analyticsService->getCustomerSegmentation($start, $end)
            ],
            'operational' => [
                'metrics' => $this->analyticsRepository->getOperationalMetrics(),
                'shipment_analytics' => $this->analyticsRepository->getShipmentAnalytics($start, $end)
            ],
            'comprehensive' => [
                'revenue' => $this->analyticsRepository->getRevenueAnalytics($start, $end),
                'bookings' => $this->analyticsRepository->getBookingAnalytics($start, $end),
                'customers' => $this->analyticsRepository->getCustomerAnalytics($start, $end),
                'operational' => $this->analyticsRepository->getOperationalMetrics()
            ]
        };

        // Add raw data if requested
        if ($includeRawData) {
            $data['raw_data'] = $this->analyticsRepository->getExportData($reportType, [
                'start_date' => $start,
                'end_date' => $end
            ]);
        }

        return $data;
    }
}
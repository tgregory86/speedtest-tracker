<?php

namespace App\Filament\Widgets;

use App\Enums\ResultStatus;
use App\Helpers\TimeZoneHelper;
use App\Models\Result;
use App\Settings\GeneralSettings;
use Filament\Widgets\ChartWidget;

class RecentJitterChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Jitter';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    public ?string $filter = '24h';

    protected function getPollingInterval(): ?string
    {
        return config('speedtest.dashboard_polling');
    }

    protected function getFilters(): ?array
    {
        return [
            '24h' => 'Last 24h',
            'week' => 'Last week',
            'month' => 'Last month',
        ];
    }

    protected function getData(): array
    {
        $settings = new GeneralSettings();

        $results = Result::query()
            ->select(['data', 'created_at'])
            ->where('status', '=', ResultStatus::Completed)
            ->when($this->filter == '24h', function ($query) {
                $query->where('created_at', '>=', now()->subDay());
            })
            ->when($this->filter == 'week', function ($query) {
                $query->where('created_at', '>=', now()->subWeek());
            })
            ->when($this->filter == 'month', function ($query) {
                $query->where('created_at', '>=', now()->subMonth());
            })
            ->latest()
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Download (ms)',
                    'data' => $results->map(fn ($item) => $item->download_jitter ? number_format($item->download_jitter, 2) : 0),
                    'borderColor' => '#0ea5e9',
                    'backgroundColor' => '#0ea5e9',
                    'fill' => false,
                    'cubicInterpolationMode' => 'monotone',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Upload (ms)',
                    'data' => $results->map(fn ($item) => $item->upload_jitter ? number_format($item->upload_jitter, 2) : 0),
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => '#8b5cf6',
                    'fill' => false,
                    'cubicInterpolationMode' => 'monotone',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Ping (ms)',
                    'data' => $results->map(fn ($item) => $item->ping_jitter ? number_format($item->ping_jitter, 2) : 0),
                    'borderColor' => '#10b981',
                    'backgroundColor' => '#10b981',
                    'fill' => false,
                    'cubicInterpolationMode' => 'monotone',
                    'tension' => 0.4,
                ],
            ],
            'labels' => $results->map(fn ($item) => $item->created_at->timezone(TimeZoneHelper::displayTimeZone($settings))->format('M d - G:i')),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class UserStats extends Component
{
    public const METRICS = ['copies', 'votes', 'saves', 'shares'];

    /** @var array<string, mixed> */
    public array $stats;

    public string $metric = 'copies';

    /**
     * @param  array<string, mixed>  $stats
     */
    public function mount(array $stats): void
    {
        $this->stats = $stats;
    }

    public function selectMetric(string $metric): void
    {
        if (in_array($metric, self::METRICS, true)) {
            $this->metric = $metric;
        }
    }

    public function render(): View
    {
        return view('livewire.user-stats', [
            'series' => $this->stats['series'][$this->metric],
            'metricLabel' => $this->stats['kpis'][$this->metric]['label'],
            'unit' => $this->stats['kpis'][$this->metric]['unit'],
        ]);
    }
}

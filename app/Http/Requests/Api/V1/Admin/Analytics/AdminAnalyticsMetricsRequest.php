<?php

namespace App\Http\Requests\Api\V1\Admin\Analytics;

use App\Http\Requests\Api\V1\Admin\Analytics\Concerns\InteractsWithAnalyticsPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdminAnalyticsMetricsRequest extends FormRequest
{
    use InteractsWithAnalyticsPeriod;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->periodRules();
    }
}

<?php

namespace App\Observers;

use App\Models\Rate;

class RateObserver
{
    /**
     * После появления оценки.
     */
    public function created(Rate $rate){
        $rate->comic()->recalculateRating();
    }
    /**
     * После обновления оценки.
     */
    public function updated(Rate $rate): void
    {
        $rate->comic()->recalculateRating();
    }

    /**
     * При удалении оценки.
     */
    public function deleted(Rate $rate): void
    {
        $rate->comic()->recalculateRating();
    }
}

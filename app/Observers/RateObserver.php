<?php

namespace App\Observers;

use App\Models\Rate;

class RateObserver
{
    public function saved(Rate $rate): void
    {
        // Обновляем кэш рейтинга комикса
        $rate->comic->observer->ratingChanged($rate->comic);
    }

    public function deleted(Rate $rate): void
    {
        $rate->comic->observer->ratingChanged($rate->comic);
    }
}

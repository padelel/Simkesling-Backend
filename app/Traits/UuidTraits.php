<?php

namespace App\Traits;

// use Ramsey\Uuid\Exception\;
use Ramsey\Uuid\Uuid;

trait UuidTraits
{
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            try {
                $model->uid = Uuid::uuid4()->toString();
            } catch (\Exception $e) {
                abort(500, $e->getMessage());
            }
        });
    }
}

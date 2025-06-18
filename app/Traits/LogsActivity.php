<?php

namespace App\Traits;

use App\Models\Activity;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->logActivity('created');
        });

        static::updated(function ($model) {
            $model->logActivity('updated');
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted');
        });
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'related');
    }

    public function logActivity($type)
    {
        if (!auth()->check()) {
            return;
        }

        $description = $this->getActivityDescription($type);

        Activity::create([
            'user_id' => auth()->id(),
            'description' => $description,
            'type' => $type,
            'related_id' => $this->id,
            'related_type' => get_class($this)
        ]);
    }

    protected function getActivityDescription($type)
    {
        $modelName = class_basename($this);
        
        switch ($type) {
            case 'created':
                return "Created new {$modelName}";
            case 'updated':
                return "Updated {$modelName}";
            case 'deleted':
                return "Deleted {$modelName}";
            default:
                return "Performed action on {$modelName}";
        }
    }
} 
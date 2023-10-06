<?php
namespace App\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Collection;

class BaseResource extends JsonResource
{
    /**
     * @param array $parameters
     * @return static
     */
    public static function make(...$parameters)
    {
        if (count($parameters) > 0 && empty($parameters[0])) {
            return $parameters[0];
        }
        return parent::make(...$parameters);
    }

    /**
     * @param Carbon $date
     * @return string
     */
    protected function date($date)
    {
        $date = empty($date) ? null : Carbon::parse($date);

        if ($date) {
            return $date->timezone(config('app.date.timezone'))->format(config('app.date.format'));
        } else {
            return $date;
        }
    }

    /**
     * @param Carbon $date
     * @return string
     */
    protected function dateTime($dateTime, $format = null)
    {
        if (empty($dateTime)) {
            return $dateTime;
        }

        return $dateTime->timezone(config('app.date.timezone'))->format($format ?: 'Y-m-d H:i:s');
    }

    /**
     * @param Carbon $date
     * @return string
     */
    protected function dateTimeDisplay($dateTime, $format = null)
    {
        if (empty($dateTime)) {
            return $dateTime;
        }

        return $dateTime->timezone(config('app.date.timezone'))->format($format ?: 'd/m/Y h:i A');
    }

    /**
     * @param mixed $number
     * @param int $places
     * @return string
     */
    protected function decimal($number, $places)
    {
        return $number !== null ? number_format($number, $places, '.', '') : null;
    }

    /**
     * @inheritDoc
     */
    protected function whenLoaded($relationship, $value = null, $default = null)
    {
        if (func_num_args() === 1) {
            return $this->objWhenLoaded($this, $relationship);
        } else if (func_num_args() === 2) {
            return $this->objWhenLoaded($this, $relationship, $value);
        }
        return $this->objWhenLoaded($this, $relationship, $value, $default);
    }

    protected function objWhenLoaded($obj, $relationship, $value = null, $default = null)
    {
        [$isLoaded, $obj] = $this->loadedObject($obj, $relationship);

        if (func_num_args() === 2) {
            return $this->when($isLoaded, $obj);
        } else if (func_num_args() === 3) {
            return $this->when($isLoaded, $value);
        }
        return $this->when($isLoaded, $value, $default);
    }

    protected function loadedObject($obj, $relationship)
    {
        if (empty($obj) || $obj instanceof MissingValue) {
            return [false, null];
        }

        $isLoaded = true;
        if (is_array($relationship)) {
            $initialObj = $obj;
            foreach ($relationship as $item) {
                $obj = $initialObj;
                [$isLoaded, $obj] = $this->loadedObject($obj, $item);
                if (!$isLoaded) {
                    return [$isLoaded, $obj];
                }
            }
            return [$isLoaded, $obj];
        }

        $arrRelationship = explode('.', $relationship);
        foreach ($arrRelationship as $relationshipPart) {
            if ($obj instanceof Collection) {
                $isLoaded = $isLoaded && ($obj->isEmpty() || array_key_exists($relationshipPart, $obj->first()->getAttributes()) || $obj->first()->relationLoaded($relationshipPart));
                if ($isLoaded) {
                    $obj = $obj->pluck($relationshipPart)->filter();
                    if ($obj->first() instanceof Collection) {
                        $obj = $obj->flatten(1);
                    }
                }
            } else {
                $isLoaded = $isLoaded && $obj && (array_key_exists($relationshipPart, $obj->getAttributes()) || $obj->relationLoaded($relationshipPart));
                if ($isLoaded) {
                    $obj = $obj->{$relationshipPart};
                    if ($obj instanceof Collection) {
                        $obj = $obj->filter();
                    }
                }
            }

            if (!$isLoaded) {
                return [$isLoaded, $obj];
            }
        }
        return [$isLoaded, $obj];
    }

    protected function hasLoadedRelationships($obj, ...$relationships)
    {
        foreach ($relationships as $relationship) {
            if (!$this->loadedObject($obj, $relationship)[0]) {
                return false;
            }
        }
        return true;
    }
}

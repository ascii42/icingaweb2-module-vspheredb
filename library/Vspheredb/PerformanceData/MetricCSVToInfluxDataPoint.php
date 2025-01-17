<?php

namespace Icinga\Module\Vspheredb\PerformanceData;

use gipfl\InfluxDb\DataPoint;
use Icinga\Module\Vspheredb\MappedClass\PerfEntityMetricCSV;
use Icinga\Module\Vspheredb\MappedClass\PerfMetricId;
use Icinga\Module\Vspheredb\Util;
use Icinga\Module\Vspheredb\VmwareDataType\ManagedObjectReference;
use function array_combine;
use function count;
use function preg_split;

class MetricCSVToInfluxDataPoint
{
    /**
     * @param PerfEntityMetricCSV $metric
     * @param $countersMap
     * @return \Generator
     */
    public static function map($measurementName, PerfEntityMetricCSV $metric, $countersMap, $tags)
    {
        $object = $metric->entity;
        $dates = static::parseDates($metric);
        $result = [];
        foreach ($metric->value as $series) {
            $key = static::makeKey($object, $series->id);
            $metric = $countersMap[$series->id->counterId];
            foreach (array_combine(
                $dates,
                preg_split('/,/', $series->value)
            ) as $time => $value) {
                $result[$time][$key][$metric] = $value === '' ? null : (int) $value;
            }
        }
        foreach ($result as $time => $results) {
            foreach ($results as $key => $metrics) {
                yield new DataPoint(
                    $measurementName,
                    ['instance' => $key] + $tags[$key],
                    $metrics,
                    $time
                );
            }
        }
    }

    protected static function makeKey(ManagedObjectReference $ref, PerfMetricId $id)
    {
        $ref = $ref->_;
        if (strlen($id->instance)) {
            return "$ref/" . $id->instance;
        }

        return $ref;
    }

    protected static function parseDates(PerfEntityMetricCSV $metric)
    {
        $parts = preg_split('/,/', $metric->sampleInfoCSV);
        $max = count($parts) - 1;
        $dates = [];
        for ($i = 1; $i <= $max; $i += 2) {
            $dates[] = Util::timeStringToUnixTime($parts[$i]);
        }

        return $dates;
    }
}

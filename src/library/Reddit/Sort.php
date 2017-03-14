<?php
final class Reddit_Sort
{
    const LAUNCH_TIME   = 1134028003;

    const WEIGHT_WINDOW = 45000;

    const UP_RANGE = 400;

    const DOWN_RANGE = 100;

    static $confidences = array();

    public static function score($ups, $downs)
    {
        return $ups - $downs;
    }

    public static function hot($ups, $downs, $timestamp)
    {
        $score = static::score($ups, $downs);

        $order = log10(max(abs($score), 1));

        if ($score > 0) {
            $sign = 1;
        } else if ($score < 0) {
            $sign = -1;
        } else {
            $sign = 0;
        }

        $seconds = $timestamp - static::LAUNCH_TIME;

        return round($sign * $order + $seconds / static::WEIGHT_WINDOW, 7);
    }

    public static function controversy($ups, $downs)
    {
        if ($ups <= 0 || $downs <= 0) {
            return 0;
        }

        $magnitude = $ups + $downs;
        $balance = ($ups > $downs) ? ($downs / $ups) : ($ups / $downs);

        return pow($magnitude, $balance);
    }

    public static function _confidence($ups, $downs)
    {
        $n = $ups + $downs;

        if ($n == 0) {
            return 0;
        }

        $z = 1.281551565545; // 80% confidence
        $p = $ups / $n;

        $left = $p + 1 / (2 * $n) * $z * $z;
        $right = $z * sqrt($p * (1 - $p) / $n + $z * $z / (4 * $n * $n));
        $under = 1 + 1 / $n * $z * $z;

        return ($left - $right) / $under;
    }

    public static function ensureConfidences()
    {
        if (!static::$confidences) {
            for ($i = 0; $i < static::UP_RANGE; $i++) {
                for ($j = 0; $j < static::DOWN_RANGE; $j++) {
                    static::$confidences[] = static::_confidence($i, $j);
                }
            }
        }
    }

    public static function confidence($ups, $downs)
    {
        static::ensureConfidences();

        if ($ups + $downs == 0) {
            return 0;
        } else if (($ups < static::UP_RANGE) && ($downs < static::DOWN_RANGE)) {
            return static::$confidences[$downs + $ups * static::DOWN_RANGE];
        } else {
            return static::_confidence($ups, $downs);
        }
    }
}
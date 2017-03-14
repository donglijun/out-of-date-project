<?php
class Mkjogo_Red_Generator
{
    public static function lucky($totalPoints, $number)
    {
        $result = array();

        if ($number == 1) {
            $result[] = $totalPoints;
        } else {
            $min = 1;

            for ($i = 1; $i < $number; $i++) {
                $threshold = ceil(($totalPoints - ($number - $i) * $min) / ($number - $i));
                $a = mt_rand(1, (int) $threshold);
                $totalPoints -= $a;

                $result[] = $a;
            }

            $result[] = $totalPoints;
        }

        return $result;
    }

    public static function common($number, $singlePoints)
    {
        return array_fill(0, $number, $singlePoints);
    }
}
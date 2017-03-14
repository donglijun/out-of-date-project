<?php
final class Helper_Parser_Sphinx
{
    public static function parseIndex($data)
    {
        $result = array();

        if (is_string($data)) {
            $result = array_filter(explode(',', trim($data)));
        }

        return $result;
    }

    public static function parseSelect($data)
    {
        $result = array();

        if (is_string($data)) {
            $selects = array_filter(explode(';', trim($data)));

            foreach ($selects as $select) {
                if (preg_match('/^([^:]+):(.+)$/', $select, $matches)) {
                    $result[$matches[1]] = $matches[2];
                } else {
                    $result[] = $select;
                }
            }
        }

        return $result;
    }

    public static function parseGroupby($data)
    {
        $result = array();

        if (is_string($data)) {
            $result = array_filter(explode(',', trim($data)));
        }

        return $result;
    }

    public static function parseSort($data)
    {
        $result = array();

        if (is_string($data)) {
            $sorts = array_filter(explode(';', trim($data)));

            foreach ($sorts as $sort) {
                if (preg_match('/^([^:]+):(.+)$/', $sort, $matches)) {
                    $result[$matches[1]] = $matches[2];
                } else {
                    $result[$sort] = 'asc';
                }
            }
        }

        return $result;
    }

    public static function parseFilter($data)
    {
        $result = array();

        if (is_string($data)) {
            $filters = array_filter(explode(';', trim($data)));

            foreach ($filters as $filter) {
                if (preg_match('/^([^:]+):(.+)$/', $filter, $matches)) {
                    $attribute  = $matches[1];
                    $values     = array_filter(explode(',', trim($matches[2])));

//                    $result[] = array(
//                        'attribute' => $attribute,
//                        'values'    => $values,
//                    );
                    $result[$attribute] = $values;
                }
            }
        } else if (is_array($data)) {
            foreach ($data as $key => $val) {
                $result[$key] = array_filter(explode(',', trim($val)));
            }
        }

        return $result;
    }

    public static function parseRange($data)
    {
        $result = array();

        if (is_string($data)) {
            $ranges = array_filter(explode(';', trim($data)));

            foreach ($ranges as $filter) {
                if (preg_match('/^([^:]+):(.+)$/', $filter, $matches)) {
                    $attribute = $matches[1];
                    $range = explode(',', trim($matches[2]));
                    if (count($range) < 2) {
                        continue;
                    }
//                    list($min, $max, ) = explode(',', trim($matches[2]));

//                    $result[] = array(
//                        'attribute' => $attribute,
//                        'min'       => $min,
//                        'max'       => $max,
//                    );
                    $result[$attribute] = $range;
                }
            }
        } else if (is_array($data)) {
            foreach ($data as $key => $val) {
                if (($range = explode(',', trim($val))) && (count($range) >= 2)) {
                    $result[$key] = explode(',', trim($val));
                }
            }
        }

        return $result;
    }

    public static function parseFaceted($data)
    {
        $result = array();

        if (is_string($data)) {
            $result = array_filter(explode(',', trim($data)));
        }

        return $result;
    }
}
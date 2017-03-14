<?php
final class Helper_Formatter_Sphinx
{
    public static function formatIndex($data)
    {
        $result = '';

        if (is_string($data)) {
            $result = $data;
        } else if (is_array($data)) {
            $result = implode(',', $data);
        }

        return $result;
    }

    public static function formatSelect($data)
    {
        $result = '';

        if (is_string($data)) {
            $result = $data;
        } else if (is_array($data)) {
            $tmp = array();

            foreach ($data as $key => $val) {
                $tmp[] = is_string($key) ? ($val ? $key . ' AS ' . $val : $key) : $val;
            }

            $result = implode(',', $tmp);
        }

        return $result;
    }

    public static function formatGroupby($data)
    {
        $result = '';

        if (is_string($data)) {
            $result = $data;
        } else if (is_array($data)) {
            $result = implode(',', $data);
        }

        return $result;
    }

    public static function formatSort($data)
    {
        $result = '';

        if (is_string($data)) {
            $result = $data;
        } else if (is_array($data)) {
            $tmp = array();

            foreach ($data as $key => $val) {
                $tmp[] = $key . ' ' . $val;
            }

            $result = implode(',', $tmp);
        }

        return $result;
    }

    public static function formatFilter($data, $exclude = false)
    {
        $result = '';

        if (is_string($data)) {
            $result = $data;
        } else if (is_array($data)) {
            $tmp = array();
            $in = $exclude ? 'NOT IN' : 'IN';
            $equal = $exclude ? '<>' : '=';

            foreach ($data as $key => $val) {
//                $tmp[] = sprintf('%s %s (%s)', $val['attribute'], $in, implode(',', $val['values']));
                if (count($val) === 1) {
                    $tmp[] = sprintf('%s%s%s', $key, $equal, current($val));
                } else {
                    $tmp[] = sprintf('%s %s (%s)', $key, $in, implode(',', $val));
                }
            }

            $result = implode(' AND ', $tmp);
        }

        return $result;
    }

    public static function formatRange($data, $exclude = false)
    {
        $result = '';

        if (is_string($data)) {
            $result = $data;
        } else if (is_array($data)) {
            $tmp = array();
            $between = $exclude ? 'NOT BETWEEN' : 'BETWEEN';

            foreach ($data as $key => $val) {
//                $tmp[] = sprintf('%s %s %s AND %s', $val['attribute'], $between, $val['min'], $val['max']);
                list($min, $max,) = $val;

                if ($min !== '' && $max !== '') {
                    $tmp[] = sprintf('%s %s %s AND %s', $key, $between, floatval($min), floatval($max));
                } else if ($min !== '') {
                    $tmp[] = sprintf('%s%s%s', $key, $exclude ? '<' : '>=', floatval($min));
                } else if ($max !== '') {
                    $tmp[] = sprintf('%s%s%s', $key, $exclude ? '>' : '<=', floatval($max));
                }
            }

            $result = implode(' AND ', $tmp);
        }

        return $result;
    }

    public static function formatMVA($data)
    {
        $result = '';

        if (is_array($data)) {
            foreach ($data as &$val) {
                $val = (int) $val;
            }
        } else if ($data) {
            $data = array(
                (int) $data,
            );
        } else {
            $data = array();
        }

        return sprintf('(%s)', implode(',', array_filter($data)));
    }
}
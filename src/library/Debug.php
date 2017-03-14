<?php
final class Debug
{
    /**
     * Dump a variable for well display in browser
     *
     * @param mixed $data Any variable to dump
     * @param bool $exit True to terminate the script after dump
     */
    public static function dump($data, $exit = false)
    {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';

        !$exit ?: exit();
    }
}
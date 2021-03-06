<?php

namespace Bolt\Helpers;

class String
{
    /**
     * Returns a "safe" version of the given string - basically only US-ASCII and
     * numbers. Needed because filenames and titles and such, can't use all characters.
     *
     * @param  string  $str
     * @param  boolean $strict
     * @param  string  $extrachars
     * @return string
     */
    public static function makeSafe($str, $strict = false, $extrachars = "")
    {
        $str = \URLify::downcode($str);
        $str = str_replace("&amp;", "", $str);

        $delim = '/';
        if ($extrachars != "") {
            $extrachars = preg_quote($extrachars, $delim);
        }
        if ($strict) {
            $str = strtolower(str_replace(" ", "-", $str));
            $regex = "[^a-zA-Z0-9_" . $extrachars . "-]";
        } else {
            $regex = "[^a-zA-Z0-9 _.," . $extrachars . "-]";
        }

        $str = preg_replace($delim . $regex . $delim, '', $str);

        return $str;
    }

    /**
     * Replace the first occurence of a string only. Behaves like str_replace, but
     * replaces _only_ the _first_ occurence.
     *
     * @see http://stackoverflow.com/a/2606638
     *
     * @param  string $search
     * @param  string $replace
     * @param  string $subject
     * @return string
     */
    public static function replaceFirst($search, $replace, $subject)
    {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }

        return $subject;
    }

    /**
     * Add 'soft hyphens' &shy; to a string, so that it won't break layout in HTML when
     * using strings without spaces or dashes.
     *
     * @param  string $str
     * @return string
     */
    public static function shyphenate($str)
    {
        $str = preg_replace("/[a-z0-9_-]/i", "$0&shy;", $str);

        return $str;
    }
}

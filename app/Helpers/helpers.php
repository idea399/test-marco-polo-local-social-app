<?php

use Illuminate\Support\Number;

if (!function_exists('format_number')) {
  function format_number(int $number): string
  {
    if ($number < 1000) {
      return (string) Number::format($number, 0);
    }
    if ($number < 1000000) {
      return Number::format($number / 1000, 2) . 'k';
    }
    return Number::format($number / 1000000, 2) . 'm';
  }
}

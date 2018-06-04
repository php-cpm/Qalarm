<?php
namespace App\Components\Alarm;

Interface CounterInferface 
{
    public function get($inc);
    public function set($clear);
}

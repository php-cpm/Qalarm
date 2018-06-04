<?php
namespace App\Models\Common;

class Mail extends BaseJobObject
{
    public $table = 'mail';

    public $from;
    public $to;
    public $title;
    public $content;
}

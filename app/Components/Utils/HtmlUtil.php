<?php
namespace App\Components\Utils;

class HtmlUtil
{
    public static function generateTable(array $tableInfo)
    {
        $content = self::getCssStyle();
        $content .= "<table style=\"border:1px solid #bfd1eb; width:99%;\">";
        $content .= self::getTableStyle(); 
        $header = $tableInfo['th'];
        $data = $tableInfo['data'];
        $content .= "<tr>";
        foreach ($header as $th) {
            $content .= "<th>$th</th>";
        }
        $content .= "</tr>\r\n";

        foreach ($data as $row) {
            $content .= "<tr align>";
            foreach ($row as $column) {
                $content .= "<td style=\"text-align:center\">$column</td>";
            }
            $content .= "</tr>";
        }
        $content .= "</table>"; 

        return $content;
    }

    public static function getHtmlSpace($n)
    {
        $ret = null;
        for($i=0; $i<$n; ++$i)
        {
            $ret .= "&nbsp;";
        }
        return $ret;
    }

    public static function getHtmlTd($n)
    {
        $ret = null;
        for($i=0; $i<$n; ++$i)
        {
            $ret .= "<td></td>";
        }
        return $ret;
    }

    public static  function getHeadline($level,$space, $value)
    {
        $level = $level."px";
        $pre= getHtmlTd($space);
        $after = getHtmlTd(3-$space);
        $extSpace = "";
        if($space == 0)
            $extSpace = getHtmlSpace(8);
        return "<tr>$pre<td style=\"font:$level\">$value.$extSpace</td>$after</tr>";
    }

    public static function getCssStyle()
    {
        $style = array(
            "border-color: #96c2f1;background:#eff7ff;",
            "border-color: #9bdf70;background:#f0fbeb;",
            "border-color: #bbe1f1;background:#eefaff;",
            "border-color: #cceff5;background:#fafcfd;",
            "border-color: #ffcc00;background:#fffff7;",
            "border-color: #cee3e9;background:#f1f7f9;",
            "border-color: #a9c9e2;background:#e8f5fe;",
            "border-color: #e3e197;background:#ffffdd;",
            "border-color: #adcd3c;background:#f2fddb;",
            "border-color: #f8b3d0;background:#fff5fa;",
            "border-color: #d3d3d3;background:#f7f7f7;",
            "border-color: #bfd1eb;background:#f3faff;",
            "border-color: #ffdd99;background:#fff9ed;",
            "border-color: #cacaff;background:#f7f7ff;",
            "border-color: #a5b6c8;background:#eef3f7;",
        );
        srand(time()+10);
        $syth = $style[rand(0,count($style)-1)];
        srand(time() + 10000000);
        $sy = $style[rand(0,count($style)-1)];

        return "<style type=\"text/css\">
   table.imagetable {
       font-family: verdana,arial,sans-serif;
       width:100%;
       font-size:12px;
       border-width: 1px;
       $sy
                    border-collapse: collapse;
    }
    table.imagetable th {
        $syth
            border-width: 1px;
        font-size:20px;
        padding: 8px;
        border-style: solid;
    }
    table.imagetable td {
        $sy
            border-width: 1px;
        padding: 8px;
        border-style: solid;
    }
    </style>";
    }
    public static function getTableStyle()
    {

        return "<table class=\"imagetable\" >";
    }
}


<?php

/*
 * diff结果解析
 * author:sunquangang(sunquangang@360.cn)
 */

namespace App\Components\Jenkins;

class DiffModel
{
    private $new = array();
    private $old = array();
    private $intra = array();
    private $visible = array();
    private $hunks = array();

    const LINES_CONTEXT     = 5;
    /*
     * parseDiff
     * @param array lines
     * @return array('old', 'new', 'intra', 'visible')
     */
    public function parseDiff($lines)
    {/*{{{*/
        $this->split2Hunks($lines);
        
        foreach($this->hunks as $hunk)
        {
            $this->parseHunk($hunk);
        }
        $this->addSkip();
        $this->generateIntra();
        $this->generateVisible();

        //$tmp = [];
        //foreach ($this->old as $chunk) {
            //if (!is_null($chunk)) {
                //$tmp[] = $chunk;
            //}
        //}
        return array(
            'old'   => $this->old,
            'new'   => $this->new, 
            'intra' => $this->intra, 
            'visible'   => $this->visible
        );
    }/*}}}*/

    public function addSkip()
    {/*{{{*/
        $old = array();
        $new = array();

        $this->old = array_reverse($this->old);
        $this->new = array_reverse($this->new);

        $skip_intra = array();
        while(count($this->old) || count($this->new))
        {
            $o_desc = array_pop($this->old);
            $n_desc = array_pop($this->new);

            $oend = end($this->old);
            if($oend)
            {
                $o_next = $oend['type'];
            }else{
                $o_next = null;
            }

            $nend = end($this->new);
            if($nend){
                $n_next = $nend['type'];
            }else{
                $n_next = null;
            }

            if($o_desc){
                $o_type = $o_desc['type'];
            }else{
                $o_type = null;
            }

            if($n_desc){
                $n_type = $n_desc['type'];
            }else{
                $n_type = null;
            }

            if(($o_type != null) && ($n_type == null))
            {
                $old[] = $o_desc;
                $new[] = null;
                if($n_desc){
                    array_push($this->new, $n_desc);
                }
                continue;
            }

            if(($n_type != null) && ($o_type == null))
            {
                $old[] = null;
                $new[] = $n_desc;
                if($o_desc){
                    array_push($this->old, $o_desc);
                }
                continue;
            }

            $old[] = $o_desc;
            $new[] = $n_desc;
        }

        $this->old = $old;
        $this->new = $new;
    }/*}}}*/

    /*
     * split2Hunks
     * @param string lines
     * @return array(
     *      0 => array('lines' => $hunk, 'OldOffSet' => $oldoffset, 'NewOffSet' => $newoffset),
     *      1 => array('lines' => $hunk, 'OldOffSet' => $oldoffset, 'NewOffSet' => $newoffset),
     * )
     */
    public function split2Hunks($lines)
    {/*{{{*/
        $line_count = count($lines);
        for($line_idx = 0; $line_idx < $line_count; )
        {/*{{{*/
            $real = array();
            $line = $lines[$line_idx];
            if(!preg_match('/^@@ /', $line))
            {
                break;
            }

            $matches = null;
            $ok = preg_match(
                '/^@@ -(\d+)(?:,(\d+))? \+(\d+)(?:,(\d+))? @@(?: .*?)?$/U',
                $line,
                $matches);

            if(!$ok)
            {
                return false;
            }

            $old_off_set = $matches[1];
            $new_off_set = $matches[3];

            // Cover for the cases where length wasn't present (implying one line).
            $old_len = 1;
            if(array_key_exists(2, $matches) && is_numeric($matches[2]))
            {
                $old_len = $matches[2];
            }
            $new_len = 1;
            if(array_key_exists(4, $matches))
            {
                $new_len = $matches[4];
            }

            $add = 0;
            $del = 0;

            while($line_idx++ < $line_count)
            {/*{{{*/
                if (!isset($lines[$line_idx])) {
                    continue;
                }
                $line = $lines[$line_idx];
                if(strlen($line)){
                    $char = $line[0];
                }else{
                    $char = ' ';
                }
                switch($char){
                case '\\':
                    if(!preg_match('@\\ No newline at end of file@', $line))
                    {
                            return false;
                    }
                    if(!$new_len)
                    {
                        break 2;
                    }
                    break;
                case '+':
                    if(!$new_len){
                        break 2;
                    }
                    ++$add;
                    --$new_len;
                    $real[] = $line;
                    break;
                case '-':
                    if(!$old_len){
                        break 2;
                    }
                    ++$del;
                    --$old_len;
                    $real[] = $line;
                    break;
                case ' ':
                    if(!$old_len && !$new_len){
                        break 2;
                    }
                    --$old_len;
                    --$new_len;
                    $real[] = $line;
                    break;
                case '~':
                    break 2;
                default:
                    break 2;
                }
            }/*}}}*/

            if($old_len != 0 || $new_len != 0){
                return false;
            }

            $corpus = implode("\n", $real);
            $this->hunks[] = array('lines' => $corpus, 'oldOffSet' => $old_off_set, 'newOffSet' => $new_off_set);
        }/*}}}*/
    }/*}}}*/

    /*
     * 分解diff结果为new和old两个部分
     * @param array hunk
     */
    public function parseHunk($hunk)
    {/*{{{*/
        $lines = $hunk['lines'];
        $lines = str_replace(
            array("\t", "\r\n", "\r"),
            array('  ', "\n",   "\n"),
            $lines);
        $lines = explode("\n", $lines);

        $types = array();
        foreach($lines as $line_index => $line)
        {/*{{{*/
            if(isset($line[0])){
                $char = $line[0];
                if($char == ' '){
                    $types[$line_index] = null;
                }elseif($char == '\\' && $line_index > 0){
                    $types[$line_index] = $types[$line_index - 1];
                }else{
                    $types[$line_index] = $char;
                }
            }else{
                $types[$line_index] = null;
            }
        }/*}}}*/

        $old_line = $hunk['oldOffSet'];
        $new_line = $hunk['newOffSet'];
        $num_lines = count($lines);

        for($cursor = 0; $cursor < $num_lines; $cursor++)
        {/*{{{*/
            $type = $types[$cursor];
            $data = array(
                'type'  => $type,
                'text'  => (string)substr($lines[$cursor], 1),
            );
            switch($type)
            {/*{{{*/
            case '+':
                $data['line']   = $new_line;
                $this->new[]    = $data;
                ++$new_line;
                break;
            case '-':
                $data['line']   = $old_line;
                $this->old[]    = $data;
                ++$old_line;
                break;
            default:
                $data['line']   = $new_line;
                $this->new[]    = $data;
                $data['line']   = $old_line;
                $this->old[]    = $data;
                ++$new_line;
                ++$old_line;
                break;
            }/*}}}*/
        }/*}}}*/
    }/*}}}*/

    /*
     * 对每一行生成详细的不一致部分
     */
    public function generateIntra()
    {/*{{{*/
        $min_length = min(count($this->old), count($this->new));
        for($ii = 0; $ii < $min_length; $ii++)
        {
            if($this->old[$ii] || $this->new[$ii])
            {
                if(isset($this->old[$ii]['text']))
                {
                    $otext = $this->old[$ii]['text'];
                }else{
                    $otext = '';
                }
                if(isset($this->new[$ii]['text']))
                {
                    $ntext = $this->new[$ii]['text'];
                }else{
                    $ntext = '';
                }
                if($otext != $ntext && empty($skip_intra[$ii]))
                {
                    $this->intra[$ii] = self::generateIntralineDiff($otext, $ntext);
                }
            }
        }

    }/*}}}*/

    /*
     * 生成需要显示的行数组
     */
    public function generateVisible()
    {/*{{{*/
        $lines_context = self::LINES_CONTEXT;
        $max_length = max(count($this->old), count($this->new));
        $old = $this->old;
        $new = $this->new;
        $visible = false;
        $last = 0;
        for($cursor = -$lines_context; $cursor < $max_length; $cursor++)
        {
            $offset = $cursor + $lines_context;
            if((isset($old[$offset]) && $old[$offset]['type']) ||
               (isset($new[$offset]) && $new[$offset]['type'])){
                    $visible = true;
                    $last = $offset;
                }else if($cursor > $last + $lines_context){
                    $visible = false;
                }
            if($visible && $cursor >= 0){
                $this->visible[$cursor] = 1;
            }
        }
    }/*}}}*/

    public static function generateIntralineDiff($o, $n)
    {/*{{{*/
        if (!strlen($o) || !strlen($n)) {
            return array(
                array(array(0, strlen($o))),
                array(array(0, strlen($n)))
            );
        }

        // This algorithm is byte-oriented and thus not safe for UTF-8, so just
        // mark all the text as changed if either string has multibyte characters
        // in it. TODO: Fix this so that this algorithm is UTF-8 aware.
        if (preg_match('/[\x80-\xFF]/', $o.$n)) {
            return array(
                array(array(1, strlen($o))),
                array(array(1, strlen($n))),
            );
        }

        $result = self::buildLevenshteinDifferenceString($o, $n);

        do {
            $orig = $result;
            $result = preg_replace(
                '/([xdi])(s{3})([xdi])/',
                '$1xxx$3',
                $result);
            $result = preg_replace(
                '/([xdi])(s{2})([xdi])/',
                '$1xx$3',
                $result);
            $result = preg_replace(
                '/([xdi])(s{1})([xdi])/',
                '$1x$3',
                $result);
        } while ($result != $orig);

        $o_bright = array();
        $n_bright  = array();
        $rlen   = strlen($result);
        $len = -1;
        $cur = $result[0];
        $result .= '-';
        for ($ii = 0; $ii < strlen($result); $ii++) {
            $len++;
            $now = $result[$ii];
            if ($result[$ii] == $cur) {
                continue;
            }
            if ($cur == 's') {
                $o_bright[] = array(0, $len);
                $n_bright[] = array(0, $len);
            } else if ($cur == 'd') {
                $o_bright[] = array(1, $len);
            } else if ($cur == 'i') {
                $n_bright[] = array(1, $len);
            } else if ($cur == 'x') {
                $o_bright[] = array(1, $len);
                $n_bright[] = array(1, $len);
            }
            $cur = $now;
            $len = 0;
        }

        $o_bright = self::collapseIntralineRuns($o_bright);
        $n_bright = self::collapseIntralineRuns($n_bright);

        return array($o_bright, $n_bright);
    }/*}}}*/

    public static function buildLevenshteinDifferenceString($o, $n)
    {/*{{{*/
        $olt = strlen($o);
        $nlt = strlen($n);

        if (!$olt) {
            return str_repeat('i', $nlt);
        }

        if (!$nlt) {
            return str_repeat('d', $olt);
        }

        $min = min($olt, $nlt);
        $t_start = microtime(true);

        $pre = 0;
        while ($pre < $min && $o[$pre] == $n[$pre]) {
            $pre++;
        }

        $end = 0;
        while ($end < $min && $o[($olt - 1) - $end] == $n[($nlt - 1) - $end]) {
            $end++;
        }

        if ($end + $pre >= $min) {
            $end = min($end, $min - $pre);
            $prefix = str_repeat('s', $pre);
            $suffix = str_repeat('s', $end);
            $infix = null;
            if ($olt > $nlt) {
                $infix = str_repeat('d', $olt - ($end + $pre));
            } else if ($nlt > $olt) {
                $infix = str_repeat('i', $nlt - ($end + $pre));
            }
            return $prefix.$infix.$suffix;
        }

        if ($min - ($end + $pre) > 80) {
            $max = max($olt, $nlt);
            return str_repeat('x', $min) .
                str_repeat($olt < $nlt ? 'i' : 'd', $max - $min);
        }

        $prefix = str_repeat('s', $pre);
        $suffix = str_repeat('s', $end);
        $o = substr($o, $pre, $olt - $end - $pre);
        $n = substr($n, $pre, $nlt - $end - $pre);

        $ol = strlen($o);
        $nl = strlen($n);

        $m = array_fill(0, $ol + 1, array_fill(0, $nl + 1, array()));

        $T_D = 'd';
        $T_I = 'i';
        $T_S = 's';
        $T_X = 'x';

        $m[0][0] = array(
            0,
            null);

        for ($ii = 1; $ii <= $ol; $ii++) {
            $m[$ii][0] = array(
                $ii * 1000,
                $T_D);
        }

        for ($jj = 1; $jj <= $nl; $jj++) {
            $m[0][$jj] = array(
                $jj * 1000,
                $T_I);
        }

        $ii = 1;
        do {
            $jj = 1;
            do {
                if ($o[$ii - 1] == $n[$jj - 1]) {
                    $sub_t_cost = $m[$ii - 1][$jj - 1][0] + 0;
                    $sub_t      = $T_S;
                } else {
                    $sub_t_cost = $m[$ii - 1][$jj - 1][0] + 2000;
                    $sub_t      = $T_X;
                }

                if ($m[$ii - 1][$jj - 1][1] != $sub_t) {
                    $sub_t_cost += 1;
                }

                $del_t_cost = $m[$ii - 1][$jj][0] + 1000;
                if ($m[$ii - 1][$jj][1] != $T_D) {
                    $del_t_cost += 1;
                }

                $ins_t_cost = $m[$ii][$jj - 1][0] + 1000;
                if ($m[$ii][$jj - 1][1] != $T_I) {
                    $ins_t_cost += 1;
                }

                if ($sub_t_cost <= $del_t_cost && $sub_t_cost <= $ins_t_cost) {
                    $m[$ii][$jj] = array(
                        $sub_t_cost,
                        $sub_t);
                } else if ($ins_t_cost <= $del_t_cost) {
                    $m[$ii][$jj] = array(
                        $ins_t_cost,
                        $T_I);
                } else {
                    $m[$ii][$jj] = array(
                        $del_t_cost,
                        $T_D);
                }
            } while ($jj++ < $nl);
        } while ($ii++ < $ol);

        $result = '';
        $ii = $ol;
        $jj = $nl;
        do {
            $r = $m[$ii][$jj][1];
            $result .= $r;
            switch ($r) {
            case $T_S:
            case $T_X:
                $ii--;
                $jj--;
                break;
            case $T_I:
                $jj--;
                break;
            case $T_D:
                $ii--;
                break;
            }
        } while ($ii || $jj);

        return $prefix.strrev($result).$suffix;
    }/*}}}*/

    private static function collapseIntralineRuns($runs)
    {/*{{{*/
        $count = count($runs);
        for ($ii = 0; $ii < $count - 1; $ii++) {
            if ($runs[$ii][0] == $runs[$ii + 1][0]) {
                $runs[$ii + 1][1] += $runs[$ii][1];
                unset($runs[$ii]);
            }
        }
        return array_values($runs);
    }/*}}}*/

}

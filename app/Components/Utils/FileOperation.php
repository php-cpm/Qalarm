<?php

/*
 * 文件操作类
 * @author sunquangang
 * 2012-7-26
 */

namespace App\Components\Utils;

define( "FILE_DEFAULT_DIRECTORY_CREATION_MODE", 0777 );

class FileOperation
{/*{{{*/
    const IS_SUDO   = true;
    const NO_SUDO   = null;

    const IS_BACK_RUN   = true;
    const NO_BACK_RUN   = false;

    const IS_RETRY  = true;
    const NO_RETRY  = false;
    const RETRY_TIMES = 3;

    /*
     * sudo user
     */
    private $sudo_user;

    /*
     * env
     * 字符编码
     */
    private $env;

    /*
     * 是否执行sudo
     */
    private $isSudo = false;

    /*
     * ssh
     * ssh 命令
     */
    private $ssh = "ssh -o StrictHostKeyChecking=no ";

    /*
     * scp
     * scp 命令
     */
    private $scp = "scp -o StrictHostKeyChecking=no ";

    /*
     * 命令输出
     */
    private $output;

    /*
     * 命令执行的返回状态
     */
    private $status;

    function __construct($sudo_user = '', $env = '', $timeout = 3)
    {/*{{{*/
        if(!empty($sudo_user))
        {
            $this->sudo_user = $sudo_user;
            $this->isSudo = true;
        }
        $this->env = 'env ' . $env;
        $this->ssh .= "-o ConnectTimeout=$timeout ";
        $this->scp .= "-o ConnectTimeout=$timeout ";
    }/*}}}*/

    /*
     * 执行命令
     * @param string command
     * @param boolean isSudo
     * @param boolean isBack
     * @return boolean 成功 true 失败 false
     */
    private function _executeCommand($command, $isSudo = null, $isBack = false, $isRetry = false)
    {/*{{{*/
        if(empty($command))
        {
            $this->status = 1;
            return false;
        }
        if(null == $isSudo)
        {
            $isSudo = $this->isSudo;
        }
        $command = $isSudo ? "sudo -u {$this->sudo_user} $command" : $command;
        
        $this->output = array();
        $this->status = 0;
        
        if(!strpos($command, '&&') && !strpos($command, '*') && !strpos($command, ';'))
        {
            $command = escapeshellcmd($command);
        }

        $command .= " 2>&1";
        $command .= $isBack ? " &" : "";

        $retry_times = $isRetry ? self::RETRY_TIMES : 1;
        for($i = 0; $i < $retry_times; $i++)
        {
            $ret = $this->_execute($command);
            if($ret) return true;

            sleep(1);
        }
        return false;
    }/*}}}*/

    private function _execute($command)
    {/*{{{*/
        $start_time = microtime(true); 
        session_write_close();
        exec($command, $this->output, $this->status);
        session_start();
        $consume = microtime(true) - $start_time; 

        $str_output = json_encode($this->output);
        //error_log("time: " . date('Y-m-d H:i:s') . "\nfile command: $command\nfile output:$str_output\nfile status:$this->status\nconsume:$consume\n\n", 3, Constants::$base_log_path . "/file.log." . date('Ymd', time()));
        return 0 === $this->status;
    }/*}}}*/

    /*
     * 获取命令执行的返回码
     * @return int 状态码
     */
    public function getStatus()
    {/*{{{*/
        return $this->status;
    }/*}}}*/
	
	/**
	 * 获取输出结果
	 * @param boolean $toString 是否以字符串的形式返回
	 * @return multitype 输出结果
	 */
	public function getOutput($toString = true)
    {/*{{{*/
        if(!$toString)
        {
            return $this->output;
        }

        $output = '';
        foreach ($this->output as $line)
        {
            $output .= $line;
        }

        return $output;
    }/*}}}*/

    /**
     * returns true if the file exists.
     *
     * Can be used as an static method if a file name is provided as a
     *  parameter
     * @param fileName optinally, name of the file whose existance we'd
     * like to check
     * @return true if successful or false otherwise
     */
    public function exists($fileName = null) 
    {/*{{{*/
        clearstatcache();
        return file_exists($fileName);
    }/*}}}*/ 

    public function fileExistsByShell($fileName, $host = null) 
    {/*{{{*/
        $command = !empty($host) ? $this->ssh . "$host " : "";

        $command .= "test -e $fileName";

        $this->_executeCommand($command);
        return (0 == $this->getStatus());
    }/*}}}*/ 

    public function pathExistsByShell($pathName, $host = null, $host2 = null) 
    {/*{{{*/
        $command = !empty($host) ? $this->ssh . "$host " : "";

        $command .= !empty($host2) ? $this->ssh . "$host2 " : "";

        $command .= "test -d $pathName";

        $this->_executeCommand($command);
        return (0 == $this->getStatus());
    }/*}}}*/ 

    /** 
     * returns true if the file could be touched
     *
     * Can be used to create a file or to reset the timestamp.
     * @return true if successful or false otherwise
     * @see PHP Function touch()
     *
     */
    function chmodByShell( $fileName = null , $mode = FILE_DEFAULT_DIRECTORY_CREATION_MODE)
    {/*{{{*/
        if( $fileName == null )
            return false;
        if(!$this->exists(dirname($fileName)))
        {
            $this->createDirByShell(dirname($fileName));
        }
        if($this->exists($fileName))
        {
            $command = "chmod $mode $fileName";

            $this->_executeCommand($command);
            return (0 == $this->getStatus());
        }
    }/*}}}*/

    /** 
     * returns true if the file could be touched
     *
     * Can be used to create a file or to reset the timestamp.
     * @return true if successful or false otherwise
     * @see PHP Function touch()
     *
     */
    function touch( $fileName = null , $mode = FILE_DEFAULT_DIRECTORY_CREATION_MODE)
    {/*{{{*/
        if( $fileName == null )
            return false;
        if(!$this->exists(dirname($fileName)))
        {
            $this->createDir(dirname($fileName));
        }
        if($this->exists($fileName))
        {
            unlink($fileName);
        }

        touch($fileName);
        return chmod($fileName, $mode);
    }/*}}}*/

    /*
     * shell touch 
     * @param string fileName
     * @return
     */
    public function touchByShell($fileName)
    {/*{{{*/
        if(!$this->exists(dirname($fileName)))
        {
            $this->createDirByShell(dirname($fileName));
        }

        $fileName = escapeshellarg($fileName);
        $command = "touch $fileName";
        $this->_executeCommand($command);
        return array($this->getOutput(), $this->getStatus());
    }/*}}}*/ 

    /**
     * Creates a new folder. If the folder name is /a/b/c and neither 
     * /a or /a/b exist, this method will take care of creating the 
     * whole folder structure automatically.
     *
     * @static
     * @param dirName The name of the new folder
     * @param mode Attributes that will be given to the folder
     * @return Returns true if no problem or false otherwise.
     */
    public function createDir($dirName, $mode = FILE_DEFAULT_DIRECTORY_CREATION_MODE )
    {/*{{{*/
        if($this->exists($dirName)) return true;

        if(substr($dirName, strlen($dirName)-1) == "/" ){
            $dirName = substr($dirName, 0,strlen($dirName)-1);
        }

        // for example, we will create dir "/a/b/c"
        // $firstPart = "/a/b"
        $firstPart = substr($dirName,0,strrpos($dirName, "/" ));           

        if($this->exists($firstPart)){
            if(!mkdir($dirName,$mode)) return false;
            chmod( $dirName, $mode );
        }else{
            $this->createDir($firstPart,$mode);
            if(!mkdir($dirName,$mode)) return false;
            chmod( $dirName, $mode );
        }

        return true;
    }/*}}}*/

    /*
     * @param string srcDirName
     * @param string destDirName
     * @param string host
     * @param string host2
     * @return 
     */
    public function createSoftLinkByShell($srcDirName, $destDirName, $host, $host2 = '')
    {/*{{{*/
        $destDirName = rtrim($destDirName, '/');
        //$this->deletePathByShell($destDirName, $host, $host2);

        $srcDirName = escapeshellarg($srcDirName);
        $destDirName = escapeshellarg($destDirName);
        $command = empty($host) ? "" : $this->ssh . "$host ";
        $command .= empty($host2) ? "" : $this->ssh . "$host2 ";
        $command .= "ln -nfs $srcDirName $destDirName";
        $this->_executeCommand($command);
        return 0 == $this->getStatus();
    }/*}}}*/

    /*
     * @param string dirName
     * @param string host
     * @param string host2
     * @return 
     */
    public function createDirByShell($dirName, $host = '', $host2 = '')
    {/*{{{*/
        if(empty($host))
        {
            if($this->exists($dirName)) return true;
        }else{
            if($this->pathExistsByShell($dirName, $host, $host2)) return true;
        }
        $dirName = escapeshellarg($dirName);
        $command = empty($host) ? "" : $this->ssh . "$host ";
        $command .= empty($host2) ? "" : $this->ssh . "$host2 ";
        $command .= "mkdir -p $dirName";
        //print($command);
        //exit;
        $this->_executeCommand($command);
        return 0 == $this->getStatus();
    }/*}}}*/

    /*
     * 删除目录中的文件
     * @param string file_path
     * @return boolean 成功返回true， 失败返回false
     */
    public function deleteFileInDir($file_path)
    {/*{{{*/
        $command = "rm -rf $file_path; mkdir -m 777 $file_path";
        $this->_executeCommand($command);
        return array($this->getOutput(), $this->getStatus());
    }/*}}}*/

    /*
     * 删除目录
     * @param string path
     * @return boolean 成功返回true， 失败返回false
     */
    public function deletePath($path)
    {/*{{{*/
        $command = "rm -rf $path";
        $this->_executeCommand($command);
        return array($this->getOutput(), $this->getStatus());
    }/*}}}*/

    /*
     * 文件patch
     * @param string patch_path
     * @param string src_path
     * @param array files
     * @param string host
     * @return boolean 成功true 失败false
     */
    public function patch($patch_path, $src_path, $files = array(), $host = '')
    {/*{{{*/
        $command = '';
        if(!empty($host))
        {
            $command = $this->ssh . "$host $this->env ";
            $mkdir = "mkdir -p ".dirname($patch_path);
        }
        else
        {
            clearstatcache();
            if(!file_exists(dirname($patch_path)))
            {
                $this->createDirByShell(dirname($patch_path));
            }
        }

        $file_str = '';
        if(!empty($files))
        {
            foreach($files as &$file)
            {
                $file = escapeshellarg($file);
            }
            $file_str = implode(' ', $files);
        }

        $cvfz = sprintf("tar cfz %s -C %s %s --ignore-failed-read ", $patch_path, $src_path, $file_str);

        $command = empty($command)? $cvfz : $command.'"'.$mkdir.';'.$cvfz.'"';
        
        $try_count = 4;
        do{
            $this->_executeCommand($command);
            if($this->getStatus() == 255)
            { 
                usleep(100000);
            }else{
                break;    
            }
        }while($try_count-- >= 0);

        return array($this->getOutput(), $this->getStatus());
    }/*}}}*/

    /*
     * 文件解压缩
     * @param string patch_path
     * @param string dest_path
     * @param string host
     * @return boolean 成功true 失败false
     */
    public function unpatch($patch_path, $dest_path, $host = '')
    {/*{{{*/ 
        $command = '';
        $sub_command = sprintf("tar mxfz %s -C %s", $patch_path, $dest_path);
        if(!empty($host))
        {
            $command = $this->ssh . "$host $this->env ";
            $command .= '" ' . sprintf("mkdir -p %s ", $dest_path) . " && " . $sub_command . ' "';
        }else{
            $command .= $sub_command; 
        }

        $try_count = 4;
        do{
            $this->_executeCommand($command);
            $str_out = $this->getOutput(true);
            if(false !== strrpos($str_out, 'Connection') && $this->getStatus() !== 0)
            { 
                usleep(100000);
            }else{
                break;    
            }
        }while($try_count-- >= 0);

        return array($this->getOutput(), $this->getStatus());
    }/*}}}*/

    /*
     * 远程文件拷贝
     * @param string src_path
     * @param string dest_path
     * @param string src_host
     * @param string dest_host
     * @return boolean 成功true 失败false
     */
    public function scp($src_path, $dest_path, $src_host = '', $dest_host = '')
    {/*{{{*/
        $command = $this->scp . "-r $src_path";
        if(!empty($src_host))
        {
            $command = $this->scp . "-r $src_host:$src_path";
        }

        if(!empty($dest_host))
        {
            $command = "$command $dest_host:$dest_path";
        }
        else
        {
            $this->createDirByShell($dest_path);
            $command = "$command $dest_path";
        }

        $try_count = 4;
        do{
            $this->_executeCommand($command, self::IS_SUDO, self::NO_BACK_RUN);
            $str_out = $this->getOutput(true);
            if(false !== strrpos($str_out, 'Connection') && $this->getStatus() !== 0)
            { 
                usleep(100000);
            }else{
                break;    
            }
        }while($try_count-- >= 0);
        return array($this->getOutput(), $this->getStatus());
    }/*}}}*/ 

    /*
     * 文件移动
     * @param string src_path
     * @param string dest_path
     */
    public function move($src_path, $dest_path)
    {/*{{{*/
        $dest_path = escapeshellarg($dest_path);
        $command = "mv -f $src_path/* $dest_path";
        $this->_executeCommand($command);
        return array($this->getOutput(), $this->getStatus());
    }/*}}}*/

    /*
     * 文件复制
     * @param string src_path
     * @param string dest_path
     */
    public function copy($src_path, $dest_path)
    {/*{{{*/
        $dest_path = escapeshellarg($dest_path);

        $command = "cp -r $src_path/. $dest_path";
        $this->_executeCommand($command);
        return array($this->getOutput(), $this->getStatus());
    }/*}}}*/

    /*
     * 文件diff
     * @param string first_file
     * @param string second_file
     * @param string option
     * @return output
     */
    public function diff($first_file, $second_file, $option = '')
    {/*{{{*/
        $first_file_exist  = true;
        $second_file_exist = true;
        if(!$this->exists($first_file))
        {
            $first_file_exist = false;
            $this->touchByShell($first_file);
        }
        if(!$this->exists($second_file))
        {
            $second_file_exist = false;
            $this->touchByShell($second_file);
        }
        $first_file = escapeshellarg($first_file);
        $second_file = escapeshellarg($second_file);
        $command = "diff $option $first_file $second_file ";

        $this->_executeCommand($command);
        $ret = array($this->getOutput(false), $this->getStatus());

        if(false == $first_file_exist)
        {
            $this->deletePath($first_file);
        }
        if(false == $second_file_exist)
        {
            $this->deletePath($second_file);
        }
        return $ret; 
    }/*}}}*/ 

    /*
     * 语法检查
     * @param string file_pat$SHELL_PATH
     * @return boolean 有语法错误 true， 没有语法错误 false
     */
    public function isSyntaxErr($file_path, $php_path = '/usr/local/bin/php')
    {/*{{{*/
        $file_path = escapeshellarg($file_path);
        $SHELL_PATH = dirname(dirname(dirname(__FILE__)));
        $command = "sh $SHELL_PATH/src/shell/checkSyntexErr.sh $file_path $php_path";

        $this->_executeCommand($command);
        return array($this->getOutput(false), $this->getStatus());
    }/*}}}*/

    /*
     * 获取不同的文件
     * @param string old_path
     * @param string new_path
     * @param boolean is_filter
     * @param array files
     * @return output
     */
    public function getDiffFiles($old_path, $new_path, $is_filter, $files)
    {/*{{{*/
        $SHELL_PATH = dirname(dirname(dirname(__FILE__)));
        foreach($files as &$file)
        {
            $file = escapeshellarg($file);
        }
        $is_filter = $is_filter ? 'true' : 'false';
        $files_str = implode(' ', $files);
        //$command = "sh $SHELL_PATH/src/shell/getDiffFiles.sh --old_path $old_path --new_path $new_path --is_filter $is_filter --file $files_str ";
        $command = "sh $SHELL_PATH/Components/Utils/getDiffFiles.sh --old_path $old_path --new_path $new_path --is_filter $is_filter --file $files_str ";

        $this->_executeCommand($command);
        return array($this->getOutput(false), $this->getStatus());
    }/*}}}*/ 

    /*
     * rsync
     * @param string auto_load_path
     * @param string log_base
     * @param string www_path
     * @param string bak_path
     * @param string file
     * @param string servers_str
     * @return boolean 成功true 失败false
     */
    public function reg2OnlineByRsync($auto_load_path, $log_base, $www_path, $bak_path, $file, $servers_str)
    {/*{{{*/
        $SHELL_PATH = dirname(dirname(dirname(__FILE__)));
        $command = "sh $SHELL_PATH/src/shell/reg2OnlineV1.sh"; 
        $command .= " --auto_load $auto_load_path --log_base $log_base --ssh_user $this->sudo_user --www_path $www_path --bak_path $bak_path --file $file $servers_str ";

        $this->_executeCommand($command, self::IS_SUDO, self::IS_BACK_RUN);
        return array($this->getOutput(), $this->getStatus());
    }/*}}}*/  

    /*
     * rsync
     * @param string log_base
     * @param string www_path
     * @param boolean is_soft_link
     * @param string servers_str
     * @param string exclude_files_str
     * @param boolean is_web_path_soft
     * @return boolean 成功true 失败false
     */
    public function reg2OnlineByRsyncV2($log_base, $www_path, $is_soft_link, $servers_str, $exclude_files_str, $is_web_path_soft = 'false', $is_online_shell = false, $online_shell = '')
    {/*{{{*/
        $SHELL_PATH = dirname(dirname(dirname(__FILE__)));
        $command = "sh $SHELL_PATH/src/shell/reg2OnlineV2.sh"; 
        if(!empty($exclude_files_str))
        {
            $command .= " --exclude_files $exclude_files_str"; 
        }
        $command .= " --log_base $log_base"; 
        if(!empty($is_online_shell)){
            $command .= " --online_shell $online_shell"; 
        }
        $command .= " --ssh_user $this->sudo_user --www_path $www_path --soft_link $is_soft_link --web_path_soft_link $is_web_path_soft $servers_str ";

        $this->_executeCommand($command, self::IS_SUDO, self::IS_BACK_RUN);
        return array($this->getOutput(), $this->getStatus());
    }/*}}}*/  

    /*
     * rsync
     * @param string log_base
     * @param string www_path
     * @param boolean is_soft_link
     * @param string servers_str
     * @param string exclude_files_str
     * @param boolean is_web_path_soft
     * @return boolean 成功true 失败false
     */
    public function reg2OnlineByRsyncV3($log_base, $www_path, $is_soft_link, $servers_str, $exclude_files_str, $is_web_path_soft = 'false')
    {/*{{{*/
        $SHELL_PATH = dirname(dirname(dirname(__FILE__)));
        $command = "sh $SHELL_PATH/src/shell/reg2OnlineV2.sh"; 
        if(!empty($exclude_files_str))
        {
            $command .= " --exclude_files $exclude_files_str"; 
        }
        $command .= " --log_base $log_base --ssh_user $this->sudo_user --www_path $www_path --soft_link $is_soft_link --web_path_soft_link $is_web_path_soft $servers_str ";

        $this->_executeCommand($command, self::IS_SUDO, self::IS_BACK_RUN);
        return array($this->getOutput(), $this->getStatus());
    }/*}}}*/  

    /*
     * rsync
     * @param string auto_load_path
     * @param string log_base
     * @param string www_path
     * @param string bak_path
     * @param string servers_str
     * @return boolean 成功true 失败false
     */
    public function onlineRollBack($auto_load_path, $log_base, $www_path, $bak_path, $servers_str)
    {/*{{{*/  
        $SHELL_PATH = dirname(dirname(dirname(__FILE__)));
        $command = "sh $SHELL_PATH/src/shell/onlineRollBackV1.sh";
        $command .= " --auto_load $auto_load_path  --log_base $log_base --ssh_user $this->sudo_user --www_path $www_path --bak_path $bak_path $servers_str";
        $this->_executeCommand($command, self::IS_SUDO, self::IS_BACK_RUN);
        return array($this->getOutput(), $this->getStatus());
    }/*}}}*/  

    /*
     * 执行脚本
     * @param string path
     * @return true false
     */
    public function execShell($shell_path, $target_path ='')
    {/*{{{*/
        $command = "sh $shell_path";
        if(!empty($target_path))
        {
            $target_path = escapeshellarg($target_path);
            $command = $this->env.' sh -c "cd '.$target_path.' && '.$command.'"';
        }else{
            $command = $this->env .' '. $command;
        }
        //$shell_path = escapeshellarg($shell_path);
        
        $this->_executeCommand($command, self::IS_SUDO);

        return array($this->getOutput(false), $this->getStatus());
    }/*}}}*/

    /*
     * 执行脚本
     * @param string path
     * @param string host
     * @return true false
     */
    public function execShellRemote($shell_path, $host, $target_path ='')
    {/*{{{*/
        if(!empty($target_path))
        {
            $target_path = escapeshellarg($target_path);
            $command = "cd $target_path ;";
        }
        $shell_path = escapeshellarg($shell_path);
        $command .= "$this->env sh $shell_path";
        
        $command = $this->ssh . "$host " . '"' . $command . '"';

        $this->_executeCommand($command);
        return array($this->getOutput(), $this->getStatus());
    }/*}}}*/
    
    /*
     * 通过回归机执行脚本
     * @param string path
     * @param string host
     * @return true false
     */
    public function execShellRemotePassByReg($shell_path, $regression, $host, $target_path ='')
    {/*{{{*/
        if(!empty($target_path))
        {
            $target_path = escapeshellarg($target_path);
            $command = "cd $target_path ;";
        }
        $shell_path = escapeshellarg($shell_path);
        $command .= "$this->env sh $shell_path";
        
        $command = $this->ssh . "$host " . "'" . $command . "'";
        
        $command = $this->ssh . "$regression " . '"' . $command . '"';

        error_log(var_export(array('command'=>$command), true), 3, '/tmp/cf.log');   

        $this->_executeCommand($command);
        return array($this->getOutput(), $this->getStatus());
    }/*}}}*/

    /*
     * @param string file_path
     * @param string black_list
     * @return boolean 成功true 失败false
     */
    public function deleteFileByType($file_path, $black_list)
    {/*{{{*/
        $file_path = escapeshellarg($file_path);
        $black_list = escapeshellarg($black_list);
        $SHELL_PATH = dirname(dirname(dirname(__FILE__)));
        $command = "sh $SHELL_PATH/src/shell/deleteFileByType.sh  $file_path $black_list";

        $this->_executeCommand($command);
        return array($this->getOutput(), $this->getStatus());
    }/*}}}*/ 

    /*
     * list
     * @param string path
     * @return list 
     */
    public function fileList($path)
    {/*{{{*/
        $command = "ls -AF $path";
        
        $this->_executeCommand($command);

        return array($this->getOutput(false), $this->getStatus());
    }/*}}}*/

}/*}}}*/

<?php
use \frame\log\Log;
class HomeOneMin extends BaseTimer {
	
	/**
     * [__construct 构造函数，设定轮训时间]
     * @param [type] $workerId [description]
     */
    public function __construct($taskConf)
    {
        //每隔10秒
        parent::__construct($taskConf, 10000, 'info');    
    }
	
	/**
     * [run 执行函数]
     * @return [type] [description]
     */
    public function run($subTaskId)
    {        
		Log::debug('正在执行' . date("Y-m-d H:i:s"));        
	}
	
	
	/**
	 * @param unknown $dir
	 * @param unknown $ex
	 */
	public function delDir($dir, $ex = array())
	{
	    //先删除目录下的文件：
	    $dh = opendir($dir);
	
	    while ($file = readdir($dh))
	    {	
	        if($file != "." && $file != "..")
	        {
	            $fullpath = $dir . "/" . $file;
	
	            if(!is_dir($fullpath))
	            {
	                if(!isset($ex[$file]))
	                   unlink($fullpath);	
	            }
	            else
	            {
	                $this->delDir($fullpath);
	            }
	
	        }
	
	    }
	
	    closedir($dh);	
	    return false;	
	}
}
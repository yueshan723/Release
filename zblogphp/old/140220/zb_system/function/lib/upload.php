<?php
/**
 * Z-Blog with PHP
 * @author
 * @copyright (C) RainbowSoft Studio
 * @version 2.0 2013-06-14
 */


class Upload extends Base{


	function __construct()
	{
		global $zbp;
		parent::__construct($zbp->table['Upload'],$zbp->datainfo['Upload']);

		$this->ID = 0;
		$this->PostTime = time();
	}

	function CheckExtName($extlist=''){
		global $zbp;
		$e=GetFileExt($this->Name);
		$extlist=strtolower($extlist);
		if(trim($extlist)=='')$extlist=$zbp->option['ZC_UPLOAD_FILETYPE'];
		if(HasNameInString($extlist,$e)){
			return true;
		}else{
			return false;
		}
	}

	function CheckSize(){
		global $zbp;
		$n=1024*1024*(int)$zbp->option['ZC_UPLOAD_FILESIZE'];
		if($n>=$this->Size){
			return true;
		}else{
			return false;
		}
	}

	function DelFile(){
	
		foreach ($GLOBALS['Filter_Plugin_Upload_DelFile'] as $fpname => &$fpsignal) {
			$fpreturn=$fpname($this);
			if ($fpsignal==PLUGIN_EXITSIGNAL_RETURN) {return $fpreturn;}
		}
		if (file_exists($this->FullFile)) { @unlink($this->FullFile);}
		return true;

	}

	function SaveFile($tmp){
		global $zbp;

		foreach ($GLOBALS['Filter_Plugin_Upload_SaveFile'] as $fpname => &$fpsignal) {
			$fpreturn=$fpname($tmp,$this);
			if ($fpsignal==PLUGIN_EXITSIGNAL_RETURN) {return $fpreturn;}
		}

		if(!file_exists($zbp->usersdir . $this->Dir)){
			@mkdir($zbp->usersdir . $this->Dir, 0755,true);	
		}
		if(PHP_OS=='WINNT'||PHP_OS=='WIN32'||PHP_OS=='Windows'){
			$fn=iconv("UTF-8","GBK//IGNORE",$this->Name);
		}else{
			$fn=$this->Name;
		}
		@move_uploaded_file($tmp, $zbp->usersdir . $this->Dir . $fn);
		return true;
	}

	function SaveBase64File($str64){
		global $zbp;

		foreach ($GLOBALS['Filter_Plugin_Upload_SaveBase64File'] as $fpname => &$fpsignal) {
			$fpreturn=$fpname($str64,$this);
			if ($fpsignal==PLUGIN_EXITSIGNAL_RETURN) {return $fpreturn;}
		}

		if(!file_exists($zbp->usersdir . $this->Dir)){
			@mkdir($zbp->usersdir . $this->Dir, 0755,true);	
		}
		$s=base64_decode($str64);
		$this->Size=strlen($s);
		if(PHP_OS=='WINNT'||PHP_OS=='WIN32'||PHP_OS=='Windows'){
			$fn=iconv("UTF-8","GBK//IGNORE",$this->Name);
		}else{
			$fn=$this->Name;
		}
		file_put_contents($zbp->usersdir . $this->Dir . $fn, $s);
		return true;
	}

	public function Time($s='Y-m-d H:i:s'){
		return date($s,$this->PostTime);
	}

	public function __set($name, $value)
	{
        global $zbp;
		if ($name=='Url') {
			return null;
		}
		if ($name=='Dir') {
			return null;
		}
		if ($name=='FullFile') {
			return null;
		}
		if ($name=='Author') {
			return null;
		}		
		parent::__set($name, $value);
	}

	public function __get($name)
	{
        global $zbp;
		if ($name=='Url') {
			foreach ($GLOBALS['Filter_Plugin_Upload_Url'] as $fpname => &$fpsignal) {
				return $fpname($this);
			}
			return $zbp->host . 'zb_users/' . $this->Dir . urlencode($this->Name);
		}
		if ($name=='Dir') {
			return 'upload/' .date('Y',$this->PostTime) . '/' . date('m',$this->PostTime) . '/';
		}
		if ($name=='FullFile') {
			return  $zbp->usersdir . $this->Dir . $this->Name;
		}
		if ($name=='Author') {
			return $zbp->GetMemberByID($this->AuthorID);
		}
		return parent::__get($name);
	}

}

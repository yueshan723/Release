<?php
/**
 * Z-Blog with PHP
 * @author
 * @copyright (C) RainbowSoft Studio
 * @version 2.0 2013-06-14
 */


class ZBlogPHP{

	private static $_zbp=null;
	public static $error_id=0;
	public static $error_file=null;
	public static $error_line=null;

	public $version=null;

	public $db = null;
	public $option = array();
	public $lang = array();
	public $path = null;
	public $host = null;
	public $cookiespath=null;
	public $guid=null;

	public $members=array();
	public $membersbyname=array();
	public $categorys=array();
	public $categorysbyorder=array();
	public $categorylayer=0;
	public $modules=array();
	public $modulesbyfilename=array();
	public $templates=array();
	public $configs=array();
	public $tags=array();
	public $tagsbyname=array();
	public $comments = array();
	public $posts=array();

	public $templatetags=array();

	public $title=null;
	public $name=null;
	public $subname=null;
	public $theme = null;
	public $style = null;

	public $user=null;
	public $cache=null;

	private $modulefunc=array();
	private $readymodules=array();
	private $readymodules_parameters=array();

	public $table=null;
	public $datainfo=null;

	public $isinitialize=false;
	public $isconnect=false;
	public $isload=false;
	public $issession=false;

	public $template = null;
	public $socialcomment = null;
	public $header = null;
	public $footer = null;

	public $themes = array();
	public $plugins = array();

	public $managecount = 50;
	public $pagebarcount = 10;
	public $searchcount = 10;
	public $displaycount = 10;
	public $commentdisplaycount = 10;

	public $sidebar =array();
	public $sidebar2=array();
	public $sidebar3=array();
	public $sidebar4=array();
	public $sidebar5=array();

	public $usersdir = null;
	public $validcodeurl = null;

	private $isgzip=false;

	static public function GetInstance(){
		if(!isset(self::$_zbp)){
			self::$_zbp=new ZBlogPHP;
		}
		return self::$_zbp;
	}

	function __construct() {

		global $option,$lang,$blogpath,$bloghost,$cookiespath,$usersdir,$table,$datainfo;
		global $blogversion,$blogtitle,$blogname,$blogsubname,$blogtheme,$blogstyle;

		ZBlogException::SetErrorHook();

		//基本配置加载到$zbp内
		$this->version=&$blogversion;
		$this->option = &$option;
		$this->lang = &$lang;
		$this->path = &$blogpath;
		$this->host = &$bloghost;
		$this->cookiespath = &$cookiespath;
		$this->usersdir = &$usersdir;

		$this->table=&$table;
		$this->datainfo=&$datainfo;

		if (trim($this->option['ZC_BLOG_CLSID'])==''){
			$this->option['ZC_BLOG_CLSID']=GetGuid();
		}

		$this->guid=&$this->option['ZC_BLOG_CLSID'];

		$this->title=&$blogtitle;
		$this->name=&$blogname;
		$this->subname=&$blogsubname;
		$this->theme=&$blogtheme;
		$this->style=&$blogstyle;

		$this->managecount=$this->option['ZC_MANAGE_COUNT'];
		$this->pagebarcount=$this->option['ZC_PAGEBAR_COUNT'];
		$this->searchcount = $this->option['ZC_SEARCH_COUNT'];
		$this->displaycount = $this->option['ZC_DISPLAY_COUNT'];
		$this->commentdisplaycount = $this->option['ZC_COMMENTS_DISPLAY_COUNT'];

		$this->cache=new Metas;

	}


	function __destruct(){
		$this->Terminate();
	}

	function __call($method, $args) {
		foreach ($GLOBALS['Filter_Plugin_Zbp_Call'] as $fpname => &$fpsignal) {
			$fpreturn=$fpname($method, $args);
			if ($fpsignal==PLUGIN_EXITSIGNAL_RETURN) {return $fpreturn;}
		}
		$this->ShowError(81,__FILE__,__LINE__);
	}

	function __set($name, $value)
	{
		foreach ($GLOBALS['Filter_Plugin_Zbp_Set'] as $fpname => &$fpsignal) {
			$fpreturn=$fpname($name, $value);
			if ($fpsignal==PLUGIN_EXITSIGNAL_RETURN) {return $fpreturn;}
		}
		$this->ShowError(81,__FILE__,__LINE__);
	}

	function __get($name)
	{
		foreach ($GLOBALS['Filter_Plugin_Zbp_Get'] as $fpname => &$fpsignal) {
			$fpreturn=$fpname($name);
			if ($fpsignal==PLUGIN_EXITSIGNAL_RETURN) {return $fpreturn;}
		}
		$this->ShowError(81,__FILE__,__LINE__);
	}



################################################################################################################


	#初始化连接
	public function Initialize(){

		if($this->option['ZC_SITE_TURNOFF']==true){
			Http503();
			$this->ShowError(82,__FILE__,__LINE__);
			return false;
		}

		if(!$this->OpenConnect())return false;

		$this->LoadConfigs();
		$this->LoadCache();
		$this->LoadOption();

		if($this->option['ZC_DEBUG_MODE']==true){
			error_reporting(-1);
		}else{
			error_reporting(0);
		}

		if($this->option['ZC_PERMANENT_DOMAIN_ENABLE']==true){
			$this->host=$this->option['ZC_BLOG_HOST'];
			$this->cookiespath=substr($this->host,strpos($this->host,'/',8));
		}else{
			$this->option['ZC_BLOG_HOST']=$this->host;
		}

		$this->option['ZC_BLOG_VERSION']=ZC_BLOG_VERSION;
		$this->option['ZC_BLOG_PRODUCT_FULL']=$this->option['ZC_BLOG_PRODUCT'] . ' ' . $this->option['ZC_BLOG_VERSION'];
		$this->option['ZC_BLOG_PRODUCT_FULLHTML']='<a href="http://www.zblogcn.com/" title="RainbowSoft Z-BlogPHP" target="_blank">' . $this->option['ZC_BLOG_PRODUCT_FULL'] . '</a>';

		date_default_timezone_set($this->option['ZC_TIME_ZONE_NAME']);

		/*if(isset($_COOKIE['timezone'])){
			$tz=GetVars('timezone','COOKIE');
			if(is_numeric($tz)){
				$tz=sprintf('%+d',-$tz);
				date_default_timezone_set('Etc/GMT' . $tz);
				$this->timezone=date_default_timezone_get();
			}
		}*/

		header('Product:' . $this->option['ZC_BLOG_PRODUCT_FULL']);

		$this->validcodeurl=$this->host . 'zb_system/script/c_validcode.php';

		#创建User类
		$this->user=new Member();

		$this->isinitialize=true;

	}


	public function Load(){

		if(!$this->isinitialize)return false;

		$this->StartGzip();

		$this->LoadMembers();

		$this->LoadCategorys();
		#$this->LoadTags();
		$this->LoadModules();

		$this->Verify();

		$this->MakeTemplatetags();

		$this->LoadTemplates();

		$this->RegBuildModule('catalog','BuildModule_catalog');

		$this->RegBuildModule('calendar','BuildModule_calendar');

		$this->RegBuildModule('comments','BuildModule_comments');

		$this->RegBuildModule('previous','BuildModule_previous');

		$this->RegBuildModule('archives','BuildModule_archives');

		$this->RegBuildModule('navbar','BuildModule_navbar');

		$this->RegBuildModule('tags','BuildModule_tags');

		$this->RegBuildModule('statistics','BuildModule_statistics');

		$this->RegBuildModule('authors','BuildModule_authors');

		foreach ($GLOBALS['Filter_Plugin_Zbp_Load'] as $fpname => &$fpsignal) $fpname();

		if($GLOBALS['manage']==true) $this->LoadManage();

		$this->isload=true;
	}

	public function LoadManage(){

		if($this->user->Status==ZC_MEMBER_STATUS_AUDITING) $this->ShowError(79,__FILE__,__LINE__);
		if($this->user->Status==ZC_MEMBER_STATUS_LOCKED) $this->ShowError(80,__FILE__,__LINE__);

		$this->CheckTemplate();

		foreach ($GLOBALS['Filter_Plugin_Zbp_LoadManage'] as $fpname => &$fpsignal) $fpname();
	}

	#终止连接，释放资源
	public function Terminate(){
		foreach ($GLOBALS['Filter_Plugin_Zbp_Terminate'] as $fpname => &$fpsignal) $fpname();

		if($this->isinitialize){
			$this->CloseConnect();
		}
	}


	public function InitializeDB($type){
		if(!trim($type))return false;
		$newtype='Db'.trim($type);
		$this->db=new $newtype();
	}

	public function OpenConnect(){

		if($this->isconnect)return false;
		if(!$this->option['ZC_DATABASE_TYPE'])return false;
		switch ($this->option['ZC_DATABASE_TYPE']) {
		case 'mysql':
		case 'pdo_mysql':
			try {
				if($this->InitializeDB($this->option['ZC_DATABASE_TYPE']))return false;
				if($this->db->Open(array(
						$this->option['ZC_MYSQL_SERVER'],
						$this->option['ZC_MYSQL_USERNAME'],
						$this->option['ZC_MYSQL_PASSWORD'],
						$this->option['ZC_MYSQL_NAME'],
						$this->option['ZC_MYSQL_PRE'],
						$this->option['ZC_MYSQL_PORT'],
						$this->option['ZC_MYSQL_PERSISTENT']
					))==false){
					$this->ShowError(67,__FILE__,__LINE__);
				}
			} catch (Exception $e) {
				throw new Exception("MySQL DateBase Connection Error.");
			}
			break;
		case 'sqlite':
		case 'sqlite3':
			try {
				$this->InitializeDB($this->option['ZC_DATABASE_TYPE']);
				if($this->db->Open(array(
					$this->usersdir . 'data/' . $this->option['ZC_SQLITE_NAME'],
					$this->option['ZC_SQLITE_PRE']
					))==false){
					$this->ShowError(69,__FILE__,__LINE__);
				}
			} catch (Exception $e) {
				throw new Exception("SQLite DateBase Connection Error.");
			}
			break;
		}
		$this->isconnect=true;
		return true;

	}

	public function CloseConnect(){
		if($this->isconnect){
			$this->db->Close();
		}
	}


	public function StartSession(){
		if($this->issession==true)return false;
		session_start();
		$this->issession=true;
		return true;
	}


	public function EndSession(){
		if($this->issession==false)return false;
		session_unset();
		session_destroy();
		$this->issession=false;
		return true;
	}


################################################################################################################
#插件用Configs表相关设置函数




	public function LoadConfigs(){

		$sql = $this->db->sql->Select($this->table['Config'],array('*'),'','','','');
		$array=$this->db->Query($sql);
		foreach ($array as $c) {
			$m=new Metas;
			$m->Unserialize($c['conf_Value']);
			$this->configs[$c['conf_Name']]=$m;
		}
	}

	public function DelConfig($name){
		$sql = $this->db->sql->Delete($this->table['Config'],array(array('=','conf_Name',$name)));
		$this->db->Delete($sql);
	}

	public function SaveConfig($name){

		if(!isset($this->configs[$name]))return false;

		$kv=array('conf_Name'=>$name,'conf_Value'=>$this->configs[$name]->Serialize());
		$sql = $this->db->sql->Select($this->table['Config'],array('*'),array(array('=','conf_Name',$name)),'','','');
		$array=$this->db->Query($sql);

		if(count($array)==0){
			$k=array('conf_Name','conf_Value');
			$v=array($name,$this->configs[$name]->Serialize());
			$sql = $this->db->sql->Insert($this->table['Config'],$kv);
			$this->db->Insert($sql);
		}else{
			array_shift($kv);
			$sql = $this->db->sql->Update($this->table['Config'],$kv,array(array('=','conf_Name',$name)));
			$this->db->Update($sql);
		}
	}

	public function Config($name){
		if(!isset($this->configs[$name])){
			$m=new Metas;
			$this->configs[$name]=$m;
		}
		return $this->configs[$name];
	}






################################################################################################################
#Cache相关


	public function SaveCache(){

		#$s=$this->usersdir . 'cache/' . $this->guid . '.cache';
		#$c=serialize($this->cache);
		#@file_put_contents($s, $c);

		//$this->configs['cache']=$this->cache;
		$this->SaveConfig('cache');

	}

	public function LoadCache(){
		#$s=$this->usersdir . 'cache/' . $this->guid . '.cache';
		#if (file_exists($s))
		#{
		#	$this->cache=unserialize(@file_get_contents($s));
		#}
		$this->cache=$this->Config('cache');
	}







################################################################################################################
#保存zbp设置函数



	public function SaveOption(){

		$this->option['ZC_BLOG_CLSID']=$this->guid;

		if( strpos('|SAE|BAE2|ACE|', '|'.$this->option['ZC_YUN_SITE'].'|')===false ){
			$s="<?php\r\n";
			$s.="return ";
			$s.=var_export($this->option,true);
			$s.="\r\n?>";
			@file_put_contents($this->usersdir . 'c_option.php',$s);
		}

		foreach ($this->option as $key => $value) {
			$this->Config('system')->$key = $value;
		}
		$this->SaveConfig('system');

		$this->cache->refesh=time();
		$this->SaveCache();
	}



	public function LoadOption(){

		$array=$this->Config('system')->Data;

		if(empty($array))return false;
		if(!is_array($array))return false;
		foreach ($array as $key => $value) {
			//if($key=='ZC_PERMANENT_DOMAIN_ENABLE')continue;
			//if($key=='ZC_BLOG_HOST')continue;
			//if($key=='ZC_BLOG_CLSID')continue;
			if($key=='ZC_YUN_SITE')continue;
			if($key=='ZC_BLOG_LANGUAGEPACK')continue;
			if($key=='ZC_DATABASE_TYPE')continue;
			if($key=='ZC_SQLITE_NAME')continue;
			if($key=='ZC_SQLITE_PRE')continue;
			if($key=='ZC_MYSQL_SERVER')continue;
			if($key=='ZC_MYSQL_USERNAME')continue;
			if($key=='ZC_MYSQL_PASSWORD')continue;
			if($key=='ZC_MYSQL_NAME')continue;
			if($key=='ZC_MYSQL_CHARSET')continue;
			if($key=='ZC_MYSQL_PRE')continue;
			if($key=='ZC_MYSQL_ENGINE')continue;
			if($key=='ZC_MYSQL_PORT')continue;
			if($key=='ZC_MYSQL_PERSISTENT')continue;
			$this->option[$key]=$value;
		}

	}







################################################################################################################
#权限及验证类




	function CheckRights($action){

		foreach ($GLOBALS['Filter_Plugin_Zbp_CheckRights'] as $fpname => &$fpsignal) {
			$fpreturn=$fpname($action);
			if ($fpsignal==PLUGIN_EXITSIGNAL_RETURN) {return $fpreturn;}
		}
		if(!isset($GLOBALS['actions'][$action])){
			if(is_numeric($action)){
				if ($this->user->Level > $action) {
					return false;
				} else {
					return true;
				}
			}
		}else{
			if ($this->user->Level > $GLOBALS['actions'][$action]) {
				return false;
			} else {
				return true;
			}
		}
	}

	function CheckRightsByLevel($level,$action){

		foreach ($GLOBALS['Filter_Plugin_Zbp_CheckRightsByLevel'] as $fpname => &$fpsignal) {
			$fpreturn=$fpname($level,$action);
			if ($fpsignal==PLUGIN_EXITSIGNAL_RETURN) {return $fpreturn;}
		}

		if(is_int($action)){
			if ($level > $action) {
				return false;
			} else {
				return true;
			}
		}

		if ($level > $GLOBALS['actions'][$action]) {
			return false;
		} else {
			return true;
		}

	}

	public function Verify(){
		return $this->Verify_MD5Path(GetVars('username','COOKIE'),GetVars('password','COOKIE'));
	}

	public function Verify_MD5Path($name,$ps_and_path){
		if (isset($this->membersbyname[$name])){
			$m=$this->membersbyname[$name];
			if(md5($m->Password . $this->guid) == $ps_and_path){
				$this->user=$m;
				return true;
			}else{
				return false;
			}
		}
	}

	public function Verify_MD5($name,$md5pw){
		if (isset($this->membersbyname[$name])){
			$m=$this->membersbyname[$name];
			return $this->Verify_Final($name,md5($md5pw . $m->Guid));
		}else{
			return false;
		}
	}

	public function Verify_Original($name,$originalpw){
		return $this->Verify_MD5($name,md5($originalpw));
	}

	public function Verify_Final($name,$password){
		if (isset($this->membersbyname[$name])){
			$m=$this->membersbyname[$name];
			if($m->Password == $password){
				$this->user=$m;
				return true;
			}else{
				return false;
			}
		}
	}









################################################################################################################
#
function BuildModule(){

	foreach ($GLOBALS['Filter_Plugin_Zbp_BuildModule'] as $fpname => &$fpsignal)$fpname();

	foreach ($this->readymodules as $modfilename) {
		if(isset($this->modulesbyfilename[$modfilename])){
			if(isset($this->modulefunc[$modfilename])){
				$m=$this->modulesbyfilename[$modfilename];
				if(function_exists($this->modulefunc[$modfilename])){
					if(!isset($this->readymodules_parameters[$modfilename])){
						$m->Content=call_user_func($this->modulefunc[$modfilename]);
					}else{
						$m->Content=call_user_func($this->modulefunc[$modfilename],$this->readymodules_parameters[$modfilename]);
					}
				}
				$m->Save();
			}
		}
	}

}

function RegBuildModule($modfilename,$userfunc){
	$this->modulefunc[$modfilename]=$userfunc;
}

function AddBuildModule($modfilename,$parameters=null){
	$this->readymodules[$modfilename]=$modfilename;
	$this->readymodules_parameters[$modfilename]=$parameters;
}

function DelBuildModule($modfilename){
	unset($this->readymodules[$modfilename]);
	unset($this->readymodules_parameters[$modfilename]);
}

function AddBuildModuleAll(){
	foreach ($this->modulesbyfilename as $key => $value) {
		$this->readymodules[$key]=$key;
	}
}






################################################################################################################
#加载函数




	public function LoadMembers(){

		$array=$this->GetMemberList();
		foreach ($array as $m) {
			$this->members[$m->ID]=$m;
			$this->membersbyname[$m->Name]=&$this->members[$m->ID];
		}
	}

	public function LoadCategorys(){

		$lv0=array();
		$lv1=array();
		$lv2=array();
		$lv3=array();
		$array=$this->GetCategoryList(null,null,array('cate_Order'=>'ASC'),null,null);
		if(count($array)==0)return false;
		foreach ($array as $c) {
			$this->categorys[$c->ID]=$c;
		}
		foreach ($this->categorys as $id=>$c) {
			$l='lv' . $c->Level;
			${$l}[$c->ParentID][]=$id;
		}

		if(count($lv0)>0)$this->categorylayer=1;
		if(count($lv1)>0)$this->categorylayer=2;
		if(count($lv2)>0)$this->categorylayer=3;
		if(count($lv3)>0)$this->categorylayer=4;

		foreach ($lv0[0] as $id0) {
			$this->categorysbyorder[$id0]=&$this->categorys[$id0];
			if(!isset($lv1[$id0])){continue;}
			foreach ($lv1[$id0] as $id1) {
				if($this->categorys[$id1]->ParentID==$id0){
					$this->categorys[$id0]->SubCategorys[]=$this->categorys[$id1];
					$this->categorysbyorder[$id1]=&$this->categorys[$id1];
					if(!isset($lv2[$id1])){continue;}
					foreach ($lv2[$id1] as $id2) {
						if($this->categorys[$id2]->ParentID==$id1){
							$this->categorys[$id0]->SubCategorys[]=$this->categorys[$id2];
							$this->categorys[$id1]->SubCategorys[]=$this->categorys[$id2];
							$this->categorysbyorder[$id2]=&$this->categorys[$id2];
							if(!isset($lv3[$id2])){continue;}
							foreach ($lv3[$id2] as $id3) {
								if($this->categorys[$id3]->ParentID==$id2){
									$this->categorys[$id0]->SubCategorys[]=$this->categorys[$id3];
									$this->categorys[$id1]->SubCategorys[]=$this->categorys[$id3];
									$this->categorys[$id2]->SubCategorys[]=$this->categorys[$id3];
									$this->categorysbyorder[$id3]=&$this->categorys[$id3];
								}
							}
						}
					}
				}
			}
		}
	}

	public function LoadTags(){

		$array=$this->GetTagList();
		foreach ($array as $t) {
			$this->tags[$t->ID]=$t;
			$this->tagsbyname[$t->Name]=&$this->tags[$t->ID];
		}

	}

	public function LoadModules(){

		$array=$this->GetModuleList();
		foreach ($array as $m) {
			$this->modules[]=$m;

			$this->modulesbyfilename[$m->FileName]=$m;
		}

		$dir=$this->usersdir . 'theme/' . $this->theme . '/include/';
		if(!file_exists($dir))return null;
		$files=GetFilesInDir($dir,'php');
		foreach ($files as $sortname => $fullname) {
			$m=new Module();
			$m->FileName=$sortname;
			$m->Content=file_get_contents($fullname);
			$m->Type='div';
			$m->Source='theme';
			$this->modules[]=$m;
			$this->modulesbyfilename[$m->FileName]=$m;
		}

	}

	public function LoadThemes(){
		$dirs=GetDirsInDir($this->usersdir . 'theme/');

		foreach ($dirs as $id) {
			$app = new App;
			if($app->LoadInfoByXml('theme',$id)==true){
				$this->themes[]=$app;
			}
		}

	}

	public function LoadPlugins(){
		$dirs=GetDirsInDir($this->usersdir . 'plugin/');

		foreach ($dirs as $id) {
			$app = new App;
			if($app->LoadInfoByXml('plugin',$id)==true){
				$this->plugins[]=$app;
			}
		}

	}

	public function LoadApp($type,$id){
		$app = new App;
		$app->LoadInfoByXml($type,$id);
		return $app;
	}



################################################################################################################
#模板相关函数




	public function MakeTemplatetags(){

		$this->templatetags=array();

		$option=$this->option;
		unset($option['ZC_BLOG_CLSID']);
		unset($option['ZC_SQLITE_NAME']);
		unset($option['ZC_SQLITE3_NAME']);
		unset($option['ZC_MYSQL_USERNAME']);
		unset($option['ZC_MYSQL_PASSWORD']);
		unset($option['ZC_MYSQL_NAME']);

		$this->templatetags['zbp']=&$this;
		$this->templatetags['user']=&$this->user;
		$this->templatetags['option']=&$option;
		$this->templatetags['categorys']=&$this->categorys;
		$this->templatetags['modules']=&$this->modulesbyfilename;
		$this->templatetags['title']=htmlspecialchars($this->title);
		$this->templatetags['host']=$this->host;
		$this->templatetags['path']=$this->path;
		$this->templatetags['cookiespath']=$this->cookiespath;
		$this->templatetags['name']=htmlspecialchars($this->name);
		$this->templatetags['subname']=htmlspecialchars($this->subname);
		$this->templatetags['theme']=&$this->theme;
		$this->templatetags['style']=&$this->style;
		$this->templatetags['language']=$this->option['ZC_BLOG_LANGUAGE'];
		$this->templatetags['copyright']=$this->option['ZC_BLOG_COPYRIGHT'];
		$this->templatetags['zblogphp']=$this->option['ZC_BLOG_PRODUCT_FULL'];
		$this->templatetags['zblogphphtml']=$this->option['ZC_BLOG_PRODUCT_FULLHTML'];
		$this->templatetags['feedurl']=$this->host . 'feed.php';
		$this->templatetags['type']='';
		$this->templatetags['page']='';
		$this->templatetags['socialcomment']=&$this->socialcomment;
		$this->templatetags['header']=&$this->header;
		$this->templatetags['footer']=&$this->footer;
		$this->templatetags['validcodeurl']=&$this->validcodeurl;

		$s=array(
			$option['ZC_SIDEBAR_ORDER'],
			$option['ZC_SIDEBAR2_ORDER'],
			$option['ZC_SIDEBAR3_ORDER'],
			$option['ZC_SIDEBAR4_ORDER'],
			$option['ZC_SIDEBAR5_ORDER']
		);
		foreach ($s as $k =>$v) {
			$a=explode('|', $v);
			$ms=array();
			foreach ($a as $v2) {
				if(isset($this->modulesbyfilename[$v2])){
					$m=$this->modulesbyfilename[$v2];
					$ms[]=$m;
				}
			}
			//reset($ms);
			$s='sidebar' . ($k==0?'':$k+1);
			$this->$s=$ms;
			$ms=null;
		}
		$this->templatetags['sidebar']=&$this->sidebar;
		$this->templatetags['sidebar2']=&$this->sidebar2;
		$this->templatetags['sidebar3']=&$this->sidebar3;
		$this->templatetags['sidebar4']=&$this->sidebar4;
		$this->templatetags['sidebar5']=&$this->sidebar5;

		//创建模板类
		$this->template = new Template();
		$this->template->SetPath($this->usersdir . 'theme/'. $this->theme .'/compile/');
		$this->template->tags = $this->templatetags;

		foreach ($GLOBALS['Filter_Plugin_Zbp_MakeTemplatetags'] as $fpname => &$fpsignal) {
			$fpreturn=$fpname($this->template);
		}

	}

	public function LoadTemplates(){

		$this->templates=array();

		#先读默认的
		$dir=$this->path .'zb_system/defend/default/';
		$files=GetFilesInDir($dir,'php');
		foreach ($files as $sortname => $fullname) {
			$this->templates[$sortname]=file_get_contents($fullname);
		}
		#再读当前的
		$dir=$this->usersdir .'theme/' . $this->theme . '/template/';
		$files=GetFilesInDir($dir,'php');
		foreach ($files as $sortname => $fullname) {
			$this->templates[$sortname]=file_get_contents($fullname);
		}
		if(!isset($this->templates['sidebar2'])){
			$this->templates['sidebar2']=str_replace('$sidebar', '$sidebar2', $this->templates['sidebar']);
		}
		if(!isset($this->templates['sidebar3'])){
			$this->templates['sidebar3']=str_replace('$sidebar', '$sidebar3', $this->templates['sidebar']);
		}
		if(!isset($this->templates['sidebar4'])){
			$this->templates['sidebar4']=str_replace('$sidebar', '$sidebar4', $this->templates['sidebar']);
		}
		if(!isset($this->templates['sidebar5'])){
			$this->templates['sidebar5']=str_replace('$sidebar', '$sidebar5', $this->templates['sidebar']);
		}
	}

	public function BuildTemplate(){

		if( strpos('|SAE|BAE2|ACE|', '|'.$this->option['ZC_YUN_SITE'].'|')!==false )return false;
		//初始化模板
		$this->LoadTemplates();

		if(strpos($this->templates['comments'], 'AjaxCommentBegin')===false)
			$this->templates['comments']='<label id="AjaxCommentBegin"></label>' . $this->templates['comments'];

		if(strpos($this->templates['comments'], 'AjaxCommentEnd')===false)
			$this->templates['comments']=$this->templates['comments'] . '<label id="AjaxCommentEnd"></label>';

		if(strpos($this->templates['comment'], 'id="cmt{$comment->ID}"')===false&&strpos($this->templates['comment'], 'id=\'cmt{$comment->ID}\'')===false){
			$this->templates['comment']='<label id="cmt{$comment->ID}"></label>'. $this->templates['comment'];
		}

		if(strpos($this->templates['commentpost'], 'inpVerify')===false){
			$verify='{if $option[\'ZC_COMMENT_VERIFY_ENABLE\'] && !$user.ID}<p><input type="text" name="inpVerify" id="inpVerify" class="text" value="" size="28" tabindex="4" /> <label for="inpVerify">'.$this->lang['msg']['validcode'].'(*)</label><img style="width:{$option[\'ZC_VERIFYCODE_WIDTH\']}px;height:{$option[\'ZC_VERIFYCODE_HEIGHT\']}px;cursor:pointer;" src="{$article.ValidCodeUrl}" alt="" title="" onclick="javascript:this.src=\'{$article.ValidCodeUrl}&amp;tm=\'+Math.random();"/></p>{/if}';

			if(strpos($this->templates['commentpost'], '<!--verify-->')!==false){
				$this->templates['commentpost']=str_replace('<!--verify-->',$verify,$this->templates['commentpost']);
			}elseif(strpos($this->templates['commentpost'], '</form>')!==false){
				$this->templates['commentpost']=str_replace('</form>',$verify.'</form>',$this->templates['commentpost']);
			}
			else{
				$this->templates['commentpost'] .= $verify;
			}
		}

		if(strpos($this->templates['header'], '{$header}')===false){
			if(strpos($this->templates['header'], '</head>')!==false){
				$this->templates['header']=str_replace('</head>','</head>' . '{$header}',$this->templates['header']);
			}elseif(strpos($this->templates['header'], '</ head>')!==false){
				$this->templates['header']=str_replace('</ head>','</ head>' . '{$header}',$this->templates['header']);
			}else{
				$this->templates['header'] .= '{$header}';
			}
		}

		if(strpos($this->templates['footer'], '{$footer}')===false){
			if(strpos($this->templates['footer'], '</body>')!==false){
				$this->templates['footer']=str_replace('</body>','{$footer}' . '</body>',$this->templates['footer']);
			}elseif(strpos($this->templates['footer'], '</ body>')!==false){
				$this->templates['footer']=str_replace('</ body>','{$footer}' . '</ body>',$this->templates['footer']);
			}elseif(strpos($this->templates['footer'], '</html>')!==false){
				$this->templates['footer']=str_replace('</html>','{$footer}' . '</html>',$this->templates['footer']);
			}elseif(strpos($this->templates['footer'], '</ html>')!==false){
				$this->templates['footer']=str_replace('</ html>','{$footer}' . '</ html>',$this->templates['footer']);
			}else{
				$this->templates['footer'] = '{$footer}' . $this->templates['footer'];
			}
		}

		$dir=$this->usersdir . 'theme/'. $this->theme .'/compile/';

		if(!file_exists($dir)){
			@mkdir($dir,0777,true);
		}

		$files2=array();
		foreach ($this->templates as $name=>$file) {
			$files2[]=$dir . $name . '.php';
		}

		//清空目标目录
		$files = GetFilesInDir($dir,'php');

		$files3 = array_diff($files,$files2);

		foreach ($files3 as $fullname) {
			@unlink($fullname);
		}

		//创建模板类
		$this->template = new Template();
		$this->template->SetPath($dir);

		//模板接口
		foreach ($GLOBALS['Filter_Plugin_Zbp_BuildTemplate'] as $fpname => &$fpsignal) {$fpname($this->templates);}

		$this->template->CompileFiles($this->templates);

		$this->cache->refesh=time();
		$this->SaveCache();

	}

	public function CheckTemplate(){
		$s=implode($this->templates);
		$md5=md5($s);
		if($md5!=$this->cache->templates_md5){
			$this->BuildTemplate();
			$this->cache->templates_md5=$md5;
			$this->SaveCache();
		}
	}





################################################################################################################
#加载数据对像List函数


	function GetListCustom($table,$datainfo,$sql){

		$array=null;
		$list=array();
		$array=$this->db->Query($sql);
		if(!isset($array)){return array();}
		foreach ($array as $a) {
			$l=new Base($table,$datainfo);
			$l->LoadInfoByAssoc($a);
			$list[]=$l;
		}
		return $list;
	}



	function GetList($type,$sql){

		$array=null;
		$list=array();
		$array=$this->db->Query($sql);
		if(!isset($array)){return array();}
		foreach ($array as $a) {
			$l=new $type();
			$l->LoadInfoByAssoc($a);
			$list[]=$l;
		}
		return $list;
	}

	function GetPostList($select=null,$where=null,$order=null,$limit=null,$option=null){

		if(empty($select)){$select = array('*');}
		if(empty($where)){$where = array();}
		$sql = $this->db->sql->Select($this->table['Post'],$select,$where,$order,$limit,$option);
		return $this->GetList('Post',$sql);

	}

	function GetArticleList($select=null,$where=null,$order=null,$limit=null,$option=null,$readtags=true){

		if(empty($select)){$select = array('*');}
		if(empty($where)){$where = array();}
		array_unshift($where,array('=','log_Type','0'));
		$sql = $this->db->sql->Select($this->table['Post'],$select,$where,$order,$limit,$option);
		$array = $this->GetList('Post',$sql);

		if($readtags){
			$tagstring = '';
			foreach ($array as $a) {
				$tagstring .= $a->Tag;
				$this->posts[$a->ID]=$a;
			}
			$this->LoadTagsByIDString($tagstring);
		}

		return $array;

	}

	function GetPageList($select=null,$where=null,$order=null,$limit=null,$option=null){

		if(empty($select)){$select = array('*');}
		if(empty($where)){$where = array();}
		array_unshift($where,array('=','log_Type','1'));
		$sql = $this->db->sql->Select($this->table['Post'],$select,$where,$order,$limit,$option);
		$array = $this->GetList('Post',$sql);
		foreach ($array as $a) {
			$this->posts[$a->ID]=$a;
		}
		return $array;

	}

	function GetCommentList($select=null,$where=null,$order=null,$limit=null,$option=null){

		if(empty($select)){$select = array('*');}
		$sql = $this->db->sql->Select($this->table['Comment'],$select,$where,$order,$limit,$option);
		$array=$this->GetList('Comment',$sql);
		foreach ($array as $comment) {
			$this->comments[$comment->ID]=$comment;
		}
		return $array;

	}

	function GetMemberList($select=null,$where=null,$order=null,$limit=null,$option=null){

		if(empty($select)){$select = array('*');}
		$sql = $this->db->sql->Select($this->table['Member'],$select,$where,$order,$limit,$option);
		return $this->GetList('Member',$sql);

	}

	function GetTagList($select=null,$where=null,$order=null,$limit=null,$option=null){

		if(empty($select)){$select = array('*');}
		$sql = $this->db->sql->Select($this->table['Tag'],$select,$where,$order,$limit,$option);
		return $this->GetList('Tag',$sql);

	}

	function GetCategoryList($select=null,$where=null,$order=null,$limit=null,$option=null){

		if(empty($select)){$select = array('*');}
		$sql = $this->db->sql->Select($this->table['Category'],$select,$where,$order,$limit,$option);
		return $this->GetList('Category',$sql);

	}

	function GetModuleList($select=null,$where=null,$order=null,$limit=null,$option=null){

		if(empty($select)){$select = array('*');}
		$sql = $this->db->sql->Select($this->table['Module'],$select,$where,$order,$limit,$option);
		return $this->GetList('Module',$sql);
	}

	function GetUploadList($select=null,$where=null,$order=null,$limit=null,$option=null){

		if(empty($select)){$select = array('*');}
		$sql = $this->db->sql->Select($this->table['Upload'],$select,$where,$order,$limit,$option);
		return $this->GetList('Upload',$sql);
	}

	function GetCounterList($select=null,$where=null,$order=null,$limit=null,$option=null){

		if(empty($select)){$select = array('*');}
		$sql = $this->db->sql->Select($this->table['Counter'],$select,$where,$order,$limit,$option);
		return $this->GetList('Counter',$sql);
	}





################################################################################################################
#读取对象函数


	function GetPostByID($id){
		if($id==0)return new Post;
		if(isset($this->posts[$id])){
			return $this->posts[$id];
		}else{
			$p = new Post;
			$p->LoadInfoByID($id);
			$this->posts[$id]=$p;
			return $p;
		}
	}

	function GetCategoryByID($id){
		if(isset($this->categorys[$id])){
			return $this->categorys[$id];
		}else{
			return new Category;
		}
	}

	function GetCategoryByName($name){
		$name=trim($name);
		foreach ($this->categorys as $key => &$value) {
			if($value->Name==$name){
				return $value;
			}
		}
		return new Category;
	}

	function GetCategoryByAliasOrName($name){
		$name=trim($name);
		foreach ($this->categorys as $key => &$value) {
			if(($value->Name==$name)||($value->Alias==$name)){
				return $value;
			}
		}
		return new Category;
	}

	function GetModuleByID($id){
		if($id==0){
			$m = new Module;
			return $m;
		}else{
			foreach ($this->modules as $key => $value) {
				if($value->ID==$id)return $value;
			}
			$m = new Module;
			return $m;
		}
	}

	function GetMemberByID($id){
		if(isset($this->members[$id])){
			return $this->members[$id];
		}
		$m = new Member;
		$m->Guid=GetGuid();
		return $m;
	}

	function GetMemberByAliasOrName($name){
		$name=trim($name);
		foreach ($this->members as $key => &$value) {
			if(($value->Name==$name)||($value->Alias==$name)){
				return $value;
			}
		}
		return new Member;
	}

	function GetCommentByID($id){
		if(isset($this->comments[$id])){
			return $this->comments[$id];
		}else{
			$c = new Comment;
			if($id==0){
				return $c;
			}else{
				$c->LoadInfoByID($id);
				$this->comments[$id]=$c;
				return $c;
			}
		}
	}

	function GetUploadByID($id){
		$m = new Upload;
		if($id>0){
			$m->LoadInfoByID($id);
		}
		return $m;
	}

	function GetCounterByID($id){
		$m = new Counter;
		if($id>0){
			$m->LoadInfoByID($id);
		}
		return $m;
	}

	function GetTagByAliasOrName($name){
		$a=array();
		$a[]=array('tag_Alias',$name);
		$a[]=array('tag_Name',$name);
		$array=$this->GetTagList('',array(array('array',$a)),'',array(1),'');
		if(count($array)==0){
			return new Tag;
		}else{
			$this->tags[$array[0]->ID]=$array[0];
			$this->tagsbyname[$array[0]->ID]=&$this->tags[$array[0]->ID];
			return $this->tags[$array[0]->ID];
		}
	}

	function GetTagByID($id){
		if(isset($this->tags[$id])){
			return $this->tags[$id];
		}else{
			$array=$this->GetTagList('',array(array('=','tag_ID',$id)),'',array(1),'');
			if(count($array)==0){
				return new Tag;
			}else{
				$this->tags[$array[0]->ID]=$array[0];
				$this->tagsbyname[$array[0]->ID]=&$this->tags[$array[0]->ID];
				return $this->tags[$array[0]->ID];
			}

		}
	}

	#load tags '{1}{2}{3}{4}{4}'
	function LoadTagsByIDString($s){
		if($s=='')return array();
		$s=str_replace('}{', '|', $s);
		$s=str_replace('{', '', $s);
		$s=str_replace('}', '', $s);
		$a=explode('|', $s);
		$b=array();
		foreach ($a as &$value) {
			$value = trim($value);
			if($value)$b[]=$value;
		}
		$t=array_unique($b);

		if(count($t)==0)return array();

		$a=array();
		$b=array();
		$c=array();
		foreach ($t as $v) {
			if(isset($this->tags[$v])==false){
				$a[]=array('tag_ID',$v);
				$c[]=$v;
			}else{
				$b[$v]=&$this->tags[$v];
			}
		}

		if(count($a)==0){
			return $b;
		}else{
			$t=array();
			//$array=$this->GetTagList('',array(array('array',$a)),'','','');
			$array=$this->GetTagList('',array(array('IN','tag_ID',$c)),'','','');
			foreach ($array as $v) {
				$this->tags[$v->ID]=$v;
				$this->tagsbyname[$v->Name]=&$this->tags[$v->ID];
				$t[$v->ID]=&$this->tags[$v->ID];
			}
			return $b+$t;
		}
	}
	#load tags 'aaa,bbb,ccc,ddd'
	function LoadTagsByNameString($s){
		$s=str_replace(';', ',', $s);
		$s=str_replace('，', ',', $s);
		$s=str_replace('、', ',', $s);
		$s=trim($s);
		$s=strip_tags($s);
		if($s=='')return array();
		if($s==',')return array();
		$a=explode(',', $s);
		$t=array_unique($a);

		if(count($t)==0)return array();

		$a=array();
		$b=array();
		foreach ($t as $v) {
			if(isset($this->tagsbyname[$v])==false){
				$a[]=array('tag_Name',$v);
			}else{
				$b[$v]=&$this->tagsbyname[$v];
			}
		}

		if(count($a)==0){
			return $b;
		}else{
			$t=array();
			$array=$this->GetTagList('',array(array('array',$a)),'','','');
			foreach ($array as $v) {
				$this->tags[$v->ID]=$v;
				$this->tagsbyname[$v->Name]=&$this->tags[$v->ID];
				$t[$v->Name]=&$this->tags[$v->ID];
			}
			return $b+$t;
		}
	}







################################################################################################################
#杂项
	function VerifyCmtKey($id,$key){
		$nowkey=md5($this->guid . $id . date('Y-m-d'));
		$nowkey2=md5($this->guid . $id . date('Y-m-d',time()-(3600*24)));
		if($key==$nowkey||$key==$nowkey2){
			return true;
		}
	}

	function CheckPlugin($name){
		$s=$this->option['ZC_BLOG_THEME'] . '|' . $this->option['ZC_USING_PLUGIN_LIST'];
		return HasNameInString($s,$name);
	}

	#$type=category,tag,page,item
	function AddItemToNavbar($type='item',$id,$name,$url){

		if(!$type)$type='item';
		$m=$this->modulesbyfilename['navbar'];
		$s=$m->Content;

		$a='<li id="navbar-'.$type.'-'.$id.'"><a href="'.$url.'">'.$name.'</a></li>';

		if($this->CheckItemToNavbar($type,$id)){
			$s=preg_replace('/<li id="navbar-'.$type.'-'.$id.'">.*?<\/li>/', $a, $s);
		}else{
			$s.='<li id="navbar-'.$type.'-'.$id.'"><a href="'.$url.'">'.$name.'</a></li>';
		}


		$m->Content=$s;
		$m->Save();

	}

	function DelItemToNavbar($type='item',$id){

		if(!$type)$type='item';
		$m=$this->modulesbyfilename['navbar'];
		$s=$m->Content;

		$s=preg_replace('/<li id="navbar-'.$type.'-'.$id.'">.*?<\/li>/', '', $s);

		$m->Content=$s;
		$m->Save();

	}

	function CheckItemToNavbar($type='item',$id){

		if(!$type)$type='item';
		$m=$this->modulesbyfilename['navbar'];
		$s=$m->Content;
		return (bool)strpos($s,'id="navbar-'.$type.'-'.$id.'"');

	}

	#$signal = good,bad,tips
	private $hint1=null,$hint2=null,$hint3=null,$hint4=null,$hint5=null;
	function SetHint($signal,$content=''){
		if($content==''){
			if($signal=='good')$content=$this->lang['msg']['operation_succeed'];
			if($signal=='bad')$content=$this->lang['msg']['operation_failed'];
		}
		if($this->hint1==null){
			$this->hint1=$signal . '|' . $content;
			setcookie("hint_signal1", $signal . '|' . $content,time()+3600,$this->cookiespath);
		}elseif($this->hint2==null){
			$this->hint2=$signal . '|' . $content;
			setcookie("hint_signal2", $signal . '|' . $content,time()+3600,$this->cookiespath);
		}elseif($this->hint3==null){
			$this->hint3=$signal . '|' . $content;
			setcookie("hint_signal3", $signal . '|' . $content,time()+3600,$this->cookiespath);
		}elseif($this->hint4==null){
			$this->hint4=$signal . '|' . $content;
			setcookie("hint_signal4", $signal . '|' . $content,time()+3600,$this->cookiespath);
		}elseif($this->hint5==null){
			$this->hint5=$signal . '|' . $content;
			setcookie("hint_signal5", $signal . '|' . $content,time()+3600,$this->cookiespath);
		}
	}

	function GetHint(){
		for ($i = 1; $i <= 5; $i++) {
			$signal=GetVars('hint_signal' . $i,'COOKIE');
			if($signal){
				$a=explode('|', $signal);
				$this->ShowHint($a[0],$a[1]);
				setcookie("hint_signal" . $i , '',time()-3600,$this->cookiespath);
			}
		}
	}

	function ShowHint($signal,$content=''){
		if($content==''){
			if($signal=='good')$content=$this->lang['msg']['operation_succeed'];
			if($signal=='bad')$content=$this->lang['msg']['operation_failed'];
		}
		echo "<div class='hint'><p class='hint hint_$signal'>$content</p></div>";
	}

	function ShowError($idortext,$file=null,$line=null){

		if((int)$idortext==2){
			Http404();
		}

		self::$error_id=(int)$idortext;
		self::$error_file=$file;
		self::$error_line=$line;

		if(is_numeric($idortext))$idortext=$this->lang['error'][$idortext];

		foreach ($GLOBALS['Filter_Plugin_Zbp_ShowError'] as $fpname => &$fpsignal) {
			$fpreturn=$fpname($idortext,$file,$line);
			if ($fpsignal==PLUGIN_EXITSIGNAL_RETURN) {return $fpreturn;}
		}

		throw new Exception($idortext);
	}


	function GetToken(){
		return md5($this->guid . date('Ymd') . $this->user->Name . $this->user->Password);
	}

	function ValidToken($t){
		if($t==md5($this->guid . date('Ymd') . $this->user->Name . $this->user->Password)){
			return true;
		}
		if($t==md5($this->guid . date('Ymd',strtotime("-1 day")) . $this->user->Name . $this->user->Password)){
			return true;
		}
		return false;
	}


	function ShowValidCode($id=''){

		foreach ($GLOBALS['Filter_Plugin_Zbp_ShowValidCode'] as $fpname => &$fpsignal) {
			return $fpname($id);//*
		}

		$_vc = new ValidateCode();
		$_vc->GetImg();
		setcookie('zbpvalidcode' . md5($this->guid . $id), md5( $this->guid . date("Ymd") . $_vc->GetCode() ), null,$this->cookiespath);
	}


	function CheckValidCode($vaidcode,$id=''){

		foreach ($GLOBALS['Filter_Plugin_Zbp_CheckValidCode'] as $fpname => &$fpsignal) {
			return $fpname($vaidcode,$id);//*
		}

		$original=GetVars('zbpvalidcode' . md5($this->guid . $id),'COOKIE');
		if(md5( $this->guid . date("Ymd") . $vaidcode)==$original) return true;
	}


	function CheckGzip(){
		$this->isgzip=true;
	}

	function StartGzip(){
		if($this->isgzip&&isset($this->option['ZC_GZIP_ENABLE'])&&$this->option['ZC_GZIP_ENABLE'])
		if(!headers_sent()&&extension_loaded("zlib")&&isset($_SERVER["HTTP_ACCEPT_ENCODING"])&&strstr($_SERVER["HTTP_ACCEPT_ENCODING"],"gzip")){
			ob_clean();
			ob_start('ob_gzhandler');
			//ini_set('zlib.output_compression', 'On');
			//ini_set('zlib.output_compression_level', '9');
		}
	}


	function  RedirectInstall($yun=false){
		if(!$yun){
			if(!$this->option['ZC_DATABASE_TYPE']){Redirect('./zb_install/');}
		}else{
			if($this->option['ZC_YUN_SITE']){
				if($this->Config('system')->CountItem()==0){Redirect('./zb_install/');}
			}
		}
	}

}
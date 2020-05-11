<?php
/**
 * 模块类
 *
 * @package Z-BlogPHP
 * @subpackage ClassLib/Module 类库
 */
class Module extends Base {

    /**
     * 构造函数
     */
    public function __construct() {
        global $zbp;
        parent::__construct($zbp->table['Module'], $zbp->datainfo['Module'], __CLASS__);
    }

    /**
     * 设置参数值
     * @param string $name
     * @param mixed $value
     * @return null
     */
    public function __set($name, $value) {
        global $zbp;
        if ($name == 'SourceType') {
            return null;
        }
        if ($name == 'NoRefresh') {
            if ((bool) $value) {
                $this->Metas->norefresh = (bool) $value;
            } else {
                $this->Metas->Del('norefresh');
            }

            return null;
        }
        parent::__set($name, $value);
    }

    /**
     * 获取参数值
     * @param $name
     * @return bool|mixed|string
     */
    public function __get($name) {
        global $zbp;
        if ($name == 'SourceType') {
            if ($this->Source == 'system') {
                return 'system';
            } elseif ($this->Source == 'user') {
                return 'user';
            } elseif ($this->Source == 'theme') {
                return 'theme';
            } elseif ($this->Source == 'plugin_' . $zbp->theme) {
                return 'theme';
            } else {
                return 'plugin';
            }
        }
        if ($name == 'NoRefresh') {
            return (bool) $this->Metas->norefresh;
        }
        if ($name == 'Name' && $this->Source == 'system') {
            switch ($this->FileName) {
                case 'calendar':
                    return $zbp->lang['msg']['calendar'];
                case 'controlpanel':
                    return $zbp->lang['msg']['control_panel'];
                case 'searchpanel':
                    return $zbp->lang['msg']['search'];
                default:
                    return $zbp->lang['msg']['module_'.$this->FileName];
            }
        }

        return parent::__get($name);
    }

    /**
     * @return bool
     */
    public function Save() {
        global $zbp;

        $this->Content = str_replace($zbp->host, '{$host}', $this->Content);

        foreach ($GLOBALS['hooks']['Filter_Plugin_Module_Save'] as $fpname => &$fpsignal) {
            $fpsignal = PLUGIN_EXITSIGNAL_NONE;
            $fpreturn = $fpname($this);
            if ($fpsignal == PLUGIN_EXITSIGNAL_RETURN) {return $fpreturn;}
        }
        if ($this->Source == 'theme') {
            if (!$this->FileName) {
                return true;
            }

            $c = $this->Content;
            $d = $zbp->usersdir . 'theme/' . $zbp->theme . '/include/';
            $f = $d . $this->FileName . '.php';
            if (!file_exists($d)) {
                @mkdir($d, 0755);
            }
            @file_put_contents($f, $c);

            return true;
        }
        //return parent::Save();
        //防Module重复保存的机制
        $m=$zbp->GetListType('Module',
                    $zbp->db->sql->get()->select($zbp->table['Module'])
                    ->where(array('=', $zbp->datainfo['Module']['FileName'][0], $this->FileName))
                    ->sql
                );
        if(count($m)<1){
            return parent::Save();
        }else{
            if($this->ID==0){
                return false;
            }
            return parent::Save();
        }
    }

    /**
     * @return bool
     */
    public function Del() {
        global $zbp;
        foreach ($GLOBALS['hooks']['Filter_Plugin_Module_Del'] as $fpname => &$fpsignal) {
            $fpsignal = PLUGIN_EXITSIGNAL_NONE;
            $fpreturn = $fpname($this);
            if ($fpsignal == PLUGIN_EXITSIGNAL_RETURN) {return $fpreturn;}
        }
        if ($this->Source == 'theme') {
            if (!$this->FileName) {
                return true;
            }

            $f = $zbp->usersdir . 'theme/' . $zbp->theme . '/include/' . $this->FileName . '.php';
            if (file_exists($f)) {
                @unlink($f);
            }

            return true;
        }

        return parent::Del();
    }

    public function Build() {

        if ($this->NoRefresh == true) {
            return;
        }

        if (isset(ModuleBuilder::$List[$this->FileName])) {
            if(isset(ModuleBuilder::$List[$this->FileName]['function'])){
                if(isset(ModuleBuilder::$List[$this->FileName]['parameters'])){
                    $this->Content = call_user_func(ModuleBuilder::$List[$this->FileName]['function'], ModuleBuilder::$List[$this->FileName]['parameters']);
                }else{
                    $this->Content = call_user_func(ModuleBuilder::$List[$this->FileName]['function']);
                }
           
            }

        }
    }

}

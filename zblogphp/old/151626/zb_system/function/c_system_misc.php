<?php
/**
 * 系统信息
 * @package Z-BlogPHP
 * @subpackage System\Misc 摘取信息
 * @copyright (C) RainbowSoft Studio
 */

ob_clean();

$type = GetVars('type', 'GET');

foreach ($GLOBALS['hooks']['Filter_Plugin_Misc_Begin'] as $fpname => &$fpsignal) {$fpname($type);}

switch ($type) {
case 'statistic':
    if (!$zbp->CheckRights('root')) {
        echo $zbp->ShowError(6, __FILE__, __LINE__);
        die();
    }
    misc_statistic();
    break;
case 'updateinfo':
    if (!$zbp->CheckRights('root')) {
        echo $zbp->ShowError(6, __FILE__, __LINE__);
        die();
    }
    misc_updateinfo();
    break;
case 'showtags':
    if (!$zbp->CheckRights('ArticleEdt')) {
        Http404();
        die();
    }
    misc_showtags();
    break;
case 'vrs':
    if (!$zbp->CheckRights('misc')) {
        $zbp->ShowError(6, __FILE__, __LINE__);
    }
    misc_viewrights();
    break;
case 'phpinfo':
    if (!$zbp->CheckRights('root')) {
        echo $zbp->ShowError(6, __FILE__, __LINE__);
        die();
    }
    misc_phpinfo();
    break;
default:
    break;
}

function misc_updateinfo() {

    global $zbp;

    $r = GetHttpContent($zbp->option['ZC_UPDATE_INFO_URL']);

    $r = '<tr><td>' . $r . '</td></tr>';

    $zbp->LoadConfigs();
    $zbp->LoadCache();
    $zbp->cache->reload_updateinfo = $r;
    $zbp->cache->reload_updateinfo_time = time();
    $zbp->SaveCache();

    echo $r;
}

function misc_statistic() {

    global $zbp;

    $zbp->BuildTemplate();

    $r = null;

    CountNormalArticleNums();
    CountTopArticle(null, null);
    CountCommentNums(null, null);
    $all_comments = $zbp->cache->all_comment_nums;

    $xmlrpc_address = $zbp->xmlrpcurl;
    $current_member = $zbp->user->Name;
    $current_version = ZC_VERSION_FULL;
    $all_artiles = GetValueInArrayByCurrent($zbp->db->Query('SELECT COUNT(*) AS num FROM ' . $GLOBALS['table']['Post'] . ' WHERE log_Type=\'0\''), 'num');
    $all_pages = GetValueInArrayByCurrent($zbp->db->Query('SELECT COUNT(*) AS num FROM ' . $GLOBALS['table']['Post'] . ' WHERE log_Type=\'1\''), 'num');
    $all_categorys = GetValueInArrayByCurrent($zbp->db->Query('SELECT COUNT(*) AS num FROM ' . $GLOBALS['table']['Category']), 'num');
    $all_views = $zbp->option['ZC_VIEWNUMS_TURNOFF'] == true ? 0 : GetValueInArrayByCurrent($zbp->db->Query('SELECT SUM(log_ViewNums) AS num FROM ' . $GLOBALS['table']['Post']), 'num');
    $all_tags = GetValueInArrayByCurrent($zbp->db->Query('SELECT COUNT(*) as num FROM ' . $GLOBALS['table']['Tag']), 'num');
    $all_members = GetValueInArrayByCurrent($zbp->db->Query('SELECT COUNT(*) AS num FROM ' . $GLOBALS['table']['Member']), 'num');
    $current_theme = '{$zbp->theme}';
    $current_style = '{$zbp->style}';
    $current_member = '{$zbp->user->Name}';
    $system_environment = '{$system_environment}';

    if ($zbp->option['ZC_DEBUG_MODE']) {
        $r .= "<tr><td colspan='4' style='text-align: center'>{$zbp->lang['msg']['debugging_warning']}</td></tr>";
    }
    $r .= "<tr><td class='td20'>{$zbp->lang['msg']['current_member']}</td><td class='td30'><a href='../cmd.php?act=misc&type=vrs' target='_blank'>{$current_member}</a></td><td class='td20'>{$zbp->lang['msg']['current_version']}</td><td class='td30'>{$current_version}</td></tr>";
    $r .= "<tr><td class='td20'>{$zbp->lang['msg']['all_artiles']}</td><td>{$all_artiles}</td><td>{$zbp->lang['msg']['all_categorys']}</td><td>{$all_categorys}</td></tr>";
    $r .= "<tr><td class='td20'>{$zbp->lang['msg']['all_pages']}</td><td>{$all_pages}</td><td>{$zbp->lang['msg']['all_tags']}</td><td>{$all_tags}</td></tr>";
    $r .= "<tr><td class='td20'>{$zbp->lang['msg']['all_comments']}</td><td>{$all_comments}</td><td>{$zbp->lang['msg']['all_views']}</td><td>{$all_views}</td></tr>";
    $r .= "<tr><td class='td20'>{$zbp->lang['msg']['current_theme']} / {$zbp->lang['msg']['current_style']}</td><td>{$current_theme}/{$current_style}</td><td>{$zbp->lang['msg']['all_members']}</td><td>{$all_members}</td></tr>";
    $r .= "<tr><td class='td20'>{$zbp->lang['msg']['xmlrpc_address']}</td><td>{$xmlrpc_address}</td><td>{$zbp->lang['msg']['system_environment']}</td><td><a href='../cmd.php?act=misc&type=phpinfo' target='_blank'>{$system_environment}</a></td></tr>";
    $r .= "<script type=\"text/javascript\">$('#statistic').attr('title','" . date("c", $zbp->cache->reload_statistic_time) . "');</script>";

    $zbp->cache->reload_statistic = $r;
    $zbp->cache->reload_statistic_time = time();
    $zbp->cache->system_environment = GetEnvironment();
    $zbp->cache->all_article_nums = $all_artiles;
    $zbp->cache->all_page_nums = $all_pages;

    $zbp->AddBuildModule('statistics', array($all_artiles, $all_pages, $all_categorys, $all_tags, $all_views, $all_comments));
    $zbp->BuildModule();
    $zbp->SaveCache();

    $r = str_replace('{#ZC_BLOG_HOST#}', $zbp->host, $r);
    $r = str_replace('{$zbp->user->Name}', $zbp->user->Name, $r);
    $r = str_replace('{$zbp->theme}', $zbp->theme, $r);
    $r = str_replace('{$zbp->style}', $zbp->style, $r);
    $r = str_replace('{$system_environment}', $zbp->cache->system_environment, $r);

    echo $r;

}

function misc_showtags() {
    global $zbp;

    header('Content-Type: application/x-javascript; Charset=utf-8');

    echo '$("#ajaxtags").html("';

    $array = $zbp->GetTagList(null, null, array('tag_Count' => 'DESC', 'tag_ID' => 'ASC'), array(100), null);
    if (count($array) > 0) {
        $t = array();
        foreach ($array as $tag) {
            echo '<a href=\"#\">' . $tag->Name . '</a>';
        }
    }

    echo '");$("#ulTag").tagTo("#edtTag");';
}

function misc_viewrights() {
    global $zbp, $blogtitle;

    $blogtitle = $zbp->name . '-' . $zbp->lang['msg']['view_rights'];
    ?><!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<?php if (strpos(GetVars('HTTP_USER_AGENT', 'SERVER'), 'Trident/')) {?>
		<meta http-equiv="X-UA-Compatible" content="IE=EDGE"/>
	<?php }?>
	<meta name="robots" content="none"/>
	<meta name="generator" content="<?php echo $GLOBALS['option']['ZC_BLOG_PRODUCT_FULL'] ?>"/>
	<link rel="stylesheet" href="css/admin.css" type="text/css" media="screen"/>
	<script src="script/common.js" type="text/javascript"></script>
	<script src="script/c_admin_js_add.php" type="text/javascript"></script>
<?php
foreach ($GLOBALS['hooks']['Filter_Plugin_Other_Header'] as $fpname => &$fpsignal) {$fpname();}
    ?>
	<title><?php echo $blogtitle; ?></title>
</head>
<body class="short">
<div class="bg">
	<div id="wrapper">
		<div class="logo"><img src="image/admin/none.gif" title="Z-BlogPHP" alt="Z-BlogPHP"/></div>
		<div class="login">
			<form method="post" action="#">
				<dl>
					<dt><?php echo $zbp->lang['msg']['current_member'] . ' : <b>' . $zbp->user->Name; ?></b><br/>
						<?php echo $zbp->lang['msg']['member_level'] . ' : <b>' . $zbp->user->LevelName; ?></b></dt>
					<?php
foreach ($GLOBALS['actions'] as $key => $value) {
        if ($GLOBALS['zbp']->CheckRights($key)) {
            echo '<dd><b>' . $zbp->GetAction_Title($key) . '</b> : ' . ($zbp->CheckRights($key) ? '<span style="color:green">true</span>' : '<span style="color:red">false</span>') . '</dd>';
        }
    }
    ?>
				</dl>
			</form>
		</div>
	</div>
</div>
</body>
</html>
<?php
RunTime();
}

function misc_phpinfo() {
    global $zbp, $blogtitle;
    $match = array();
    $blogtitle = $zbp->name . '-phpinfo';
    ob_start();
    phpinfo();
    $s = ob_get_clean();

    if (PHP_ENGINE !== ENGINE_HHVM) {
        preg_match("/<body.*?>(.*?)<\/body>/is", $s, $match);
    }

    ?><!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<?php if (strpos(GetVars('HTTP_USER_AGENT', 'SERVER'), 'Trident/')) {?>
		<meta http-equiv="X-UA-Compatible" content="IE=EDGE"/>
	<?php }?>
	<meta name="robots" content="none"/>
	<meta name="generator" content="<?php echo $GLOBALS['option']['ZC_BLOG_PRODUCT_FULL'] ?>"/>
	<link rel="stylesheet" href="css/admin.css" type="text/css" media="screen"/>
	<script src="script/common.js" type="text/javascript"></script>
	<script src="script/c_admin_js_add.php" type="text/javascript"></script>
<?php
foreach ($GLOBALS['hooks']['Filter_Plugin_Other_Header'] as $fpname => &$fpsignal) {$fpname();}
    ?>
	<title><?php echo $blogtitle; ?></title>
	<style type="text/css">
*{color:#000;}
pre {margin: 0; font-family: monospace;}
a:link {color: #009; text-decoration: none; background-color: #fff;}
a:hover {text-decoration: underline;}
table {border-collapse: collapse; border: 0; width: 934px; box-shadow: 1px 2px 3px #ccc;}
.center {text-align: center;}
.center table {margin: 1em auto; text-align: left;}
.center th {text-align: center !important;}
td, th {border: 1px solid #666; font-size: 75%; vertical-align: baseline; padding: 4px 5px;}
h1 {font-size: 150%;}
h2 {font-size: 125%;}
.p {text-align: left;}
.e {background-color: #ccf; width: 300px; font-weight: bold;}
.h {background-color: #99c; font-weight: bold;}
.v {background-color: #ddd; max-width: 300px; overflow-x: auto;}
.v i {color: #999;}
img {float: right; border: 0;}
hr {display:none;}
div.bg {background: #777bb4!important;}
	</style>
</head>
<body class="short">
<div class="bg">
	<div id="wrapper">
		<div class="logo"><img src="image/admin/none.gif" title="Z-BlogPHP" alt="Z-BlogPHP"/></div>
		<?php
if (PHP_ENGINE === ENGINE_HHVM) {
        echo '<p style="text-align: center;">' . GetEnvironment() . '</p>';
    } else {
        echo $match[0];
    }?>
	</div>
</div>
</body>
</html>
<?php
RunTime();
}

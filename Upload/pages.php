<?php
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'pages.php');

require_once "global.php";

if (!$db->table_exists('pages')) redirect('index.php');

$pagesquery = $db->query("
    SELECT *
    FROM " . TABLE_PREFIX . "pages
");

if ($db->num_rows($pagesquery) < 1) redirect('index.php');

$pid = isset($_REQUEST['pid']) ? $_REQUEST['pid'] : false;

if (!$pid) {
    redirect('index.php');
}

while ($custompage = $db->fetch_array($pagesquery)) {
    $slug = $custompage['slug'];
    $name = $custompage['name'];

    if ($pid == $slug) {
        add_breadcrumb($name);

        eval("\$page = \"".$templates->get("pages_" . $slug . "")."\";");
        output_page($page);
    }
}

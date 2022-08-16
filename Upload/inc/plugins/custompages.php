<?php

if (!defined("IN_MYBB")) die("Direct initialization of this file is not allowed.");

function custompages_info() {
    global $lang;
    $lang->load('custompages');

    return array(
        "name"          => "Custom Pages",
        "description"   => $lang->plugin_desc,
        "website"       => "https://github.com/venomnomous/custom-pages",
        "author"        => "Venomous",
        "authorsite"    => "https://github.com/venomnomous",
        "version"       => "1.0",
        "codename"      => "custompages",
        "compatibility" => "18*"
    );
}

function custompages_install() {
    global $db, $lang;
    $lang->load('custompages');

    $db->query("CREATE TABLE " . TABLE_PREFIX . "pages (
        `pid` int(10) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `slug` varchar(255) NOT NULL,
        PRIMARY KEY (`pid`),
        KEY `pid` (`pid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1");

    $template_group1 = array(
        'prefix' => $db->escape_string("pages"),
        'title' => $db->escape_string($lang->template_title1),
        'isdefault' => 0
    );

    $db->insert_query('templategroups', $template_group1);
}

function custompages_is_installed() {
    global $db;

    if ($db->table_exists("pages")) return true;
    return false;
}

function custompages_uninstall() {
    global $db;

    if ($db->table_exists("pages")) $db->drop_table("pages");

    $db->delete_query("templategroups", "prefix = 'pages'");
    $db->delete_query("templates", "title LIKE 'pages%'");
}

$plugins->add_hook("admin_config_action_handler", "pages_admin_config_action_handler");
function pages_admin_config_action_handler(&$actions) {
    $actions['pages'] = array('active' => 'pages', 'file' => 'pages');
}

$plugins->add_hook("admin_config_permissions", "pages_admin_config_permissions");
function pages_admin_config_permissions(&$admin_permissions) {
    global $lang;
    $lang->load('custompages');

    $admin_permissions['pages'] = $lang->acp_pages_settings;
}

$plugins->add_hook("admin_config_menu", "pages_admin_config_menu");
function pages_admin_config_menu(&$sub_menu) {
    global $lang;
    $lang->load('custompages');

    $sub_menu[] = [
        "id" => "pages",
        "title" => $lang->acp_pages,
        "link" => "index.php?module=config-pages"
    ];
}

$plugins->add_hook("admin_load", "pages_load");
function pages_load() {
    global $mybb, $db, $lang, $page, $run_module, $action_file, $errors;
    $lang->load('custompages');

    if ($page->active_action != 'pages')  return false;

    if ($run_module == 'config' && $action_file == 'pages') {
        if ($mybb->input['action'] == "" || !isset($mybb->input['action'])) {
            $page->add_breadcrumb_item($lang->acp_pages);
            $page->output_header($lang->acp_pages);

            $sub_tabs['pages'] = [
                "title" => $lang->acp_pages,
                "link" => "index.php?module=config-pages",
                "description" => $lang->acp_pages_desc
            ];
            $sub_tabs['pages_add'] = [
                "title" => $lang->acp_pages_new,
                "link" => "index.php?module=config-pages&amp;action=add_page",
                "description" => $lang->acp_pages_new_desc
            ];
            $page->output_nav_tabs($sub_tabs, 'pages');

            if (isset($errors))  $page->output_inline_error($errors);

            $form = new Form("index.php?module=config-pages", "post");
            $form_container = new FormContainer($lang->acp_pages);
            $form_container->output_row_header($lang->acp_pages_fields_name);
            $form_container->output_row_header($lang->acp_pages_fields_slug);
            $form_container->output_row_header($lang->acp_pages_fields_options);

            $pagequery = $db->simple_select("pages", "*", "", ["order_by" => 'pid', 'order_dir' => 'ASC']);
            while ($custompage = $db->fetch_array($pagequery)) {
                $form_container->output_cell('<strong>'.htmlspecialchars_uni($custompage['name']).'</strong>');
                $form_container->output_cell('<em>'.htmlspecialchars_uni($custompage['slug']).'</em>');
                $popup = new PopupMenu("pages_{$custompage['pid']}", $lang->acp_pages_edit);
                $popup->add_item(
                    $lang->acp_pages_edit,
                    "index.php?module=config-pages&amp;action=edit_page&amp;pid={$custompage['pid']}"
                );
                $popup->add_item(
                    $lang->acp_pages_delete,
                    "index.php?module=config-pages&amp;action=delete_page&amp;pid={$custompage['pid']}"
                    ."&amp;my_post_key={$mybb->post_code}"
                );
                $form_container->output_cell($popup->fetch(), array("class" => "align_center"));
                $form_container->construct_row();
            }

            $form_container->end();
            $form->end();
            $page->output_footer();
            exit;
        }

        if ($mybb->input['action'] == "add_page") {
            if ($mybb->request_method == "post") {
                if (empty($mybb->input['name'])) $errors[] = $lang->acp_pages_error1;

                if (empty($errors)) {
                    $name = $db->escape_string($mybb->input['name']);

                    $searchArr = array('/ß/','/Ä/','/Ö/','/Ü/','/ä/','/ö/','/ü/', '/\s+/');
                    $replaceArr = array('sz','Ae','Oe','Ue','ae','oe','ue', '');
                    $slugValue = strtolower(preg_replace($searchArr, $replaceArr, $mybb->input['name']));
                    $slug = $db->escape_string(preg_replace("/[^0-9a-zA-Z-]/", "", $slugValue));

                    add_page($name, $slug);

                    $mybb->input['module'] = "pages";
                    $mybb->input['action'] = $lang->acp_pages_success;
                    log_admin_action(htmlspecialchars_uni($mybb->input['name']));

                    flash_message($lang->acp_pages_success, 'success');
                    admin_redirect("index.php?module=config-pages");
                }
            }

            $page->add_breadcrumb_item($lang->acp_pages_add);
            $page->output_header($lang->acp_pages);

            $sub_tabs['pages'] = [
                "title" => $lang->acp_pages,
                "link" => "index.php?module=config-pages",
                "description" => $lang->acp_pages_desc
            ];
            $sub_tabs['pages_add'] = [
                "title" => $lang->acp_pages_new,
                "link" => "index.php?module=config-pages&amp;action=add_page",
                "description" => $lang->acp_pages_new_desc
            ];
            $page->output_nav_tabs($sub_tabs, 'pages_add');

            if(isset($errors)) $page->output_inline_error($errors);

            $form = new Form("index.php?module=config-pages&amp;action=add_page", "post", "", 1);
            $form_container = new FormContainer($lang->acp_pages_add);
            $form_container->output_row($lang->acp_pages_fields_name . "<em>*</em>", $lang->acp_pages_fields_name_desc, $form->generate_text_box('name', $mybb->input['name']));

            $searchArr = array('/ß/','/Ä/','/Ö/','/Ü/','/ä/','/ö/','/ü/', '/\s+/');
            $replaceArr = array('sz','Ae','Oe','Ue','ae','oe','ue', '');
            $slugValue = strtolower(preg_replace($searchArr, $replaceArr, $mybb->input['name']));

            $form_container->output_row("<em>" . $lang->acp_pages_fields_slug . "</em>", $lang->acp_pages_fields_slug_desc, $form->generate_hidden_field('slug', preg_replace("/[^0-9a-zA-Z-]/", "", $slugValue)));

            $form_container->end();
            $buttons[] = $form->generate_submit_button($lang->acp_pages_add);
            $form->output_submit_wrapper($buttons);
            $form->end();
            $page->output_footer();
            exit;
        }

        if ($mybb->input['action'] == "edit_page") {
            if ($mybb->request_method == "post") {
                if (empty($mybb->input['name'])) $errors[] = $lang->acp_pages_error1;

                if (empty($errors)) {
                    $pid = $mybb->get_input('pid', MyBB::INPUT_INT);

                    $name = $db->escape_string($mybb->input['name']);

                    $searchArr = array('/ß/','/Ä/','/Ö/','/Ü/','/ä/','/ö/','/ü/', '/\s+/');
                    $replaceArr = array('sz','Ae','Oe','Ue','ae','oe','ue', '');
                    $slugValue = strtolower(preg_replace($searchArr, $replaceArr, $mybb->input['name']));
                    $slug = $db->escape_string(preg_replace("/[^0-9a-zA-Z-]/", "", $slugValue));

                    edit_page($pid, $name, $slug);

                    $mybb->input['module'] = "pages";
                    $mybb->input['action'] = $lang->acp_pages_success2;
                    log_admin_action(htmlspecialchars_uni($mybb->input['name']));

                    flash_message($lang->acp_pages_success2, 'success');
                    admin_redirect("index.php?module=config-pages");
                }
            }
            
            $page->add_breadcrumb_item($lang->acp_pages_edit);
            $page->output_header($lang->acp_pages);

            $sub_tabs['pages'] = [
                "title" => $lang->acp_pages,
                "link" => "index.php?module=config-pages",
                "description" => $lang->acp_pages_desc
            ];
            $page->output_nav_tabs($sub_tabs, 'pages'); 

            if(isset($errors))  $page->output_inline_error($errors);

            $pid = $mybb->get_input('pid', MyBB::INPUT_INT);
            $pagesquery = $db->simple_select("pages", "*", "pid={$pid}");
            $custompage = $db->fetch_array($pagesquery);

            $form = new Form("index.php?module=config-pages&amp;action=edit_page", "post", "", 1);
            echo $form->generate_hidden_field('pid', $pid);
            $form_container = new FormContainer($lang->acp_pages_edit);
            $form_container->output_row($lang->acp_pages_fields_name . "<em>*</em>", $lang->acp_pages_fields_name_desc, $form->generate_text_box('name', htmlspecialchars_uni($custompage['name'])));
            $form_container->output_row("<em>" . $lang->acp_pages_fields_slug . "</em>", $lang->acp_pages_fields_slug_desc, $form->generate_hidden_field('slug', htmlspecialchars_uni($custompage['slug'])));
            $form_container->end();
            $buttons[] = $form->generate_submit_button($lang->acp_pages_edit);
            $form->output_submit_wrapper($buttons);
            $form->end();
            $page->output_footer();
            exit;
        }

        if ($mybb->input['action'] == "delete_page") {
            $pid = $mybb->get_input('pid', MyBB::INPUT_INT);
            $pagequery = $db->simple_select("pages", "*", "pid={$pid}");
            $custompage = $db->fetch_array($pagequery);

            if (empty($pid)) {
                flash_message($lang->acp_pages_error2, 'error');
                admin_redirect("index.php?module=config-pages");
            }
            if (isset($mybb->input['no']) && $mybb->input['no']) admin_redirect("index.php?module=config-pages");
            if (!verify_post_check($mybb->input['my_post_key'])) {
                flash_message($lang->invalid_post_verify_key2, 'error');
                admin_redirect("index.php?module=config-pages");
            }
            else {
                if ($mybb->request_method == "post") {
                    delete_page($pid);

                    $mybb->input['module'] = "pages";
                    $mybb->input['action'] = $lang->acp_pages_success3;
                    log_admin_action(htmlspecialchars_uni($custompage['name']));

                    flash_message($lang->acp_pages_success3, 'success');
                    admin_redirect("index.php?module=config-pages");
                }
                else {
                    $page->output_confirm_action(
                        "index.php?module=config-pages&amp;action=delete_page&amp;lid={$pid}",
                        $lang->acp_pages_delete
                    );
                }
            }
            exit;
        }
    }
}

function add_page($name, $slug) {
    global $db;
    require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';

    $new_record = [
        "name" => $name,
        "slug" => $slug
    ];

    $db->insert_query("pages", $new_record);

    page_templates_add($slug);
}

function edit_page($pid, $name) {
    global $db;
    require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';

    $new_record = [
        "name" => $name,
        "slug" => $slug
    ];

    $db->update_query("pages", $new_record, "pid = '$pid'");
}

function delete_page($pid) {
    global $db;
    require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';

    $custompage = $db->fetch_array($db->simple_select("pages", "*", "pid = '$pid'"));
    $slug = $custompage['slug'];

    $db->delete_query("pages", "pid = '$pid'");
    $db->delete_query("templates", "title LIKE 'pages_" . $slug . "%'");
}

function page_templates_add($slug) {
    global $db;

    $templates[] = array(
        'title' => 'pages_' . $slug,
        'template' => $db->escape_string('
<html>
    <head>
        <title>{$mybb->settings[\'bbname\']} - {$name}</title>
        {$headerinclude}
    </head>
    <body>
        {$header}
        <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
            <tr>
                <td class="thead"><strong>{$name}</strong></td>
            </tr>
            <tr>
                <td class="trow1" valign="top">
                    Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.
                </td>
            </tr>
        </table>
        {$footer}
    </body>
</html>'),
    'sid' => '-2',
        'version' => '',
        'dateline' => time()
    );

    $db->insert_query_multiple("templates", $templates);
}

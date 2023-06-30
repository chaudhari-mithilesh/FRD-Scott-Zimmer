<?php
/* Plugin Name: FRD Compatibility Tables
 * Plugin URI: https://wisdmlabs.com/
 * Version: 1.1.5
 * Description: This plugin import FRD data.
 * Author: Wisdmlabs
 * Author URI: http://wisdmlabs.com/
* */

add_action('wdm_plugin_activated', 'wdm_plugin_activation');

add_action('admin_enqueue_scripts', 'enqueueScripts');

function enqueueScripts()
{
    wp_enqueue_script('jquery-ui-tabs');
    wp_enqueue_style('jquery-ui-css', plugin_dir_url(__FILE__) . 'css/jquery-ui.css');
    wp_enqueue_style('sweetalert-css', plugin_dir_url(__FILE__) . 'css/sweetalert.css');
    wp_enqueue_style('datatable-css', plugin_dir_url(__FILE__) . 'css/jquery.dataTables.min.css');
    // wp_enqueue_style('dataTables_themeroller-css', plugin_dir_url(__FILE__) . 'css/jquery.dataTables_themeroller.css');
    wp_enqueue_style('wdm_style-css', plugin_dir_url(__FILE__) . 'css/style.css');
    wp_enqueue_script('datatable-js', plugins_url('/js/jquery.dataTables.min.js', __FILE__));
    wp_enqueue_script('wdm-importer-js', plugins_url('/js/wdm_import.js', __FILE__), array('jquery'));
    wp_enqueue_script('sweetalert-js', plugins_url('/js/sweetalert.min.js', __FILE__));
    wp_enqueue_script('modernizr-js', plugins_url('/js/modernizr.js', __FILE__));
    wp_localize_script('wdm-importer-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    wp_enqueue_style('jquery-select2-css-backend', plugin_dir_url(__FILE__) . 'css/select2.min.css');
    wp_enqueue_script('wdm-select2-js', plugins_url('/js/select2.min.js', __FILE__), array('jquery'));
}
add_action('wp_enqueue_scripts', 'enqueueFrontendScripts');
function enqueueFrontendScripts()
{
    wp_enqueue_style('jquery-select2-css', plugin_dir_url(__FILE__) . 'css/select2.min.css');
    wp_enqueue_style('wdm_style-css', plugin_dir_url(__FILE__) . 'css/front_style.css');
    wp_enqueue_script('wdm-filter-js', plugins_url('/js/wdm_filter.js', __FILE__), array('jquery'));
    wp_enqueue_script('wdm-select2-js', plugins_url('/js/select2.js', __FILE__));
    wp_localize_script('wdm-filter-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}
function wdm_plugin_activate()
{
    do_action('wdm_plugin_activated');
}
register_activation_hook(__FILE__, 'wdm_plugin_activate');

function wdm_plugin_activation()
{
    global $wpdb;
    if (!function_exists('dbDelta')) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    }

    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wdm_posts (
                    id int(10) NOT NULL AUTO_INCREMENT,
                    name varchar(200) NOT NULL,
                    type varchar(200) NOT NULL,
                    manufacturer int(11),
                    category int(11),
                    sort_order int(10),
                    PRIMARY KEY (id)
                )");
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wdm_categories (
                    id int(10) NOT NULL AUTO_INCREMENT,
                    name varchar(200) NOT NULL,
                    type varchar(200) NOT NULL,
                    PRIMARY KEY (id)
                )");

    $wpdb->query("CREATE VIEW frd_product AS SELECT post.id as id, post.name as pname, cat.name as cname, post.sort_order as sort_order
                FROM {$wpdb->prefix}wdm_posts AS post 
                JOIN {$wpdb->prefix}wdm_categories AS cat 
                ON post.category=cat.id WHERE post.type like 'frd_product'");

    $wpdb->query("CREATE VIEW manufacturer AS SELECT id, name FROM {$wpdb->prefix}wdm_categories WHERE type like 'manufacturer'");

    $wpdb->query("CREATE VIEW carrier_model AS SELECT post.id as id, post.name as pname, cat.name as cname, man.name as manufacturer
                FROM {$wpdb->prefix}wdm_posts AS post 
                JOIN {$wpdb->prefix}wdm_categories AS cat 
                ON post.category=cat.id
                JOIN manufacturer as man ON post.manufacturer=man.id
                WHERE post.type like 'c_model'");

    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wdm_compatibility (
                    id int(10) NOT NULL AUTO_INCREMENT,
                    model_id int(10),
                    frd_id int(10),
                    breaker_performance varchar(50),
                    auto_coupler varchar(50),
                    install_kit varchar(50),
                    discontinued varchar(50),
                    PRIMARY KEY (id)
                )");
}

add_action('admin_menu', 'addImportSubmenu');

function addImportSubmenu()
{
    add_submenu_page(
        'tools.php',
        'Import FRD Data',
        'Import FRD Data',
        'manage_options',
        'import-frd',
        'wdmImportPage'
    );
}

function wdmImportPage()
{
    $display_none='';
    ?>
    <div class="se-pre-con">
        <div>
            <div class="rect1"></div>
            <div class="rect2"></div>
            <div class="rect3"></div>
            <div class="rect4"></div>
            <div class="rect5"></div>
        </div>
    </div>
    <div id="tabs">
      <ul>
        <li><a href="#tabs-1">Import FRD Data</a></li>
        <li><a href="#tabs-2">View FRD Data</a></li>
      </ul>
      <div id="tabs-1">
        <?php
        if (isset($_POST['wdm_submit'])) {
            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'] . '/wdm_temp';
            if (! is_dir($upload_dir)) {
                mkdir($upload_dir);
            }
            move_uploaded_file($_FILES["wdm_import"]["tmp_name"], $upload_dir.'/'.$_FILES["wdm_import"]['name']);
            ini_set("auto_detect_line_endings", true);
            $wdm_fp=file($upload_dir.'/'.$_FILES["wdm_import"]['name']);
            ?>
            <div class="wdm_loader" data-file_name="<?php echo $_FILES['wdm_import']['name'];
            ?>" data-count="<?php echo count($wdm_fp);
            ?>"></div>
            <div class="loding_img" style="display: none;"></div>
            <div class="loader" style="display: none;">
                    <div class="progress-bar"><div class="progress-stripes"></div><div class="percentage">0%</div></div>
            </div>

            <span>Importing...</span>
            <?php
            $display_none='style="display:none"';
        }
    ?>
            <form method="post" enctype="multipart/form-data" <?php echo $display_none;
    ?> >
                <div>
                    <div class="wrap">
                        <h1>Import FRD Data</h1>
                        <p>Upload the .csv file containing FRD data and click import to import the data</p>
                        <input type="file" name="wdm_import" accept=".csv">
                        <input type="submit" name="wdm_submit" value="import">
                    </div>
                    <input type="button" class="wdm_reset" value="Delete All Data">
                </div>
            </form>
      </div>
      <div id="tabs-2">
        <?php
        if (!isset($_POST['wdm_submit'])) {
            ?>
        <div id="display_page_popup">
        </div>
        <table id="comp_table">
            <thead>
              <th>Carrier Manufacturer</th>
              <th>Carrier Type</th>
              <th>Carrier Model</th>
              <th>FRD Product</th>
              <th>FRD Type</th>
              <th>Breaker Performance</th>
              <th>Auto Coupler</th>
              <th>Install Kit</th>
              <th>Discontinued</th>
            </thead>
            <tbody>
                <?php
                displayTable();
            ?>
            </tbody>
        </table>
        <?php
        }
    ?>
      </div>
    </div>
    <?php
}

function displayTable()
{
    global $wpdb;
    $rows=$wpdb->get_results('SELECT car.manufacturer as manufacturer, car.cname AS crrier_type, car.pname AS c_model,
                             frd.pname As frd_product, frd.cname As frd_type,
                             comp.breaker_performance, comp.auto_coupler, comp.install_kit, comp.discontinued
                             FROM '.$wpdb->prefix.'wdm_compatibility As comp
                             JOIN carrier_model AS car ON comp.model_id=car.id
                             LEFT JOIN frd_product As frd ON comp.frd_id=frd.id
                             ');
    //var_dump($rows);
    foreach ($rows as $value) {
        echo '<tr>';
        echo '<td>'.$value->manufacturer;
        echo "</td><td>".$value->crrier_type;
        echo "</td><td>".$value->c_model;
        echo "</td><td>".$value->frd_product;
        echo "</td><td>".$value->frd_type;
        echo "</td><td>".$value->breaker_performance;
        echo "</td><td>".$value->auto_coupler;
        echo "</td><td>".$value->install_kit;
        echo "</td><td>".$value->discontinued;
        echo "</td></tr>";
    }
}

add_action('wp_ajax_wdm_import_data', 'wdmImportData');

function wdmImportData()
{
    global $wpdb;
    $upload_dir = wp_upload_dir();
    ini_set("auto_detect_line_endings", true);
    $file=fopen($upload_dir['basedir'].'/wdm_temp/'.$_POST['file'], 'r');
    // fseek($file, $_POST['start']);
    $wdm_j=0;
    while ($wdm_j<$_POST['start']) {
        $row=fgetcsv($file);
        $wdm_j++;
    }
    $wdm_i=0;
    while (!feof($file) && $wdm_i<1000) {
        $wdm_i++;
        $row=fgetcsv($file);
        // if (isset($row[0]) && isset($row[1]) && isset($row[2]) && isset($row[3]) && isset($row[4])) {
        if (!empty($row[0]) && !empty($row[1]) && !empty($row[2])) {
            $manufacturer=wdmGetCategoryID($row[0], "manufacturer");
            $c_type=wdmGetCategoryID($row[1], "carrier");
            $frd_type=wdmGetCategoryID($row[4], "frd");
            $order = isset($row[9]) ? $row[9] : 0;
            $c_model=wdmGetPostID($row[2], 0, 'c_model', $c_type, $manufacturer);
            $frd_product=wdmGetPostID($row[3], $order, 'frd_product', $frd_type);
            if ($frd_product!=0) {
                $exists=$wpdb->get_var('SELECT id FROM '.$wpdb->prefix.'wdm_compatibility WHERE model_id='.$c_model.' AND frd_id='.$frd_product);
            } elseif (!empty($row[6])) {
                $exists=$wpdb->get_var('SELECT id FROM '.$wpdb->prefix.'wdm_compatibility WHERE model_id='.$c_model.' AND frd_id='.$frd_product.' AND auto_coupler <> ""');
            } elseif (!empty($row[7])) {
                $exists=$wpdb->get_var('SELECT id FROM '.$wpdb->prefix.'wdm_compatibility WHERE model_id='.$c_model.' AND frd_id='.$frd_product.' AND install_kit <> ""');
            }
            if (!$exists) {
                $wpdb->insert($wpdb->prefix.'wdm_compatibility', array(
                'model_id' => $c_model,
                'frd_id' => $frd_product,
                'breaker_performance' => isset($row[5]) ? $row[5] : 0,
                'auto_coupler' => isset($row[6]) ? $row[6] : '',
                'install_kit' => isset($row[7]) ? $row[7] : 0,
                'discontinued' => isset($row[8]) ? strtolower($row[8]) : ''
                ));
            } else {
                $wpdb->update(
                    $wpdb->prefix.'wdm_compatibility',
                    array('model_id' => $c_model,
                                    'frd_id' => $frd_product,
                                    'breaker_performance' => isset($row[5]) ? $row[5] : 0,
                                    'auto_coupler' => isset($row[6]) ? $row[6] : 0,
                                    'install_kit' => isset($row[7]) ? $row[7] : 0,
                                    'discontinued' => isset($row[8]) ? strtolower($row[8]) : ''),
                    array('id' => $exists),
                    array('%d',
                                    '%d',
                                    '%s',
                                    '%s',
                                    '%s',
                                    '%s'),
                    array('%d')
                );
            }
        }
        // }
    }
    fclose($file);
    die();
}

function wdmGetCategoryID($name, $type)
{
    global $wpdb;
    $category_id=0;
    if (!empty($type)) {
        $category_id=$wpdb->get_var('SELECT id from '.$wpdb->prefix.'wdm_categories WHERE name like "'.$name.'" AND type like "'.$type.'"');
        if (!$category_id) {
            $wpdb->insert($wpdb->prefix.'wdm_categories', array(
                'name' => $name,
                'type' => $type
            ));
            $category_id=$wpdb->insert_id;
        }
    }
    return $category_id;
}

function wdmGetPostID($name, $order, $type, $category_id, $manufacturer = 0)
{
    global $wpdb;
    $post_id=0;
    if (!empty($name)) {
        $post_id=$wpdb->get_var('SELECT id from '.$wpdb->prefix.'wdm_posts WHERE name like "'.$name.'" AND type like "'.$type.'" AND manufacturer='.$manufacturer.' AND category='.$category_id);
        if (!$post_id) {
            $wpdb->insert($wpdb->prefix.'wdm_posts', array(
                'name' => $name,
                'type' => $type,
                'manufacturer' => $manufacturer,
                'category' => $category_id,
                'sort_order' => $order
            ));
            $post_id=$wpdb->insert_id;
        }
    }
    return $post_id;
}

add_shortcode('wdm_view_compatibility', 'wdmFilter');

function wdmFilter($atts)
{
    ob_start();
    global $wpdb;
    $is_breaker=0;
    if (isset($atts['title'])) {
        echo "<div class='wdm_title'><h2>CARRIER COMPATIBILITY GUIDE for ".strtoupper($atts['title'])."</h2></div>";
        if (strpos($atts['title'], 'Breakers') !== false) {
            $is_breaker=1;
        }
    } elseif (isset($atts['product_series'])) {
        echo "<div class='wdm_title'><h2>CARRIER COMPATIBILITY GUIDE for ".strtoupper($atts['product_series'])."</h2></div>";
    } elseif (isset($atts['product'])) {
        $is_breaker=1;
        echo "<div class='wdm_title'><h2>CARRIER COMPATIBILITY GUIDE for ".$atts['product']."</h2></div>";
    } else {
        echo "<div class='wdm_title'><h2>CARRIER COMPATIBILITY GUIDE</h2></div>";
    }
    echo "<input type='hidden' class='is_breaker' value='".$is_breaker."'>";
    if (isset($atts['product_series'])) {
        ?>
        <input type="hidden" class="product_series" value="<?php echo $atts['product_series'];
        ?>">
        <input type="hidden" class="product" value='<?php echo $atts['product'];
        ?>'>
        <?php
        $frd_products=$wpdb->get_results('SELECT post.name
                                        FROM '.$wpdb->prefix.'wdm_categories AS cat
                                        JOIN '.$wpdb->prefix.'wdm_posts AS post ON cat.id=post.category
                                        WHERE cat.type like "frd" AND post.type like "frd_product"
                                        AND cat.name like "'.$atts['product_series'].'"
                                        ORDER BY post.sort_order ASC');
        echo '<div class="wdm_carrier_model wdm_section">';
        $model_name_sections = $atts['model_name_sections'];
        if ($atts['product_series']!='TEFRA Auto Coupler') {
            echo "<div class='wdm_title'><h3>Select FRD Model</h3></div>";
            echo "<div class='wdm_models_list'>";
            //$model_name_sections = $atts['model_name_sections'];
            if (isset($model_name_sections)) {
                $model_name_sections = explode('|', $model_name_sections);
                $sections = array();
                foreach ($model_name_sections as $model_names) {
                    //preg_match('/([\w\s]+)({([\w,]+)})/', $model_names, $models);
                    preg_match('/([\w\s\-\:]*)({([\w\s,]*)})/', $model_names, $models);
                    echo "<div class='wdm_model_section'>".$models[1]."</div>";
                    $model_vals = explode(',', $models[3]);
                    foreach ($frd_products as $frd_product) {
                        if (in_array($frd_product->name, $model_vals)) {
                            echo "<div class='wdm_model'>".$frd_product->name."</div>";
                        }
                    }
                }
            } else {
                foreach ($frd_products as $key) {
                    echo "<div class='wdm_model'>".$key->name."</div>";
                }
            }
            echo "</div>";
        } else {
            echo "<div class='wdm_title'><h3>Select TEFRA Model Size</h3></div>";
            echo "<div class='wdm_models_list'>";
            $arr=array();
            foreach ($frd_products as $key) {
                $size=explode('-', $key->name);
                if (!in_array($size[0], $arr)) {
                    array_push($arr, $size[0]);
                }
            }
            if (isset($model_name_sections)) {
                $model_name_sections = explode('|', $model_name_sections);
                $sections = array();
                foreach ($model_name_sections as $model_names) {
                    preg_match('/([\w\s\-\:]*)({([\w\s,]*)})/', $model_names, $models);
                    echo "<div class='wdm_model_section'>".$models[1]."</div>";
                    $model_vals = explode(',', $models[3]);
                    foreach ($arr as $frd_product) {
                        if (in_array($frd_product, $model_vals)) {
                            echo "<div class='wdm_model'>".$frd_product."</div>";
                        }
                    }
                }
            } else {
                foreach ($arr as $tac_model) {
                    echo "<div class='wdm_model'>".$tac_model."</div>";
                }
            }
            echo "</div>";
            unset($arr);
        }
        echo "</div>";
    } elseif (isset($atts['product'])) {
        ?>
        <input type="hidden" class="product" value='<?php echo $atts['product'];
        ?>'>
        <?php
        $products='';
        $string=explode(',', $atts['product']);
        foreach ($string as $value) {
            $products.=',"'.$value.'"';
        }
        if (count($string)==1) {
            wdmDisplayCarrierTypes(wdmGetCarriers($atts['product']), $atts['product']);
        } else {
            ?>
            <div class="wdm_carrier_model wdm_section">
                <div class='wdm_title'><h3>Select FRD Model</h3></div>
                <div class='wdm_models_list'>
                    <?php
                    foreach ($string as $value) {
                        echo "<div class='wdm_model'>".$value."</div>";
                    }
                    ?>
                </div>
            </div>
            <?php
        }
    } else {
        $carrier_type=$wpdb->get_results('SELECT id, name from '.$wpdb->prefix.'wdm_categories WHERE type like "carrier"');
        wdmDisplayCarrierTypes($carrier_type, '');
        $all_compatibility_class = 'all_compatibility';
    }
    ?>
    <input type="hidden" name="carrier_type" class="carrier_type" data-name="" value="">
    <div class="conf_model">
        <div class="wdm_manufacturers wdm_section"></div>
        <div class="wdm_models wdm_section"></div>
    </div>
    <input type="hidden" name="carrier_man" class="carrier_man" data-name="" value="0">
    <div class="wdm_compatibility_section wdm_section <?php echo $all_compatibility_class; ?>"></div>
    <?php
    $content=ob_get_contents();
    ob_end_clean();
    return $content;
}

function wdmGetCarriers($products)
{
    global $wpdb;
    $products=($_POST['product_series']=='TEFRA Auto Coupler') ? 'like "'.$products.'%"' : 'like "'.$products.'"';
    return $wpdb->get_results('SELECT DISTINCT (
                    cat.id
                    ), cat.name
                    FROM '.$wpdb->prefix.'wdm_categories AS cat
                    JOIN '.$wpdb->prefix.'wdm_posts AS post ON cat.id = post.category
                    JOIN '.$wpdb->prefix.'wdm_compatibility AS comp ON comp.model_id= post.id
                    WHERE post.type LIKE "c_model"
                    AND comp.frd_id IN ( SELECT id from '.$wpdb->prefix.'wdm_posts
                    WHERE name '.$products.'
                    AND type like "frd_product")');
}

function wdmDisplayCarrierTypes($carrier_type, $product = '')
{
    $ext='';
    if (strpos($product, 'MT') !== false) {
        $ext='-MT';
    } elseif (strpos($product, 'CW') !== false) {
        $ext='-WC';
    } elseif (strpos($product, 'HP') !== false) {
        $ext='-HP';
    } elseif (strpos($product, 'TAC') !== false) {
        $ext='-TAC';
    } elseif ($product == '') {
        $ext='';
    } else {
        $ext='-breaker';
    }
    ?>
    <div class="wdm_carrier_type wdm_section">
        <div class="wdm_title"><h3>Select Carrier Type</h3></div>
        <div class="wdm_types">
            <?php
            if (!empty($carrier_type)) {
                $arr=array();
                foreach ($carrier_type as $key) {
                    if ($key->name=='Compact Utility Loader') {
                        $arr[0]=array($key->id,$key->name);
                    } elseif ($key->name=='Skid Steer') {
                        $arr[1]=array($key->id,$key->name);
                    } elseif ($key->name=='Mini Excavator') {
                        $arr[2]=array($key->id,$key->name);
                    } elseif ($key->name=='Backhoe') {
                        $arr[3]=array($key->id,$key->name);
                    } elseif ($key->name=='Wheeled Excavator') {
                        $arr[4]=array($key->id,$key->name);
                    } elseif ($key->name=='Excavator') {
                        $arr[5]=array($key->id,$key->name);
                    }
                }
                /*$width=(100/count($arr))-1;*/
                ksort($arr);
                foreach ($arr as $key) {
                    echo '<div class="wdm_ct_tile" data-id="'.$key[0].'"><div class="ct_img"><img src="'.plugins_url('frd-compatibility-tables/images/'.strtolower(str_replace(' ', '', $key[1])).$ext.'.png').'"></div><div class="ct_name">'.$key[1].'</div></div>';
                }
            } else {
                if (isset($atts['product_series'])) {
                    echo "<div>Carrier Types with product series ".$atts['product_series']."&nbsp;does not exists</div>";
                } elseif (isset($atts['product'])) {
                    echo "<div>Carrier Types with product/s ".$atts['product']." does not exists</div>";
                } else {
                    echo "<div>Carier Type does not exists</div>";
                }
            }
            ?>
            <div class="wdm_next">></div>
            <div class="wdm_previous"><</div>
        </div>
        <div class="carrier_scroll_help">Scroll horizontally for additional carrier options</div>
            </div>
    <?php
}

add_action('wp_ajax_wdm_get_carrier_types', 'wdmSortCarrierByFRD');
add_action('wp_ajax_nopriv_wdm_get_carrier_types', 'wdmSortCarrierByFRD');
function wdmSortCarrierByFRD()
{
    wdmDisplayCarrierTypes(wdmGetCarriers($_POST['product']), $_POST['product']);
    die();
}

add_action('wp_ajax_wdm_get_manufacturer', 'wdmGetManufacturer');
add_action('wp_ajax_nopriv_wdm_get_manufacturer', 'wdmGetManufacturer');
function wdmGetManufacturer()
{
    global $wpdb;
    if ($_POST['product']) {
        $products=($_POST['product_series']=='TEFRA Auto Coupler') ? 'like "'.$_POST['product'].'%"' : 'like "'.$_POST['product'].'"';
        $manufacturer = $wpdb->get_results('SELECT DISTINCT (cat.id), cat.name
                    FROM '.$wpdb->prefix.'wdm_categories AS cat
                    JOIN '.$wpdb->prefix.'wdm_posts AS post ON cat.id = post.manufacturer
                    JOIN '.$wpdb->prefix.'wdm_compatibility AS comp ON comp.model_id= post.id
                    WHERE post.type LIKE  "c_model"
                    AND comp.frd_id IN ( SELECT p.id from '.$wpdb->prefix.'wdm_posts As p
                    WHERE p.name '.$products.'
                    AND type like "frd_product" )
                    AND post.category='.$_POST['c_type'].
                    ' ORDER BY cat.name');
    } else {
        $manufacturer=$wpdb->get_results('SELECT DISTINCT (
                        cat.id
                        ), cat.name
                        FROM '.$wpdb->prefix.'wdm_categories AS cat
                        JOIN '.$wpdb->prefix.'wdm_posts AS post ON cat.id = post.manufacturer
                        WHERE post.type LIKE  "c_model"
                        AND post.category ='.$_POST['c_type'].
                        ' ORDER BY cat.name');
    }

    if (!empty($manufacturer)) {
        echo "<select name='wdm_manufacturer'><option readonly>-- Select Manufacturer --</option>";
        foreach ($manufacturer as $key) {
            echo '<option value="'.$key->id.'">'.$key->name.'</option>';
        }
        echo "</select>";
    } else {
        if (isset($_POST['product_series'])) {
            echo "<div>Manufacturers associated with product series <b>".$_POST['product_series']."</b> and Carrier Type <b>".$_POST['c_type']."</b> does not exists</div>";
        } elseif (isset($_POST['product'])) {
            echo "<div>Manufacturers associated with product/s <b>".$_POST['product']."</b> and Carrier Type <b>".$_POST['c_type']."</b> does not exists</div>";
        } else {
            echo "<div>Manufacturers associated with Carrier Type <b>".$_POST['c_type']."</b> does not exists</div>";
        }
    }

    die();
}

add_action('wp_ajax_wdm_get_model', 'wdmGetModel');
add_action('wp_ajax_nopriv_wdm_get_model', 'wdmGetModel');
function wdmGetModel()
{
    global $wpdb;
    if ($_POST['product']) {
        wdmGetProductModels();
        die();
    } else {
        $carrier_models=$wpdb->get_results('SELECT id, name from '.$wpdb->prefix.'wdm_posts WHERE type like "c_model" AND manufacturer='.$_POST['manufacturer'].' AND category ='.$_POST['c_type']);
    }
    if (!empty($carrier_models)) {
        echo "<select name='wdm_model' class='wdm_model'>";
        echo '<option readonly>--Select Carrier Model--</option>';
        foreach ($carrier_models as $key) {
            echo '<option value="'.$key->id.'">'.$key->name.'</option>';
        }
        echo "</select>";
    } else {
        if (isset($_POST['product_series'])) {
            echo "<div>Models associated with product series <b>".$_POST['product_series']."</b> and Carrier Type <b>".$_POST['c_type']."</b> and Manufacturere <b>".$_POST['manufacturer']."</b> does not exists</div>";
        } elseif (isset($_POST['product'])) {
            echo "<div>Models associated with product/s <b>".$_POST['product']."</b> and Carrier Type <b>".$_POST['c_type']."</b> and Manufacturere <b>".$_POST['manufacturer']."</b> does not exists</div>";
        } else {
            echo "<div>Models associated with Carrier Type <b>".$_POST['c_type']."</b> and Manufacturere <b>".$_POST['manufacturer']."</b> does not exists</div>";
        }
    }
    die();
}

function wdmGetProductModels()
{
    global $wpdb;
    $products=($_POST['product_series']=='TEFRA Auto Coupler') ? 'like "'.$_POST['product'].'%"' : 'like "'.$_POST['product'].'"';
    $carrier_models=$wpdb->get_results('SELECT DISTINCT (
                post.id
                ), post.name, frd.pname, comp.breaker_performance, comp.auto_coupler
                FROM '.$wpdb->prefix.'wdm_categories AS cat
                JOIN '.$wpdb->prefix.'wdm_posts AS post ON cat.id = post.manufacturer
                JOIN '.$wpdb->prefix.'wdm_compatibility AS comp ON comp.model_id= post.id
                JOIN frd_product AS frd ON comp.frd_id=frd.id
                WHERE post.type LIKE  "c_model"
                AND comp.frd_id IN ( SELECT p.id from '.$wpdb->prefix.'wdm_posts As p
                WHERE p.name '.$products.'
                AND type like "frd_product")
                AND post.category='.$_POST['c_type'].'
                AND post.manufacturer='.$_POST['manufacturer']);
    wdmDisplayComaptibilityTable($carrier_models);
}

function wdmDisplayComaptibilityTable($carrier_models)
{
    echo "<div class='wdm_title'>COMPATIBLE <b>".$_POST['man_txt']."&nbsp;".$_POST['c_type_txt']."</b> for <b>".$_POST['product']."</b></div>";
    echo '<div class="wdm_compatibility_content">';
    echo '<div class="wdm_column">';
    $manufacturer=strtolower(str_replace(' ', '-', $_POST['man_txt']));
    if ($_POST['product_series']!='TEFRA Auto Coupler') {
        $is_breaker=$_POST['is_breaker'] ? 'wdm_breaker' : '';
        echo "<table class='".$is_breaker."'><thead><tr><th>Model No.</th>";
        if (!$_POST['product_series']) {
            if ($_POST['is_breaker']) {
                echo "<th>Compatibility</th>";
            }
        }
        echo "</tr></thead><tbody><tr><td colspan='2'><div class='scrollit'><table>";
        foreach ($carrier_models as $key) {
            $class=($key->breaker_performance=='Optional*') ? 'wdm_optinal':'wdm_optimum';
            //echo "<div class='wdm_model ".$class."'>".$key->breaker_performance."</div>";
            echo "<tr><td>".$key->name."</td>";
            if (!$_POST['product_series']) {
                if ($_POST['is_breaker']) {
                    echo "<td class='".$class."'>".$key->breaker_performance."</td>";
                }
            }
            echo "</tr>";
        }
        echo "</table></div></tbody></table>";
        echo "</div><div class='wdm_column'>";
        // if(getimagesize(plugins_url('frd-compatibility-tables/images/manufacturers/'.$manufacturer.'.jpg'))!==false){
            echo "<div class='wdm_logo'>";
            echo "<img src='".plugins_url('frd-compatibility-tables/images/manufacturers/'.$manufacturer.'.png')."'>";
            echo "</div>";
        // }
    } else {
        echo "<table class='wdm_tac'><thead><tr><th>Carrier Model No.</th><th>TEFRA Model NO.</th><th>Pin Pick-up Range (mm)</th>";
        echo "</tr></thead><tbody><tr><td colspan='3'><div class='scrollit'><table>";
        foreach ($carrier_models as $key) {
            echo "<tr><td>".$key->name."</td>";
            echo "<td>".$key->pname."</td>";
            echo "<td>".$key->auto_coupler."</td>";
            echo "</tr>";
        }
        echo "</table></div></tbody></table>";
        echo "</div><div class='wdm_column'>";
        // if(getimagesize(plugins_url('frd-compatibility-tables/images/manufacturers/'.$manufacturer.'.jpg'))!==false){
            echo "<div class='wdm_logo'>";
            echo "<img src='".plugins_url('frd-compatibility-tables/images/manufacturers/'.$manufacturer.'.png')."'>";
            echo "</div>";
        // }
    }
    echo '</div>';
}
add_action('wp_ajax_wdm_view_compatibility', 'wdmShowCompatibility');
add_action('wp_ajax_nopriv_wdm_view_compatibility', 'wdmShowCompatibility');

function wdmShowCompatibility()
{
    global $wpdb;
    $hyd_brk_comp=array();
    $hyd_brk_opt=array();
    $dis_brk_comp=array();
    $dis_brk_opt=array();
    $cmpt_drv=array();
    $cmpt_whl=array();
    $dis_cmpt=array();
    $dis_mech=array();
    $mech_thumb=array();
    $t_a_c=array();
    $dis_t_a_c=array();
    $auto_coupler=array();
    if ($_POST['product_series']) {
        $comp_data=$wpdb->get_results('SELECT post.name AS frd_name, cat.name AS cat_name, comp.breaker_performance, comp.auto_coupler, comp.install_kit , comp.discontinued
                                   FROM '.$wpdb->prefix.'wdm_compatibility AS comp LEFT JOIN '.$wpdb->prefix.'wdm_posts AS post ON comp.frd_id=post.id
                                   LEFT JOIN '.$wpdb->prefix.'wdm_categories AS cat ON cat.id=post.category
                                   WHERE comp.model_id='.$_POST['carrier_model'].' AND comp.frd_id IN ( SELECT p.id from '.$wpdb->prefix.'wdm_posts AS p 
                                    JOIN '.$wpdb->prefix.'wdm_categories AS c ON p.category=c.id
                                    WHERE c.name LIKE "'.$_POST['product_series'].'") ORDER BY post.sort_order ASC');
    } elseif ($_POST['product']) {
        $products='';
        $string=explode(',', $_POST['product']);
        foreach ($string as $value) {
            $products.=',"'.$value.'"';
        }
        $products=ltrim($products, ',');
        $comp_data=$wpdb->get_results('SELECT post.name AS frd_name, cat.name AS cat_name, comp.breaker_performance, comp.auto_coupler, comp.install_kit , comp.discontinued
                                   FROM '.$wpdb->prefix.'wdm_compatibility AS comp LEFT JOIN '.$wpdb->prefix.'wdm_posts AS post ON comp.frd_id=post.id
                                   LEFT JOIN '.$wpdb->prefix.'wdm_categories AS cat ON cat.id=post.category
                                   WHERE comp.model_id='.$_POST['carrier_model'].'
                                   AND comp.frd_id IN ( SELECT p.id from '.$wpdb->prefix.'wdm_posts As p
                                    WHERE p.name IN ('.$products.')
                                    AND type like "frd_product") ORDER BY post.sort_order ASC');
    } else {
        $comp_data=$wpdb->get_results('SELECT post.name AS frd_name, cat.name AS cat_name, comp.breaker_performance, comp.auto_coupler, comp.install_kit , comp.discontinued
                                   FROM '.$wpdb->prefix.'wdm_compatibility AS comp LEFT JOIN '.$wpdb->prefix.'wdm_posts AS post ON comp.frd_id=post.id
                                   LEFT JOIN '.$wpdb->prefix.'wdm_categories AS cat ON cat.id=post.category
                                   WHERE comp.model_id='.$_POST['carrier_model'].' ORDER BY post.sort_order ASC');
    }
    foreach ($comp_data as $key) {
        if ((strpos($key->cat_name, 'Breakers') !== false ) && $key->discontinued=='') {
            if ($key->breaker_performance=='Optimum') {
                array_push($hyd_brk_comp, $key->frd_name);
            } else {
                array_push($hyd_brk_opt, $key->frd_name);
            }
        } elseif ((strpos($key->cat_name, 'Breakers') !== false ) && $key->discontinued=='discontinued') {
            if ($key->breaker_performance=='Optimum') {
                array_push($dis_brk_comp, $key->frd_name);
            } else {
                array_push($dis_brk_opt, $key->frd_name);
            }
        } elseif ($key->cat_name=='Mechanical Thumb') {
            if ($key->discontinued=='') {
                array_push($mech_thumb, $key->frd_name);
            } else {
                array_push($dis_mech, $key->frd_name);
            }
        } elseif ($key->cat_name=='Compactor Wheels') {
            if ($key->discontinued=='') {
                array_push($cmpt_whl, $key->frd_name);
            } else {
                array_push($dis_cmpt, $key->frd_name);
            }
        } elseif ($key->cat_name=='Compactor Drivers') {
            if ($key->discontinued=='') {
                array_push($cmpt_drv, $key->frd_name);
            } else {
                array_push($dis_cmpt, $key->frd_name);
            }
        } elseif ($key->cat_name=='TEFRA Auto Coupler') {
            if ($key->discontinued=='') {
                array_push($t_a_c, $key->frd_name);
            } else {
                array_push($dis_t_a_c, $key->frd_name);
            }
        }
        if (!empty($key->auto_coupler)) {
            array_push($auto_coupler, $key->auto_coupler);
        }
    }

    wdmFrontDisplay($hyd_brk_opt, $hyd_brk_comp, $dis_brk_opt, $dis_brk_comp, $cmpt_drv, $cmpt_whl, $dis_cmpt, $mech_thumb, $dis_mech, $t_a_c, $dis_t_a_c, $auto_coupler);

    die();
    unset($hyd_brk_comp);
    unset($hyd_brk_opt);
    unset($dis_brk);
    unset($cmpt_drv);
    unset($cmpt_whl);
    unset($dis_cmpt);
    unset($dis_mech);
    unset($mech_thumb);
    unset($t_a_c);
    unset($dis_t_a_c);
    unset($auto_coupler);
}

function wdmDisplayModelList($arr, $is_optional = '')
{
    if (!empty($arr)) {
        foreach ($arr as $value) {
            echo '<div class="wdm_model_name '.$is_optional.'">'.$value.'</div>';
        }
    }
}

function wdmFrontDisplay($hyd_brk_opt, $hyd_brk_comp, $dis_brk_opt, $dis_brk_comp, $cmpt_drv, $cmpt_whl, $dis_cmpt, $mech_thumb, $dis_mech, $t_a_c, $dis_t_a_c, $auto_coupler)
{
    $invalid_col_class="";
    $invalid_col_head_class="";
    if ($_POST['product_series'] == 0 &&
        $_POST['product'] == 0 &&
        ($_POST['c_type_txt']=='Compact Utility Loader' || $_POST['c_type_txt']=='Skid Steer')
    ) {
        $invalid_col_class = "invalid_col";
        $invalid_col_head_class="invalid_col_head";
    }
    echo "<div class='wdm_title'>COMPATIBILITY DETAILS for <b>".$_POST['man_txt']."&nbsp;".$_POST['model_txt']."&nbsp;".$_POST['c_type_txt']."</b></div>";
    echo "<div class='wdm_comp_col'><h5 class='comp_title'>HYDRAULIC<br/>BREAKERS</h5>";
    if (!empty($hyd_brk_comp)) {
        echo "<h7 class='current_series'>Current Series</h7><h6 class='sub_title_comp'>Optimum Compatibility</h6>";
        sort($hyd_brk_comp);
        wdmDisplayModelList($hyd_brk_comp);
    }
    if (!empty($hyd_brk_opt)) {
        echo '<h6 class="sub_title_opt">Optional Compatibility*</h6>';
        sort($hyd_brk_opt);
        wdmDisplayModelList($hyd_brk_opt, 'wdm_optional_model');
    }
    if (!empty($dis_brk_opt) || !empty($dis_brk_comp)) {
        echo "<h7 class='previous_series'>Previous Models:</h7>";
        if (!empty($dis_brk_comp)) {
            echo '<h6 class="sub_title_comp">Optimum Compatibility</h6>';
            sort($dis_brk_comp);
            wdmDisplayModelList($dis_brk_comp);
        }
        if (!empty($dis_brk_opt)) {
            echo '<h6 class="sub_title_opt">Optional Compatibility*</h6>';
            sort($dis_brk_opt);
            wdmDisplayModelList($dis_brk_opt, 'wdm_optional_model');
        }
    }
    echo "</div>";
    echo "<div class='wdm_comp_col $invalid_col_class'><h5 class='comp_title $invalid_col_head_class'>COMPACTION<br/>ATTACHMENTS</h5>";
    if (!empty($cmpt_drv)) {
        echo '<h6 class="compaction_sub">Vibratory Plate</h6>';
        wdmDisplayModelList($cmpt_drv);
    }
    if (!empty($cmpt_whl)) {
        echo "<h6 class='compaction_sub'>Static Wheel</h6>";
        wdmDisplayModelList($cmpt_whl);
    }
    if (!empty($dis_cmpt)) {
        echo "<h7>Previous Models:</h7>";
        wdmDisplayModelList($dis_cmpt);
    }
    echo "</div>";
    echo "<div class='wdm_comp_col $invalid_col_class'><h5 class='comp_title $invalid_col_head_class'>MECHANICAL<br/>THUMBS</h5>";
    // if (!empty($mech_thumb)) {
    //     foreach ($mech_thumb as $value) {
    //         echo $value.'<br>';
    //     }
    // }
    wdmDisplayModelList($mech_thumb);
    if (!empty($dis_mech)) {
        echo "<h7>Previous Models:</h7>";
        wdmDisplayModelList($dis_mech);
    }
    echo "</div>";
    echo "<div class='wdm_comp_col $invalid_col_class'><h5 class='comp_title $invalid_col_head_class'>TEFRA AUTO-COUPLERS</h5>";
    if (!empty($t_a_c)) {
        wdmDisplayModelList($t_a_c);
    }
    if (!empty($dis_t_a_c)) {
        echo "<h7>Previous Models:</h7>";
        wdmDisplayModelList($dis_t_a_c);
    }
    echo "</div>";
    echo "<div class='wdm_comp_col $invalid_col_class'><h5 class='comp_title $invalid_col_head_class'>Pin Pick-up Range (mm)</h5>";
    if (!empty($auto_coupler)) {
        wdmDisplayModelList($auto_coupler);
    }
    echo "</div>";
}

add_action('wp_ajax_wdm_delete_all_data', 'wdmDeleteDBData');

function wdmDeleteDBData()
{
    global $wpdb;
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM `".$wpdb->prefix."wdm_categories` where 1", array()
        )
    );
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM `".$wpdb->prefix."wdm_compatibility` where 1", array()
        )
    );
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM `".$wpdb->prefix."wdm_posts` where 1", array()
        )
    );
}
add_action('admin_init', 'wdmCachedPagesOption');  
function wdmCachedPagesOption() {
    register_setting('general','wdm_cached_page');
    add_settings_section(
        'wdm_cached_pages_section',
        'FRD Compatibility',
        'addPagesToCachedSection',
        'general'
    );
	add_settings_field( // Option 1
        'wdm_cached_page', // Option ID
        'Clear cache on data upload', // Label
        'addPagesToCached', // !important - This is where the args go!
        'general', // Page it will be displayed (General Settings)
        'wdm_cached_pages_section', // Name of our section
        array('label_for' => 'wdm_cached_page')
    );
}
function addPagesToCachedSection () {
    return false;
	//echo "Add Pages to Cached";
}
function addPagesToCached($args)
{
	$pages = get_pages();
	$option = get_option('wdm_cached_page');
	?>
		<select class = "wdm_cached_page" name = "wdm_cached_page[]" multiple="multiple">
		<?php foreach($pages as $page) { ?>
		<option value = "<?php echo $page->ID; ?>" <?= (is_array($option) && in_array($page->ID, $option)) ? 'selected' : '' ?>><?php echo $page->post_title; ?></option>
		<?php } ?>
		</select>
	<?php
}
add_action('wp_ajax_wdm_clear_cache', 'clearPageCache');

function clearPageCache(){
   $all_pages = get_option('wdm_cached_pages');
   if(!empty($all_pages)){
       foreach ($all_pages as $page_id) {
            wdmPurgePage($page_id);
        }
    }
}

function wdmPurgePage($post_id) {

   if ( class_exists('Ninukis_Plugin') && Ninukis_Plugin::isCachingEnabled() ) {
   return NinukisCaching::get_instance()->purge_page_cache( $post_id );
   } else {
   return TRUE;
   }
}
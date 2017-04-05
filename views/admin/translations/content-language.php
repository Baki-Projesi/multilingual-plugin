<?php
echo head(array('title' => __('Content Languages')));
$tempCodes = unserialize(get_option('multilanguage_language_codes'));

$defaultCodes = Zend_Locale::getDefault();
$defaultCode = current(array_keys($defaultCodes));

if (plugin_is_active('Locale')) {
    $plugin = new LocalePlugin();
    $defaultCode = $plugin->filterLocale(null);
}

$codes = array();
foreach ($tempCodes as $code) {
    $codes[$code] = $code;
}

$codes = array($defaultCode => $defaultCode) + $codes;
?>

<form method='POST'>

<section class='seven columns alpha'>

<p><?php echo __('Default language is %s', $defaultCode); ?></p>

<?php 
//echo "<h2>" . __('Exhibits') . "</h2>";
if (isset($_GET['map'])) {
    if (isset($_GET['exhibit_id'])) {
        display_map_table();
    } elseif (isset($_GET['page_id'])) {      
        display_page_map_table();
    }

} else {

    /* TO DO: Generalize for Exhibits... right now only fleshed out with SimplePages */
    
  // if (isset($exhibits)): ?>
<!--    <table>
        <tr>
            <th>Exhibit</th>
            <th>Language</th>
            <th>Mapped to </th>
            <th></th>
        </tr>-->
    <?php //foreach ($exhibits as $exhibit):?>  
    <!--<tr>-->
    <?php 
//        $code = MultilanguageContentLanguage::lang('Exhibit', $exhibit->id); 
//        $mapped_exhibit = get_exhibit(MultilanguageContentLanguage::map_to_id('Exhibit', $exhibit->id));
//        
    ?>
<!--        <td><?php //echo $exhibit->title;?></td>
        <td><?php //echo get_view()->formSelect("exhibits[$exhibit->id]", $code, null, $codes);?></td>
        <td><?php //if (isset($mapped_exhibit)) { echo $mapped_exhibit->title; } ?></td>
        <td><?php //echo "<a href='?map&exhibit_id=$exhibit->id' class='button'>Change mapping</button></a>"; ?></td>
    </tr>-->
    <?php //endforeach; 
   // echo "</table>";

     

// endif; ?>

<h2><?php echo __('Simple Pages'); ?></h2>
<?php if (isset($simple_pages)): ?>
    <table>
        <tr>
            <th>Language</th>
            <th>Simple Page</th>
            <th>Mapped to </th>
            <th></th>
        </tr>
    <?php foreach ($simple_pages as $page):?>
    <?php
        $code = MultilanguageContentLanguage::lang('SimplePagesPage', $page->id);
        $currentid = $page->id;
        $mapped_page = get_simple_page(MultilanguageContentLanguage::map_to_id('SimplePagesPage', $page->id));
    ?>
    <tr>
        <td><?php echo get_view()->formSelect("simple_pages_page[$page->id]", $code, null, $codes);?></td>
        <td><?php echo $page->title; ?></td>
        <td><?php if (isset($mapped_page)) { echo $mapped_page->title; } ?></td>
        <td><?php echo "<a href='?map&page_id=$page->id' class='button'>Change mapping</button></a>"; ?></td>


    </tr>
    <?php endforeach; ?>
    </table>

<?php endif;

} // end not map
?>

</section>
    
<section class="three columns omega">
    <div class="panel" id="save">
        <input type="submit" class="submit big green button" value="Save Changes" id="save-changes" name="submit">
       
    </div>
</section>

</form>


<?php 
echo foot();
?>

<?php

function display_map_table() {
    
    echo flash();
    
    $exhibits_en = array();
    $exhibits_other = array();
    $table = get_db()->getTable('MultilanguageContentLanguage');
            
    $select = $table->getSelectForFindBy(
                array('record_type' => 'Exhibit',
                      'record_id'   => $_GET['exhibit_id'],
                ));
    $contentLanguage = $table->fetchObject($select);
    $this_lang = $contentLanguage->lang;
    
    $this_exhibit = get_exhibit($_GET['exhibit_id']);

    foreach (loop('exhibits') as $exhibit) {
       $params = array('record_type' => 'Exhibit',
            'record_id'   => $exhibit->id,
        );
       $select = $table->getSelectForFindBy($params);
        
        $record = $table->fetchObject($select);
        if ($record) {
            $exhibit_lang = $record->lang;
        }
        
        if ($exhibit_lang == 'en') {  
            $exhibits_en[$exhibit->id] = $exhibit->title;
        } else {
            $exhibits_other[$exhibit->id] = $exhibit->title;
        }
    }
    
    $exhibit_list = array();
    if ($this_lang == 'en') { 
        $exhibit_list = $exhibits_other; 
    } else {
        $exhibit_list = $exhibits_en;
    }
    
?>
<table>
    <input type='hidden' name='exhibit_id' value='<?php echo $this_exhibit->id?>' />
    <tr>
        <th>Exhibit - <?php echo $this_lang ?></th>
        <th>Map to </th>
    </tr>
    <tr>
    <?php
        echo "<td>" . $this_exhibit->title  . "</td>";
        echo "<td>";
            
            echo get_view()->formSelect('exhibit_map', $this_exhibit->id , null, $exhibit_list);
       
        echo "</td>";
    echo "</tr>";

?>
</table>

<p><a href="content-language" class="button">Set Language for Exhibits</a></p>
<?php 
}

// Construct the table that displays SimplePages and their mapping
function display_page_map_table() {
    
    echo flash();
    
    $pages_en = array();
    $pages_other = array();
    
    // Get record from multilanguage_content_language table
    $table = get_db()->getTable('MultilanguageContentLanguage');
            
    $select = $table->getSelectForFindBy(
                array('record_type' => 'SimplePagesPage',
                      'record_id'   => $_GET['page_id'],
                ));
    $contentLanguage = $table->fetchObject($select);
    $this_lang = $contentLanguage->lang;
      
    // Get record from simple_pages_pages table
    $this_page = get_simple_page($_GET['page_id']);
  
    // Does this page already have a mapping? If so, it should be the selected 
    // option in drop-down box
   $mapped_page_id = 0;
   if ($contentLanguage->map_to_id > 0) {
        $mapped_page_id = $contentLanguage->map_to_id;
   }
   
   // Get a list of all the SimplePages that this page could map to
   $pages = get_db()->getTable('SimplePagesPage')->findAll();
   foreach($pages as $page) {
        $params = array('record_type' => 'SimplePagesPage',
            'record_id'   => $page->id,
        );
      
       $select = $table->getSelectForFindBy($params);
       
        $record = $table->fetchObject($select);
           
        if ($record) {
            $page_lang = $record->lang;
        }
     
        if ($page_lang == 'en') {  
            $pages_en[$page->id] = $page->title;
        } else {
            $pages_other[$page->id] = $page->title;               
        }
   }
    // Load the page_list array with pages in the alternate language
    $page_list = array();

    if ($this_lang == 'en') { 
        $page_list = $pages_other;         
    } else {
        $page_list = $pages_en;
    }
    $page_list[0] = "Please select a page   ";
?>
<table>
    <input type='hidden' name='page_id' value='<?php echo $this_page->id?>' />
    <tr>
        <th>Simple Page - <?php echo $this_lang ?></th>
        <th>Map to </th>
    </tr>
    <tr>
    <?php
        echo "<td>" . $this_page->title  . "</td>";
        echo "<td>";
            // constuct select field with pages in alternate language
            echo get_view()->formSelect('simple_page_map', $mapped_page_id , null, $page_list);
        echo "</td>";
    echo "</tr>";

?>
</table>



<p><a href="content-language" class="button">Return</a></p>
<?php 
}

// get record from Exhibit table
function get_exhibit($id) {
    $table = get_db()->getTable('Exhibit');
    $exhibit = $table->find($id);  
    return $exhibit;
}
// get record from SimpagePagesPage table
function get_simple_page($id) {
    $table = get_db()->getTable('SimplePagesPage');
    $page = $table->find($id);  
    return $page;    
}

?>
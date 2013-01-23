<?php
    $error_messages = array();
    
    
    if(isset($_POST['import_csv_url']) && strlen($_POST['import_csv_url']) > 0) {
        
        $file_path = $_POST['import_csv_url'];
        
    } elseif(isset($_FILES['import_csv']['tmp_name'])) {
        
        if(function_exists('wp_upload_dir')) {
            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'].'/csv_import';
        } else {
            $upload_dir = dirname(__FILE__).'/uploads';
        }
        
        if(!file_exists($upload_dir)) {
            $old_umask = umask(0);
            mkdir($upload_dir, 0755, true);
            umask($old_umask);
        }
        if(!file_exists($upload_dir)) {
            $error_messages[] = 'Could not create upload directory "'.$upload_dir.'".';
        }
        
        //gets uploaded file extension for security check.
        $uploaded_file_ext = strtolower(pathinfo($_FILES['import_csv']['name'], PATHINFO_EXTENSION));
        
        //full path to uploaded file. slugifys the file name in case there are weird characters present.
        $uploaded_file_path = $upload_dir.'/'.sanitize_title(basename($_FILES['import_csv']['name'],'.'.$uploaded_file_ext)).'.'.$uploaded_file_ext;
        
        if($uploaded_file_ext != 'csv') {
            $error_messages[] = 'The file extension "'.$uploaded_file_ext.'" is not allowed.';
            
        } else {
            
            if(move_uploaded_file($_FILES['import_csv']['tmp_name'], $uploaded_file_path)) {
                $file_path = $uploaded_file_path;
                
            } else {
                $error_messages[] = 'move_uploaded_file() returned false.';
            }
        }
    }
    
    if($file_path) {
        //now that we have the file, grab contents
        $handle = fopen($file_path, 'r' );
        $import_data = array();
        
        if ( $handle !== FALSE ) {
            while ( ( $line = fgetcsv($handle) ) !== FALSE ) {
                $import_data[] = $line;
            }
            fclose( $handle );
            
        } else {
            $error_messages[] = 'Could not open file.';
        }
        
        if(sizeof($import_data) == 0) {
            $error_messages[] = 'No data to import.';
        }
        
        if(intval($_POST['header_row']) == 1)
            $header_row = array_shift($import_data);
            
        $row_count = sizeof($import_data);
    }
    
    $show_import_checkboxes = !!($row_count < 100);
    
    $col_mapping_options = array(
        'do_not_import' => array(
            'label' => 'Do Not Import',
            'mapping_hints' => array()),
        'post_title' => array(
            'label' => 'Name',
             'mapping_hints' => array('Title', 'Product Name')),
        'post_status' => array(
            'label' => 'Status (Valid: publish/draft/trash/[more in Codex])',
             'mapping_hints' => array('Status')),
        'post_content' => array(
            'label' => 'Description',
             'mapping_hints' => array('Desc', 'Content')),
        'post_excerpt' => array(
            'label' => 'Short Description',
             'mapping_hints' => array('Short Desc', 'Excerpt')),
        '_regular_price' => array(
            'label' => 'Regular Price',
             'mapping_hints' => array('Price')),
        '_sale_price' => array(
            'label' => 'Sale Price',
             'mapping_hints' => array()),
        '_tax_status' => array(
            'label' => 'Tax Status (Valid: taxable/shipping/none)',
             'mapping_hints' => array('Tax Status', 'Taxable')),
        '_tax_class' => array(
            'label' => 'Tax Class',
             'mapping_hints' => array()),
        '_visibility' => array(
            'label' => 'Visibility (Valid: visible/catalog/search/hidden)',
             'mapping_hints' => array('Visibility', 'Visible')),
        '_featured' => array(
            'label' => 'Featured (Valid: yes/no)',
             'mapping_hints' => array('Featured')),
        '_weight' => array(
            'label' => 'Weight',
             'mapping_hints' => array('wt')),
        '_length' => array(
            'label' => 'Length',
             'mapping_hints' => array('l')),
        '_width' => array(
            'label' => 'Width',
             'mapping_hints' => array('w')),
        '_height' => array(
            'label' => 'Height',
             'mapping_hints' => array('h')),
        '_sku' => array(
            'label' => 'SKU',
             'mapping_hints' => array()),
        '_downloadable' => array(
            'label' => 'Downloadable (Valid: yes/no)',
             'mapping_hints' => array('Downloadable')),
        '_virtual' => array(
            'label' => 'Virtual (Valid: yes/no)',
             'mapping_hints' => array('Virtual')),
        '_stock' => array(
            'label' => 'Stock',
             'mapping_hints' => array('Qty', 'Quantity')),
        '_stock_status' => array(
            'label' => 'Stock Status (Valid: instock/outofstock)',
             'mapping_hints' => array('Stock Status', 'In Stock')),
        '_backorders' => array(
            'label' => 'Backorders (Valid: yes/no/notify)',
             'mapping_hints' => array('Backorders')),
        '_manage_stock' => array(
            'label' => 'Manage Stock (Valid: yes/no)',
             'mapping_hints' => array('Manage Stock')),
        '_product_type' => array(
            'label' => 'Product Type (Valid: simple/variable/grouped/external)',
             'mapping_hints' => array('Product Type', 'Type')),
        'product_cat_by_name' => array(
            'label' => 'Categories By Name (Separated by "|")',
             'mapping_hints' => array('Category', 'Categories', 'Product Category', 'Product Categories', 'product_cat')),
        'product_cat_by_id' => array(
            'label' => 'Categories By ID (Separated by "|")',
             'mapping_hints' => array()),
        'product_tag_by_name' => array(
            'label' => 'Tags By Name (Separated by "|")',
             'mapping_hints' => array('Tag', 'Tags', 'Product Tag', 'Product Tags', 'product_tag')),
        'product_tag_by_id' => array(
            'label' => 'Tags By ID (Separated by "|")',
             'mapping_hints' => array()),
        'custom_field' => array(
            'label' => 'Custom Field (Set Name Below)',
             'mapping_hints' => array('Custom Field', 'Custom')),
        'product_image_by_url' => array(
            'label' => 'Images (By URL, Separated by "|")',
             'mapping_hints' => array('Image URL', 'Image URLs', 'Product Image URL', 'Product Image URLs')),
        'product_image_by_path' => array(
            'label' => 'Images (By Local File Path, Separated by "|")',
             'mapping_hints' => array('Image Path', 'Image Paths', 'Product Image Path', 'Product Image Paths')),
        '_button_text' => array(
            'label' => 'Button Text (External Product Only)',
             'mapping_hints' => array('Button Text')),
        '_product_url' => array(
            'label' => 'Product URL (External Product Only)',
             'mapping_hints' => array('Product URL', 'URL')),
        '_file_path' => array(
            'label' => 'File Path (Downloadable Product Only)',
             'mapping_hints' => array('File Path', 'File')),
        '_download_expiry' => array(
            'label' => 'Download Expiration (in Days)',
             'mapping_hints' => array('Download Expiration', 'Download Expiry')),
        '_download_limit' => array(
            'label' => 'Download Limit (Number of Downloads)',
            'mapping_hints' => array('Download Limit', 'Number of Downloads'))
    );
    
?>
<script type="text/javascript">
    jQuery(document).ready(function($){
        $("select.map_to").change(function(){
            
            if($(this).val() == 'custom_field') {
                $(this).closest('th').find('.custom_field_settings').show(400);
            } else {
                $(this).closest('th').find('.custom_field_settings').hide(400);
            }
            
            if($(this).val() == 'product_image') {
                $(this).closest('th').find('.product_image_settings').show(400);
            } else {
                $(this).closest('th').find('.product_image_settings').hide(400);
            }
        });
    });
</script>

<div class="woo_product_importer_wrapper wrap">
    <div id="icon-tools" class="icon32"><br /></div>
    <h2>Woo Product Importer &raquo; Preview</h2>
    
    <?php if(sizeof($error_messages) > 0): ?>
        <ul class="import_error_messages">
            <?php foreach($error_messages as $message):?>
                <li><?php echo $message; ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    
    <form enctype="multipart/form-data" method="post" action="<?php echo get_admin_url().'tools.php?page=woo-product-importer&action=result'; ?>">
        <input type="hidden" name="uploaded_file_path" value="<?php echo htmlspecialchars($file_path); ?>">
        <input type="hidden" name="header_row" value="<?php echo $_POST['header_row']; ?>">
        <input type="hidden" name="row_count" value="<?php echo $row_count; ?>">
        <input type="hidden" name="limit" value="5">
        
        <p>
            <button class="button-primary" type="submit">Import</button>
        </p>
        
        <table class="wp-list-table widefat fixed pages" cellspacing="0">
            <thead>
                <?php if(intval($_POST['header_row']) == 1): ?>
                    <tr class="header_row">
                        <th colspan="<?php echo ($show_import_checkboxes) ? sizeof($header_row) + 1 : sizeof($header_row); ?>">CSV Header Row</th>
                    </tr>
                    <tr class="header_row">
                        <?php if($show_import_checkboxes): ?>
                            <th></th>
                        <?php endif; ?>
                        <?php foreach($header_row as $col): ?>
                            <th><?php echo htmlspecialchars($col); ?></th>
                        <?php endforeach; ?>
                    </tr>
                <?php endif; ?>
                <tr>
                    <?php if($show_import_checkboxes): ?>
                        <th class="narrow">Import?</th>
                    <?php endif; ?>
                    <?php
                        reset($import_data);
                        $first_row = current($import_data);
                        foreach($first_row as $key => $col):
                    ?>
                        <th>
                            <div class="map_to_settings">
                                Map to: <select name="map_to[<?php echo $key; ?>]" class="map_to">
                                    <?php foreach($col_mapping_options as $value => $meta): ?>
                                        <option value="<?php echo $value; ?>" <?php
                                            if(intval($_POST['header_row']) == 1) {
                                                //pre-select this value if the header_row
                                                //matches the label, value, or any of the hints.
                                                if( $header_row[$key] == $value ||
                                                    $header_row[$key] == $meta['label'] ||
                                                    in_array($header_row[$key], $meta['mapping_hints']) ) {
                                                        
                                                    echo 'selected="selected"';
                                                }
                                            }
                                        ?>><?php echo $meta['label']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="custom_field_settings field_settings">
                                <h4>Custom Field Settings</h4>
                                <p>
                                    <label for="custom_field_name_<?php echo $key; ?>">Name</label>
                                    <input type="text" name="custom_field_name[<?php echo $key; ?>]" id="custom_field_name_<?php echo $key; ?>" value="<?php echo $header_row[$key]; ?>" />
                                </p>
                                <p>
                                    <input type="checkbox" name="custom_field_visible[<?php echo $key; ?>]" id="custom_field_visible_<?php echo $key; ?>" value="1" checked="checked" />
                                    <label for="custom_field_visible_<?php echo $key; ?>">Visible?</label>
                                </p>
                            </div>
                            <div class="product_image_settings field_settings">
                                <h4>Image Settings</h4>
                                <p>
                                    <input type="checkbox" name="product_image_set_featured[<?php echo $key; ?>]" id="product_image_set_featured_<?php echo $key; ?>" value="1" checked="checked" />
                                    <label for="product_image_set_featured_<?php echo $key; ?>">Set First Image as Featured</label>
                                </p>
                            </div>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($import_data as $row_id => $row): ?>
                    <tr>
                        <?php if($show_import_checkboxes): ?>
                            <td class="narrow"><input type="checkbox" name="import_row[<?php echo $row_id; ?>]" value="1" checked="checked" /></td>
                        <?php endif; ?>
                        <?php foreach($row as $col): ?>
                            <td><?php echo htmlspecialchars($col); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
</div>
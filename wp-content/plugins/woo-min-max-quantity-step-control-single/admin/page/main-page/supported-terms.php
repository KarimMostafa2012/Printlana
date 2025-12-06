<?php
    
$term_lists_temp = get_object_taxonomies('product','objects');
                    

$term_lists = $term_lists_temp;

/**
 * We will remove Product Attribute, like: size, color or any other product attribute
 * from Terms support list
 * 
 * asole amra jevabe taxonomy pai:
 * 
 * ekhane Taxonomy_Object->labels->back_to_items a string thake erokom "'&larr; Back to "Color" attributes'"
 * ekhan theke 'attributes' string er strpost thakle amra seta bad debo.
 * ejonno ami korechi. empty hole true, mane position paoya jayni. r jayni manei 'attributes' string ta nei.
 * 
 */
$term_lists = array_filter($term_lists,function($kkk){
    $parenttt = $kkk->labels->back_to_items;
    return empty(strpos($parenttt, 'attributes'));
});

$supported_terms = isset( $saved_data['supported_terms'] ) ? $saved_data['supported_terms'] : array( 'product_cat', 'product_tag' );
$select_option = false;
$ourTermList = [];
if( is_array( $term_lists ) && count( $term_lists ) > 0 ){
    foreach( $term_lists as $trm_key => $trm_object ){

        $selected =  ( !$supported_terms && $trm_key == 'product_cat' ) || ( is_array( $supported_terms ) && in_array( $trm_key, $supported_terms ) ) ? 'selected' : false;

        if( $trm_object->labels->singular_name == 'Tag' && $trm_key !== 'product_tag' ){
            $value = $trm_key;
            $select_option .= "<option value='" . esc_attr( $trm_key ) . "' " . esc_attr( $selected ) . ">" . $trm_key . "</option>";
        }else{
            $value = $trm_object->labels->singular_name;
            $select_option .= "<option value='" . esc_attr( $trm_key ) . "' " . esc_attr( $selected ) . ">" . $trm_object->labels->singular_name . "</option>";
        }
        if( $selected ){
        $ourTermList[$trm_key] = $value; 
        }
    }
}

?>

<table class="wcmmq-table supported-terms">
    <thead>
        <tr>
            <th class="wcmmq-inside">
                <div class="wcmmq-table-header-inside">
                    <h3><?php echo esc_html__( 'Terms', 'woo-min-max-quantity-step-control-single' ); ?></h3>
                </div>
            </th>
            <th>
            <div class="wcmmq-table-header-right-side"></div>
            </th>
        </tr>
    </thead>

    <tbody>
        <tr>
            <td>
                <div class="wcmmq-form-control">
                    <div class="form-label col-lg-6">
                        <label for=""><?php echo esc_html__('Select Terms for Condition','woo-min-max-quantity-step-control-single');?></label>
                    </div>
                    <div class="form-field col-lg-6" >
                        <select style="width: 100%;" name="data[supported_terms][]" data-name="supported_terms" class="ua_input_select" id="wcmmq_supported_terms" multiple>
                        <?php 
                        $allowed_html = array( 
                            'option' => array( 
                                'value' => true,
                                'selected' => true 
                                )
                        );
                        echo wp_kses( $select_option, $allowed_html ); 
                        ?>
                        </select>
                    </div>
                </div>
            </td>
            <td>
                <div class="wcmmq-form-info">
                <?php wcmmq_doc_link('https://codeastrology.com/min-max-quantity/set-conditions-to-a-specific-category/'); ?>
                <p>Set Taxonomy wise miminum, maximum and step quantity.</p>
                <?php if( ! defined( 'WC_MMQ_PRO_VERSION' ) ){ ?>
                <p class="wcmmq_terms_promotion wcmmq-input-decimal-msg-free">
                <?php 
                    echo esc_html__('For Mulitple Terms, Such: Category, Tag, Color, Size or any other taxonomy. Need Pro version. ','woo-min-max-quantity-step-control-single');?> <a href="https://codeastrology.com/min-max-quantity/pricing/"><?php echo esc_html__('Upgrade to PRO','woo-min-max-quantity-step-control-single');?></a>    
                </p>
                <?php
                    };
                ?>
                </div> 
            </td>
        </tr>
    </tbody>
</table>   
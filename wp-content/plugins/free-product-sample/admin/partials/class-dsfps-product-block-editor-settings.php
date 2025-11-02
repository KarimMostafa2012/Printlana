<?php 
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DSFPS_Product_Block_Editor_Settings
 */
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use \Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( !class_exists( 'DSFPS_Product_Block_Editor_Settings' ) ) {
class DSFPS_Product_Block_Editor_Settings {
   /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;
    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;
    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version     The version of this plugin.
     *
     * @since    1.0.0
     */
    public function __construct( $plugin_name, $version ) {
    	$this->plugin_name = $plugin_name;
    	$this->version     = $version;
    }

    /**
     * Add Revenue Booster group in product block editor
     * 
     * @param BlockInterface $general_group
     * 
     * @since 1.0.0
     */
    public function dsfps_add_sample_product_group( BlockInterface $general_group ){
        $parent = $general_group->get_parent();
        $dsfps_sample = $parent->add_group([
            'id'         => 'fps-free-product-sample',
            'order'      => 999,
            'attributes' => [
                'title' => __( 'Sample Product', 'free-product-sample' ),
            ],
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.type === "external" || editedProduct.type === "grouped"',
                ],
            ],
        ]);
        // Add Sample Product Section
        $dsfps_sample_product_section = $dsfps_sample->add_section([
            'id'         => 'dsfps-sample-product-section',
            'order'      => 10,
            'attributes' => [
                'title' => __( 'Sample Product', 'free-product-sample' ),
                'description' => esc_html__( '', 'free-product-sample' ),
            ],
        ]); 

        $dsfps_sample_product_section->add_block([
            'id'         => 'dsfps_make_it_sample_custom_metefield',
            'blockName'  => 'woocommerce/product-toggle-field',
            'order'      => 1,
            'attributes' => array(
                'property'       => 'meta_data.dsfps_make_it_sample',
                'checkedValue'   => "yes",
                'uncheckedValue' => 'no',
                'label'          => __( 'Enable sample', 'free-product-sample' ),
                'help'        => __( 'Select to enable sample for this product to available for the users.', 'free-product-sample' ),
            ),
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "simple" && editedProduct.type !== "variable"',
                ],
            ],
        ]);
        $dsfps_sample_product_section->add_block([
            'id'         => 'fps_manage_variation_sample_custom_metefield',
            'blockName'  => 'woocommerce/product-toggle-field',
            'order'      => 2,
            'attributes' => array(
                'property'       => 'meta_data.fps_manage_variation_sample',
                'checkedValue'   => "yes",
                'uncheckedValue' => 'no',
                'label'          => __( 'Manage Sample?', 'free-product-sample' ),
                'help'        => __( 'Enable this to manage sample for this variation.', 'free-product-sample' ),
            ),
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "variation"',
                ],
            ],
        ]);
        $dsfps_sample_product_section->add_block([
            'id'         => 'dsfps_price_custom_metefield',
            'blockName'  => 'woocommerce/product-pricing-field',
            'order'      => 3,
            'attributes' => array(
                'label'    => __( 'Sample Price', 'free-product-sample'),
                'property' => 'meta_data.dsfps_price',
                'help'     => __( 'The price of sample product for this product in currency format.', 'free-product-sample'),
                'tooltip'  => __( 'The price of sample product for this product in currency format.', 'free-product-sample'),
            ),
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "simple"',
                ],
            ],
            'disableConditions' => [
                [
                    'expression' => 'editedProduct.type !== "simple"',
                ],
            ],
        ]);
        $dsfps_sample_product_section->add_block([
            'id'         => 'dsfps_variation_price_custom_metefield',
            'blockName'  => 'woocommerce/product-pricing-field',
            'order'      => 4,
            'attributes' => array(
                'label'    => __( 'Variation Sample Price', 'free-product-sample'),
                'property'       => 'meta_data.dsfps_variation_price',
                'help'     => __('The price of sample product for this product in currency format.', 'free-product-sample'),
                'tooltip'  => __('The price of sample product for this product in currency format.', 'free-product-sample'),
            ),
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "variation"',
                ],
            ],
            'disableConditions' => [
                [
                    'expression' => 'editedProduct.meta_data.fps_manage_variation_sample !== "yes"',
                ],
            ],
        ]);
        $dsfps_sample_product_section->add_block([
            'id'         => 'dsfps_enable_attachment_metefield',
            'blockName'  => 'woocommerce/product-toggle-field',
            'order'      => 4,
            'attributes' => array(
                'property'       => 'meta_data.dsfps_enable_attachment',
                'checkedValue'   => "yes",
                'uncheckedValue' => 'no',
                'label'          => __( 'Sample Attachment', 'free-product-sample' ),
                'help'        => __( 'Attach custom file for sample orders. Customers will receive this attached file in order emails and on my account page.', 'free-product-sample' ),
            ),
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "simple" && editedProduct.type !== "variable"',
                ],
            ],
        ]);
        $dsfps_sample_product_section->add_block([
            'id'         => 'dsfps_enable_attachment_variation_metefield',
            'blockName'  => 'woocommerce/product-toggle-field',
            'order'      => 4,
            'attributes' => array(
                'property'       => 'meta_data.dsfps_enable_attachment_variation',
                'checkedValue'   => "yes",
                'uncheckedValue' => 'no',
                'label'          => __( 'Sample Attachment', 'free-product-sample' ),
                'help'        => __( 'Attach custom file for sample orders. Customers will receive this attached file in order emails and on my account page.', 'free-product-sample' ),
            ),
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "variation"',
                ],
            ],
            'disableConditions' => [
                [
                    'expression' => 'editedProduct.meta_data.fps_manage_variation_sample !== "yes"',
                ],
            ],
        ]);
        $dsfps_sample_product_section->add_block(
            array(
              'id'         => 'dsfps_custom_attachment-metafield',
              'blockName'  => 'woocommerce/product-text-field',
              'order'      => 4,
              'attributes' => array(
                'property'    => 'meta_data.dsfps_custom_attachment',
                'label'       => __( 'Add your attachment url here.', 'free-product-sample' ),
                'placeholder' => __( 'Add your attachment url here.', 'free-product-sample' ),
                'suffix'      => true,
                'type'        => array(
                  'value'   => 'url',
                  'message' => __( 'Provided url is not valid.', 'free-product-sample' ),
                ),
              ),
              'hideConditions' => [
                    [
                        'expression' => 'editedProduct.meta_data.dsfps_enable_attachment  !== "yes" || editedProduct.type !== "simple" && editedProduct.type !== "variable"',
                    ],
                ],
            )
          );
        $dsfps_sample_product_section->add_block(
            array(
              'id'         => 'dsfps_custom_attachment_variation-metafield',
              'blockName'  => 'woocommerce/product-text-field',
              'order'      => 4,
              'attributes' => array(
                'property'    => 'meta_data.dsfps_custom_attachment',
                'label'       => __( 'Add your variation attachment url here.', 'free-product-sample' ),
                'placeholder' => __( 'Add your attachment url here.', 'free-product-sample' ),
                'suffix'      => true,
                'type'        => array(
                  'value'   => 'url',
                  'message' => __( 'Provided url is not valid.', 'free-product-sample' ),
                ),
              ),
              'hideConditions' => [
                    [
                        'expression' => 'editedProduct.meta_data.dsfps_enable_attachment_variation  !== "yes" || editedProduct.type !== "variation"',
                    ],
                ],
            'disableConditions' => [
                    [
                        'expression' => 'editedProduct.meta_data.fps_manage_variation_sample !== "yes"',
                    ],
                ],
            )
          );
        $dsfps_sample_product_section->add_block([
            'id'         => 'dsfps_quantity_limit_per_product_metefield',
            'blockName'  => 'woocommerce/product-number-field',
            'order'      => 6,
            'attributes' => array(
                'property'       => 'meta_data.dsfps_quantity_limit_per_product',
                'label'          => __( 'Allow Max Quantity', 'free-product-sample' ),
                'tooltip'        => __( 'Specify the maximum quantity for this sample product.', 'free-product-sample' ),
                'help'           => __( 'Specify the maximum quantity for this sample product.', 'free-product-sample' ),
            ),
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "simple" && editedProduct.type !== "variable"',
                ],
            ],
           
        ]);
        $dsfps_sample_product_section->add_block([
            'id'         => 'dsfps_manage_stock_metefield',
            'blockName'  => 'woocommerce/product-toggle-field',
            'order'      => 7,
            'attributes' => array(
                'property'       => 'meta_data.dsfps_manage_stock',
                'checkedValue'   => "yes",
                'uncheckedValue' => 'no',
                'label'          => __( 'Manage stock?', 'free-product-sample' ),
                'help'        => __( 'Enable this to manage stock for this product.', 'free-product-sample' ),
            ),
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "simple" && editedProduct.type !== "variable"',
                ],
            ],
        ]);
        $dsfps_sample_product_section->add_block([
            'id'         => 'dsfps_stock_metefield',
            'blockName'  => 'woocommerce/product-number-field',
            'order'      => 8,
            'attributes' => array(
                'property'       => 'meta_data.dsfps_stock',
                'label'          => __( 'Stock quantity', 'free-product-sample' ),
                'tooltip'        => __( 'Manage the sample products stock quantity.', 'free-product-sample' ),
                'help'           => __( 'Manage the sample products stock quantity.', 'free-product-sample'),
            ),
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.meta_data.dsfps_manage_stock  !== "yes" || editedProduct.type !== "simple" && editedProduct.type !== "variable"',
                ],
            ],
        ]);
        $dsfps_sample_product_section->add_block([
            'id'         => 'dsfps_variation_stock_metefield',
            'blockName'  => 'woocommerce/product-number-field',
            'order'      => 9,
            'attributes' => array(
                'property'       => 'meta_data.dsfps_variation_stock',
                'label'          => __( 'Variation Stock quantity', 'free-product-sample' ),
                'tooltip'        => __( 'Manage the sample products stock quantity.', 'free-product-sample' ),
                'help'           => __( 'Manage the sample products stock quantity.', 'free-product-sample' ),
            ),
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.manage_stock  !== true || editedProduct.type !== "variation"',
                ],
            ],
            'disableConditions' => [
                [
                    'expression' => 'editedProduct.meta_data.fps_manage_variation_sample !== "yes"',
                ],
            ],
        ]);
        $dsfps_sample_product_section->add_block([
            'id'         => 'dsfps_weight_metefield',
            'blockName'  => 'woocommerce/product-number-field',
            'order'      => 10,
            'attributes' => array(
                'property'       => 'meta_data.dsfps_weight',
                'label'          => __( 'Weight (kg)', 'free-product-sample' ),
                'tooltip'        => __( 'Weight in decimal form', 'free-product-sample' ),
                'help'           => __( 'Weight in decimal form', 'free-product-sample' ),
            ),
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "simple" && editedProduct.type !== "variable"',
                ],
            ],
        ]);
        $dsfps_sample_product_section->add_block([
            'id'         => 'dsfps_variation_weight_metefield',
            'blockName'  => 'woocommerce/product-number-field',
            'order'      => 11,
            'attributes' => array(
                'property'       => 'meta_data.dsfps_variation_weight',
                'label'          => __( 'Variation Weight (kg)', 'free-product-sample' ),
                'tooltip'        => __( 'Weight in decimal form', 'free-product-sample' ),
                'help'           => __( 'Weight in decimal form', 'free-product-sample' ),
            ),
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "variation"',
                ],
            ],
            'disableConditions' => [
                [
                    'expression' => 'editedProduct.meta_data.fps_manage_variation_sample !== "yes"',
                ],
            ],
        ]);

        $dsfpd_dimensions_column = $dsfps_sample_product_section->add_block(
			array(
				'id'        => 'dsrbfw-fbt-discount-columns',
				'blockName' => 'core/columns',
				'order'     => 12,
			)
		);

        $dsfpd_dimensions_length = $dsfpd_dimensions_column->add_block(
			array(
				'id'         => 'dsrbfw-fbt-discount-column',
				'blockName'  => 'core/column',
				'order'      => 13,
				'attributes' => array(
					'templateLock' => 'all',
				),
			)
		);
        $dsfpd_dimensions_length->add_block([
            'id'         => 'dsfps_length-field',
            'blockName'  => 'woocommerce/product-number-field',
            'order'      => 14,
            'attributes' => array(
                'label' => __( 'Length', 'free-product-sample' ),
                'property' => 'meta_data.dsfps_length',
                'tooltip'      => __( 'Length in decimal form', 'free-product-sample' ),
                'help'           => __( 'Length in decimal form', 'free-product-sample' ),
            ),
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "simple"  && editedProduct.type !== "variable"',
                ],
            ],
        ]);
        $dsfpd_dimensions_length->add_block([
            'id'         => 'dsfps_variation_length-field',
            'blockName'  => 'woocommerce/product-number-field',
            'order'      => 15,
            'attributes' => array(
                'label' => __( 'Variation Length', 'free-product-sample' ),
                'property' => 'meta_data.dsfps_variation_length',
                'tooltip'      => __( 'Length in decimal form', 'free-product-sample' ),
                'help'           => __( 'Length in decimal form', 'free-product-sample'),
            ),
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "variation"',
                ],
            ],
            'disableConditions' => [
                [
                    'expression' => 'editedProduct.meta_data.fps_manage_variation_sample !== "yes"',
                ],
            ],
        ]);
       
        $dsfpd_dimensions_width = $dsfpd_dimensions_column->add_block([
            'id'         => 'dsrbfw-fbt-discount-type-column',
            'blockName'  => 'core/column',
            'order'      => 16,
            'attributes' => array(
                'templateLock' => 'all',
            ),
        ]);
        $dsfpd_dimensions_width->add_block([
            'id'         => 'dsfps_width-field',
            'blockName'  => 'woocommerce/product-number-field',
            'order'      => 17,
            'attributes' => array(
                'label' => __( 'Width', 'free-product-sample' ),
                'property' => 'meta_data.dsfps_width',
                'tooltip'      => __( 'Width in decimal form', 'free-product-sample' ),
                'help'           => __( 'Width in decimal form', 'free-product-sample'),
            ),
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "simple" && editedProduct.type !== "variable"',
                ],
            ],
        ]);
        $dsfpd_dimensions_width->add_block([
            'id'         => 'dsfps_variation_width-field',
            'blockName'  => 'woocommerce/product-number-field',
            'order'      => 18,
            'attributes' => array(
                'label' => __( 'Variation Width', 'free-product-sample' ),
                'property' => 'meta_data.dsfps_variation_width',
                'tooltip'      => __( 'Width in decimal form', 'free-product-sample' ),
                'help'           => __( 'Width in decimal form', 'free-product-sample'),
            ),
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "variation"',
                ],
            ],
            'disableConditions' => [
                [
                    'expression' => 'editedProduct.meta_data.fps_manage_variation_sample !== "yes"',
                ],
            ],

        ]);

        $dsfpd_dimensions_height = $dsfpd_dimensions_column->add_block([
            'id'         => 'dsfps_height-column',
            'blockName'  => 'core/column',
            'order'      => 19,
            'attributes' => array(
                'templateLock' => 'all',
            ),
        ]);
        $dsfpd_dimensions_height->add_block([
            'id'         => 'dsfps_height-field',
            'blockName'  => 'woocommerce/product-number-field',
            'order'      => 20,
            'attributes' => array(
                'label' => __( 'Height', 'free-product-sample' ),
                'property' => 'meta_data.dsfps_height',
                'tooltip'      => __( 'Height in decimal format', 'free-product-sample' ),
                'help'           => __( 'Height in decimal format', 'free-product-sample'),
            ),
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "simple" && editedProduct.type !== "variable"',
                ],
            ],
        ]);
        $dsfpd_dimensions_height->add_block([
            'id'         => 'dsfps_variation_height-field',
            'blockName'  => 'woocommerce/product-number-field',
            'order'      => 21,
            'attributes' => array(
                'label' => __( 'Variation Height', 'free-product-sample' ),
                'property' => 'meta_data.dsfps_variation_height',
                'tooltip'      => __( 'Height in decimal format', 'free-product-sample' ),
                'help'           => __( 'Height in decimal format', 'free-product-sample'),
            ),
            'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "variation"',
                ],
            ],
            'disableConditions' => [
                [
                    'expression' => 'editedProduct.meta_data.fps_manage_variation_sample !== "yes"',
                ],
            ],
        ]);

        $shipping_classes = get_terms( array('taxonomy' => 'product_shipping_class', 'hide_empty' => 0 ) );
        $options = array(); 
        $options[] = array(
            'value' => '-1',
            'label' => __( 'Same as a sample settings page', 'free-product-sample' )
        );
        if ( ! empty( $shipping_classes ) && ! is_wp_error( $shipping_classes ) ) {
            foreach ($shipping_classes as $ship_class) {
                $options[] = array(
                    'value' => $ship_class->term_id,
                    'label' => __($ship_class->name, 'free-product-sample' )
                );
            }
        }
       
        $dsfps_sample_product_section->add_block(
            array(
              'id'         => 'dsfps_shipping_class-metafield',
              'blockName'  => 'woocommerce/product-select-field',
              'order'      => 22,
              'attributes' => array(
                'label'    => __('Shipping class', 'free-product-sample' ),
                'property' => 'meta_data.dsfps_shipping_class',
                'help'     => __('Shipping classes are used by certain shipping methods to group similar products.', 'free-product-sample' ),
                'tooltip'  => __('Shipping classes are used by certain shipping methods to group similar products.', 'free-product-sample' ),
                'options'  => $options,
              ),
              'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "simple" && editedProduct.type !== "variable"',
                ],
            ],
            )
          );
          $options = array(); 
          $options[] = array(
              'value' => '-1',
              'label' => __( 'Same as a parent', 'free-product-sample' )
          );
          if ( ! empty( $shipping_classes ) && ! is_wp_error( $shipping_classes ) ) {
              foreach ($shipping_classes as $ship_class) {
                  $options[] = array(
                      'value' => $ship_class->term_id,
                      'label' => __($ship_class->name, 'free-product-sample' )
                  );
              }
          }
          $dsfps_sample_product_section->add_block(
            array(
              'id'         => 'dsfps_variation_shipping_class-metafield',
              'blockName'  => 'woocommerce/product-select-field',
              'order'      => 23,
              'attributes' => array(
                'label'    => __('Variation Shipping class', 'free-product-sample' ),
                'property' => 'meta_data.dsfps_variation_shipping_class',
                'help'     => __('Shipping classes are used by certain shipping methods to group similar products.', 'free-product-sample' ),
                'tooltip'  => __('Shipping classes are used by certain shipping methods to group similar products.', 'free-product-sample' ),
                'options'  => $options,
              ),
              'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "variation"'
                ],
            ],
            'disableConditions' => [
                [
                    'expression' => 'editedProduct.meta_data.fps_manage_variation_sample !== "yes"',
                ],
            ],
            )
          );
        $tax_classes = WC_Tax::get_tax_rate_classes();
        if ( is_array( $tax_classes ) && ! empty( $tax_classes ) ) {
            $options = array();
            $options[] = array(
                'value' => '',
                'label' => __( 'Same as a sample settings page', 'free-product-sample' )
            );
            $options[] = array(
                'value' => 'standard',
                'label' => __( 'Standard', 'free-product-sample' )
            );
            foreach ( $tax_classes as $tax_class ) {
              $options[] = array(
                'value' => $tax_class->slug,
                'label' => $tax_class->name,
              );
          }
        }

       
        $dsfps_sample_product_section->add_block(
            array(
              'id'         => 'dsfps_tax_class-metafield',
              'blockName'  => 'woocommerce/product-select-field',
              'order'      => 24,
              'attributes' => array(
                'label'    =>  __('Tax class', 'free-product-sample' ),
                'property' => 'meta_data.dsfps_tax_class',
                'help'     =>  __('Choose a tax class for this product. Tax classes are used to apply different tax rates specific to certain types of product.', 'free-product-sample' ),
                'tooltip'  =>  __('Choose a tax class for this product. Tax classes are used to apply different tax rates specific to certain types of product.', 'free-product-sample' ),
                'options'  => $options,
              ),
              'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "simple" && editedProduct.type !== "variable"',
                ],
            ],
            )
          );
          if ( is_array( $tax_classes ) && ! empty( $tax_classes ) ) {
            $options = array();
            $options[] = array(
                'value' => '',
                'label' => __( 'Same as parent', 'free-product-sample' )
            );
            $options[] = array(
                'value' => 'standard',
                'label' => __( 'Standard', 'free-product-sample' )
            );
            foreach ( $tax_classes as $tax_class ) {
              $options[] = array(
                'value' => $tax_class->slug,
                'label' => $tax_class->name,
              );
          }
        }
          $dsfps_sample_product_section->add_block(
            array(
              'id'         => 'dsfps_variation_tax_class-metafield',
              'blockName'  => 'woocommerce/product-select-field',
              'order'      => 25,
              'attributes' => array(
                'label'    =>  __('Variation Tax class', 'free-product-sample' ),
                'property' => 'meta_data.dsfps_variation_tax_class',
                'help'     =>  __('Choose a tax class for this product. Tax classes are used to apply different tax rates specific to certain types of product.', 'free-product-sample' ),
                'tooltip'  =>  __('Choose a tax class for this product. Tax classes are used to apply different tax rates specific to certain types of product.', 'free-product-sample' ),
                'options'  => $options,
              ),
              'hideConditions' => [
                [
                    'expression' => 'editedProduct.type !== "variation"'
                ],
            ],
            'disableConditions' => [
                [
                    'expression' => 'editedProduct.meta_data.fps_manage_variation_sample !== "yes"',
                ],
            ],
            )
        );
    }
}
}
<?php
/** @var array $model */

use SW_WAPF_PRO\Includes\Classes\Util;

$formula = empty( $model['field']->options['formula'] ) ? 0 : esc_attr( $model['field']->options['formula'] );
$result_text = empty( $model['field']->options['result_text'] ) ? '{result}' : esc_attr( $model['field']->options['result_text'] );
$type = isset( $model['field']->options['calc_type'] ) && $model['field']->options['calc_type'] === 'cost' ? 'cost' : 'default';
$result_format = $type === 'default' ? ( empty( $model['field']->options['result_format'] ) ? 'number' : 'none' ) : '';

$attributes = [
    'class'             => 'wapf-input input-' . $model['field']->id,
    'data-field-id '    => $model['field']->id
];

if( $type === 'cost' ) {
    $attributes['data-wapf-pricetype'] = 'fx';
    $attributes['data-wapf-price'] = $formula;
}

$attributes = Util::array_to_attributes( $attributes );
// but value="idle" so tyhat calculateOptionsTotal would calculate this field.
?>

<div class="wapf-calc-wrapper">
    <span class="wapf-calc-text" data-type="<?php echo $type ?>" data-format="<?php echo $result_format ?>" data-txt="<?php echo $result_text ?>" data-formula="<?php echo $formula ?>"></span>
    <input data-is-calc="1" type="hidden" <?php echo $attributes ?> data-fid="<?php echo $model['field']->id;?>" value="idle" name="wapf[field_<?php echo $model['field']->id;?>]" />
</div>
<?php
/* @var $model array */
use SW_WAPF_PRO\Includes\Classes\Util;
?>
<div rv-controller="VariablesCtrl" data-variables="<?php echo Util::to_html_attribute_string($model['variables']); ?>">
    <input type="hidden" name="wapf-variables" rv-value="json" />

    <div class="wapf-collapsible__holder" style="padding:0 25px;" rv-show="variables|isNotEmpty">
        <div rv-each-variable="variables" class="wapf-collapsible__wrapper" rv-data-variable-id="variable.name">
            <div class="wapf-collapsible__header">
                <div class="wapf-collapsible__sort" title="<?php _e('Drag & drop','sw-wapf');?>">☰</div>
                <div class="wapf-collapsible__name">
                    var_{variable.name}
                </div>
                <div class="wapf-collapsible__actions">
                    <a class="wapf-variable-dupe" href="#" rv-on-click="duplicateVariable" title="<?php _e('Duplicate variable','sw-wapf');?>"><?php _e('Duplicate', 'sw-wapf') ?></a>
                    <a href="#" style="color: #a00 !important" title="<?php _e('Delete variable','sw-wapf');?>" rv-on-click="deleteVariable"><?php _e('Delete', 'sw-wapf') ?></a>
                </div>
            </div>
            <div class="wapf-collapsible__body">
                <div class="wapf-field__setting">
                    <div class="wapf-setting__label">
                        <label>
                            <?php _e('Variable name','sw-wapf');?>
                        </label>
                        <p class="wapf-description">
                            <?php _e('A unique key to identify your variable. Use this key in pricing formulas.','sw-wapf'); ?>
                        </p>
                    </div>
                    <div class="wapf-setting__input">
                        <div>
                            <div class="wapf-input-with-prepend">
                                <div class="wapf-input-prepend">var_</div>
                                <input type="text" rv-value="variable.name" rv-on-keyup="onChangeVariableName"/>
                            </div>
                        </div>
                        <div class="wapf-option-note">
                            <?php _e('Should only contain letters, numbers, or underscores.','sw-wapf'); ?>
                        </div>
                    </div>
                </div>
                <div class="wapf-field__setting">
                    <div class="wapf-setting__label">
                        <label>
                            <?php _e('Standard value','sw-wapf');?>
                        </label>
                        <p class="wapf-description">
                            <?php _e('The default value of your variable.','sw-wapf'); ?>
                        </p>
                    </div>
                    <div class="wapf-setting__input">
                        <input type="text" rv-value="variable.default" rv-on-change="onChange"  />
                        <p style="opacity:.7"><?php _e('This should be a number or a formula.','sw-wapf'); ?></p>
                    </div>
                </div>
                <div class="wapf-field__setting">
                    <div class="wapf-setting__label">
                        <label>
                            <?php _e('Value changes','sw-wapf');?>
                        </label>
                        <p class="wapf-description">
                            <?php _e('Add rules when the value of this variable should change.','sw-wapf'); ?>
                        </p>
                    </div>
                    <div class="wapf-setting__input">
                        <div class="variable_rule__wrapper">

                            <table>
                                <tr rv-each-variablerule="variable.rules" class="hide_del">
                                    <td>
                                        <strong><?php _e('If this happens','sw-wapf'); ?></strong>
                                        <select rv-on-change="onVariableRuleTypeChange" rv-value="variablerule.type">
                                            <option rv-disabled="canAddFieldToVariableRule|neq true" value="field"><?php _e('Field value changes','sw-wapf');?></option>
                                            <option value="qty"><?php _e('Product quantity changes','sw-wapf');?></option>
                                        </select>
                                    </td>
                                    <td rv-show="variablerule.type|eq 'qty'">
                                        <strong><?php _e('And quantity','sw-wapf'); ?></strong>
                                        <select rv-value="variablerule.condition" rv-on-change="onChange">
                                            <option value="=="><?php _e(' is equal to','sw-wapf'); ?></option>
                                            <option value="!="><?php _e('is not equal to','sw-wapf'); ?></option>
                                            <option value="gt"><?php _e('is greater than','sw-wapf'); ?></option>
                                            <option value="lt"><?php _e('is lesser than','sw-wapf'); ?></option>
                                        </select>
                                    </td>
                                    <td rv-show="variablerule.type|eq 'qty'">
                                        <input step="any" min="1" rv-on-change="onChange" rv-on-keyup="onChange" type="number" rv-value="variablerule.value" />
                                    </td>
                                    <td rv-show="variablerule.type|eq 'qty'">&nbsp;</td>
                                    <td rv-show="variablerule.type |eq 'field'" style="width: 20%;">
                                        <strong><?php _e('This field changes','sw-wapf'); ?></strong>
                                        <select rv-value="variablerule.field" rv-on-change="onChange" >
                                            <option rv-each-field="fields" rv-value="field.id">{field.label}</option>
                                        </select>
                                    </td>
                                    <td rv-show="variablerule.type |eq 'field'" style="width: 20%">
                                        <select rv-value="variablerule.condition" rv-on-change="onChange" >
                                            <option rv-each-condition="availableConditions | filterConditions variablerule.field fields" rv-value="condition.value">{ condition.label }</option>
                                        </select>
                                    </td>
                                    <td rv-show="variablerule.type |eq 'field'" style="width: 20%;">
                                        
                                        <input rv-if="variablerule.condition | conditionNeedsValue availableConditions 'text' fields variablerule.field" rv-on-keyup="onChange" type="text" rv-value="variablerule.value" />
                                        
                                        <input rv-if="variablerule.condition | conditionNeedsValue availableConditions 'number' fields variablerule.field" step="any" rv-on-change="onChange" rv-on-keyup="onChange" type="number" rv-value="variablerule.value" />
                                       
                                        <select rv-if="variablerule.condition | conditionNeedsValue availableConditions 'options' fields variablerule.field" rv-on-change="onChange" rv-value="variablerule.value">
                                            <option rv-each-v="fields | query 'first' 'id' '===' variablerule.field 'get' 'choices'" rv-value="v.slug">{v.label}</option>
                                        </select>
                                       
                                        <input rv-if="variablerule.condition | conditionDoesntNeedValue availableConditions fields variablerule.field" disabled type="text" />
                                       
                                        <select rv-if="variablerule.condition | conditionNeedsValue availableConditions 'product_choice' fields variablerule.field" rv-on-change="onChange" rv-value="variablerule.value">
                                            <option rv-each-v="fields | query 'first' 'id' '===' variablerule.field 'get' 'choices'" rv-value="v.slug">{v.label}</option>
                                        </select>
                                        
                                        <input rv-if="variablerule.condition | conditionNeedsValue availableConditions 'product_id' fields variablerule.field" placeholder="<?php _e( 'Product ID', 'sw-wapf' ) ?>" step="1" rv-on-change="onChange" rv-on-keyup="onChange" type="number" rv-value="variablerule.value" />
                                    </td>
                                    <td>
                                        <strong><?php _e('Variable value is','sw-wapf');?></strong>
                                        <input type="text" rv-value="variablerule.variable" rv-on-change="onChange"/>
                                    </td>
                                    <td style="width: 30px;vertical-align: bottom;padding-bottom:1em">
                                        <a href="#" title="<?php _e('Delete','sw-wapf');?>" rv-on-click="deleteVariableRule" class="wapf-button--tiny-rounded wapf-del btn-del"></a>
                                    </td>
                                </tr>
                            </table>

                        </div>
                        <div style="padding-top:15px;">
                            <a href="#" rv-on-click="addVariableRule" class="button"><?php _e('Add new rule','sw-wapf'); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="wapf-list--empty">
        <a href="#" class="button button-primary button-large" rv-on-click="addEmptyVariable"><?php _e('Add new variable','sw-wapf'); ?></a>
    </div>

</div>
<?php /* @var $model array */ ?>

<div style="width:100%;"  class="wapf-field__conditionals">

    <div class="wapf-field__conditionals__container">
        <div rv-if="fieldsForConditionals.fields | isEmpty" class="wapf-lighter">
            <?php _e('You need atleast 2 fields to create conditional rules. Add another field first.','sw-wapf');?>
        </div>
        <div rv-if="fieldsForConditionals.fields | isNotEmpty">
            <strong rv-show="field.conditionals|isNotEmpty"><?php _e('Show this field if','sw-wapf'); ?></strong>
            <div rv-each-conditional="field.conditionals">
                <table style="padding-bottom:10px;width:100%;" class="wapf-field__conditional">
                    <tr class="conditional__rule hide_del" rv-each-rule="conditional.rules">
                        <td style="width: 31%">
                            <select rv-on-change="onConditionalFieldChange" rv-value="rule.field">
                                <option rv-each-fieldobj="fieldsForConditionals.fields" rv-value="fieldobj.id">{fieldobj.label}</option>
                            </select>
                        </td>
                        <td style="width: 21%">
                            <select rv-on-change="onConditionalConditionChange" rv-value="rule.condition">
                                <option rv-each-condition="rule.possibleConditions" rv-value="condition.value">{ condition.label }</option>
                            </select>
                        </td>
                        <td>
                            <input rv-if="rule.selectedCondition.type | eq 'text'" rv-on-keyup="onChange" type="text" rv-value="rule.value" />
                            <input rv-if="rule.selectedCondition.type | eq 'number'" step="any" rv-on-change="onChange" rv-on-keyup="onChange" type="number" rv-value="rule.value" />
                            <!--<select rv-if="rule.selectedCondition.type | eq 'options'" rv-on-change="onChange" rv-value="rule.value">
                                <option rv-each-w="fields | query 'getChoices' rule.field" rv-value="w.slug">{w.label}</option>
                            </select>-->
                            <select rv-if="rule.selectedCondition.type | eq 'options'" rv-on-change="onChange" rv-value="rule.value">
                                <option rv-each-w="rule.selectedCondition._choices" rv-value="w.slug">{w.label}</option>
                            </select>
                            <select rv-if="rule.selectedCondition.type | eq 'product_choice'" rv-on-change="onChange" rv-value="rule.value">
                                <option rv-each-w="rule.selectedCondition._choices" rv-value="w.slug">{w.label}</option>
                            </select>
                            <input rv-if="rule.selectedCondition.type | eq 'product_id'" placeholder="<?php _e( 'Product ID', 'sw-wapf' ) ?>" step="1" rv-on-change="onChange" rv-on-keyup="onChange" type="number" rv-value="rule.value" />
                            <input rv-if="rule.selectedCondition.type | eq false" disabled type="text"/>
                            <div style="opacity: .7;font-size: 90%;padding-top: 5px;display: inline-block" rv-if="rule.selectedCondition.desc | isNotEmpty">{rule.selectedCondition.desc}</div>
                        </td>

                        <td style="width: 40px">
                            <a href="#" rv-show="conditional.rules | isLastIteration $index" rv-on-click="addRule" class="button button-small">+ <?php _e('And','sw-wapf'); ?></a>
                        </td>
                        <td style="width: 30px;vertical-align: middle">
                          <a href="#" title="<?php _e('Delete','sw-wapf');?>" rv-on-click="deleteRule" class="wapf-button--tiny-rounded wapf-del btn-del"></a>
                        </td>
                    </tr>
                </table>
                <div rv-if="$index | lt field.conditionals"><b><?php _e('Or','sw-wapf');?></b></div>
            </div>
            <div style="padding-top: 5px;">
                <a href="#" rv-on-click="addConditional" class="button"><?php _e('Add new rule group','sw-wapf'); ?></a>
            </div>
        </div>
    </div>

</div>

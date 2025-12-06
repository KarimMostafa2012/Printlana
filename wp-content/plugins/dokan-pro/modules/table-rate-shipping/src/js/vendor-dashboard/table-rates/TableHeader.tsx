import { SimpleCheckbox } from '@getdokan/dokan-ui';
import { __, sprintf } from '@wordpress/i18n';
import DragIcon from './DragIcon';
import { DokanTooltip as Tooltip } from '@dokan/components';

interface TableHeaderProps {
    onSelectAll: ( checked: boolean ) => void;
    isAllSelected: boolean;
}

export const TableHeader = ( {
    onSelectAll,
    isAllSelected,
}: TableHeaderProps ) => (
    <thead className="text-xs text-gray-700 uppercase bg-gray-50">
        <tr>
            <th scope="col" className="bg-gray-50 py-4 pr-0 !pl-4">
                <SimpleCheckbox
                    input={ {
                        id: 'select-all-rows',
                        name: 'select-all-rows',
                        type: 'checkbox',
                    } }
                    onChange={ ( e ) => onSelectAll( e.target.checked ) }
                    checked={ isAllSelected }
                />
            </th>
            <th scope="col" className="bg-gray-50 py-4 px-2.5">
                <Tooltip
                    className="ml-2 text-gray-400 self-center"
                    content={ __( 'Draggable shipping rates.', 'dokan' ) }
                >
                    <span>
                        <DragIcon
                            className={ `fill-gray-900 cursor-default` }
                        />
                    </span>
                </Tooltip>
            </th>
            <th scope="col" className="bg-gray-50 py-4 px-2.5">
                <Tooltip
                    className="ml-2 text-gray-400 self-center"
                    content={ __(
                        'Shipping class this rate applies to.',
                        'dokan'
                    ) }
                >
                    <span>{ __( 'Shipping Class', 'dokan' ) }</span>
                </Tooltip>
            </th>
            <th scope="col" className="bg-gray-50 py-4 px-2.5">
                <Tooltip
                    className="ml-2 text-gray-400 self-center"
                    content={ __( 'Condition vs. destination.', 'dokan' ) }
                >
                    <span>{ __( 'Condition', 'dokan' ) }</span>
                </Tooltip>
            </th>
            <th scope="col" className="bg-gray-50 py-4 px-2.5">
                <Tooltip
                    className="ml-2 text-gray-400 self-center"
                    content={ __(
                        'Bottom and top range for the selected condition.',
                        'dokan'
                    ) }
                >
                    <span>{ __( 'Min-Max', 'dokan' ) }</span>
                </Tooltip>
            </th>
            <th scope="col" className="bg-gray-50 py-4 px-2.5">
                <Tooltip
                    className="ml-2 text-gray-400 self-center"
                    content={ __(
                        'Break at this point. For per-order rates, no rates other than this will be offered. For calculated rates, this will stop any further rates being matched.',
                        'dokan'
                    ) }
                >
                    <span>{ __( 'Break', 'dokan' ) }</span>
                </Tooltip>
            </th>
            <th scope="col" className="bg-gray-50 py-4 px-2.5">
                <Tooltip
                    className="ml-2 text-gray-400 self-center"
                    content={ __(
                        'Enable this option to disable all rates/this shipping method if this row matches any item/line/class being quoted.',
                        'dokan'
                    ) }
                >
                    <span>{ __( 'Abort', 'dokan' ) }</span>
                </Tooltip>
            </th>
            <th scope="col" className="bg-gray-50 py-4 px-2.5">
                <Tooltip
                    className="ml-2 text-gray-400 self-center"
                    content={ __(
                        'Cost for shipping the order, including tax.',
                        'dokan'
                    ) }
                >
                    <span>{ __( 'Row cost', 'dokan' ) }</span>
                </Tooltip>
            </th>
            <th scope="col" className="bg-gray-50 py-4 px-2.5">
                <Tooltip
                    className="ml-2 text-gray-400 self-center"
                    content={ __( 'Cost per item, including tax.', 'dokan' ) }
                >
                    <span>{ __( 'Item cost', 'dokan' ) }</span>
                </Tooltip>
            </th>
            <th scope="col" className="bg-gray-50 py-4 px-2.5">
                <Tooltip
                    className="ml-2 text-gray-400 self-center"
                    content={ __( 'Cost per weight unit.', 'dokan' ) }
                >
                    <span>
                        { sprintf(
                            /* translators: 1) Shipping Cost */
                            __( '%1$s cost', 'dokan' ),
                            dokanTableRateShippingHelper.weight_unit
                        ) }
                    </span>
                </Tooltip>
            </th>
            <th scope="col" className="bg-gray-50 py-4 px-2.5">
                <Tooltip
                    className="ml-2 text-gray-400 self-center"
                    content={ __( 'Percentage of total to charge.', 'dokan' ) }
                >
                    <span>{ __( '% cost', 'dokan' ) }</span>
                </Tooltip>
            </th>
            <th scope="col" className="bg-gray-50 py-4 px-2.5">
                <Tooltip
                    className="ml-2 text-gray-400 self-center"
                    content={ __(
                        'Label for the shipping method which the user will be presented.',
                        'dokan'
                    ) }
                >
                    <span>{ __( 'Label', 'dokan' ) }</span>
                </Tooltip>
            </th>
        </tr>
    </thead>
);

export default TableHeader;

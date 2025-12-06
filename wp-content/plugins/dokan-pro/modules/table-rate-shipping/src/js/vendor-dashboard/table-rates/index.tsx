import { TableRow } from './TableRow';
import { TableHeader } from './TableHeader';
import { useState } from '@wordpress/element';
import { DokanModal } from '@dokan/components';
import { ActionButtons } from './ActionButtons';
import { __, sprintf, _n } from '@wordpress/i18n';
import TableRatesSkeleton from './TableRatesSkeleton';
import { useTableRates } from '../hooks/useTableRates';

interface TableRatesProps {
    zoneId: number;
    instanceId: number;
}

const TableRates = ( { zoneId, instanceId }: TableRatesProps ) => {
    const [ showModal, setShowModal ] = useState( false );
    const {
        selectedRows,
        tableData,
        isLoading,
        isSaving,
        handleSelectAll,
        handleSelectRow,
        handleDuplicateRows,
        handleAddShippingRate,
        handleDeleteRows,
        handleTableDataUpdate,
        handleSave,
        handleOrderUpdate,
    } = useTableRates( zoneId, instanceId );

    const rateConditionOptions = [
        { value: '', label: __( 'None', 'dokan' ) },
        { value: 'price', label: __( 'Price', 'dokan' ) },
        { value: 'weight', label: __( 'Weight', 'dokan' ) },
        { value: 'items', label: __( 'Item count', 'dokan' ) },
        ...( dokanTableRateShippingHelper?.shipping_class?.length > 0
            ? [
                  {
                      value: 'items_in_class',
                      label: __( 'Item count (same class)', 'dokan' ),
                  },
              ]
            : [] ),
    ];

    const shippingClassOptions = [
        { value: '', label: __( 'Any class', 'dokan' ) },
        { value: '0', label: __( 'No class', 'dokan' ) },
        ...dokanTableRateShippingHelper?.shipping_class?.map(
            ( classItem ) => ( {
                value: String( classItem?.term_id ),
                label: classItem?.name,
            } )
        ),
    ];

    if ( isLoading ) {
        return <TableRatesSkeleton />;
    }

    return (
        <div className="bg-white sm:gap-4">
            { /* Header section */ }
            <div className="py-6 px-1">
                <h3 className="text-base font-medium text-gray-900 mb-1">
                    { __( 'Table Rates', 'dokan' ) }
                </h3>
                <p className="text-sm text-gray-500">
                    { __(
                        'Define your table rates here in order of priority.',
                        'dokan'
                    ) }
                </p>
            </div>

            { /* Table section */ }
            <div className="w-full pr-1">
                <div className="bg-white shadow-md rounded-lg overflow-scroll max-h-[550px]">
                    <table
                        id="table-rates-shipping-table"
                        className="dataviews-view-table w-full text-sm text-left text-gray-500"
                    >
                        <TableHeader
                            onSelectAll={ handleSelectAll }
                            isAllSelected={
                                tableData.length > 0 &&
                                selectedRows.length === tableData.length
                            }
                        />
                        <tbody>
                            <TableRow
                                tableData={ tableData }
                                selectedRows={ selectedRows }
                                onSelectRow={ handleSelectRow }
                                onUpdate={ handleTableDataUpdate }
                                handleOrderUpdate={ handleOrderUpdate }
                                shippingClassOptions={ shippingClassOptions }
                                rateConditionOptions={ rateConditionOptions }
                            />
                        </tbody>
                    </table>
                </div>

                { /* Action buttons */ }
                <ActionButtons
                    onSave={ handleSave }
                    isSaving={ isSaving }
                    onAdd={ handleAddShippingRate }
                    onDuplicate={ handleDuplicateRows }
                    onDelete={ () => setShowModal( true ) }
                    hasSelectedRows={ selectedRows.length > 0 }
                />

                { /* Delete confirmation modal */ }
                <DokanModal
                    isOpen={ showModal }
                    loading={ isLoading }
                    onConfirm={ handleDeleteRows }
                    onClose={ () => setShowModal( false ) }
                    namespace="table-rate-method-delete"
                    dialogHeader={ __(
                        'Confirm Shipping Rate Deletion',
                        'dokan'
                    ) }
                    confirmationTitle={ _n(
                        'Delete Table Rate',
                        'Delete Table Rates',
                        selectedRows?.length,
                        'dokan'
                    ) }
                    confirmationDescription={ __(
                        'Are you sure you want to delete the selected shipping rate(s)? This action cannot be undone and may affect how shipping is calculated for your customers.',
                        'dokan'
                    ) }
                />
            </div>
        </div>
    );
};

export default TableRates;

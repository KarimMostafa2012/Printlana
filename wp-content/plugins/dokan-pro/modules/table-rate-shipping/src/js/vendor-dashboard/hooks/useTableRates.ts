import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useToast } from '@getdokan/dokan-ui';
import { TableRate, UseTableRatesReturn } from "../definations";

export const useTableRates = ( zoneId: string, instanceId: string ): UseTableRatesReturn => {
    const [ selectedRows, setSelectedRows ] = useState<string[]>([]);
    const [ tableData, setTableData ] = useState<TableRate[]>([]);
    const [ isLoading, setIsLoading ] = useState(true);
    const [ isSaving, setIsSaving ] = useState(false);
    const toast = useToast();

    // Fetch table rates on mount
    const fetchTableRates = useCallback( async () => {
        try {
            const response = await apiFetch<TableRate[]>({
                path: `/dokan/v1/shipping/table-rate/rates/zone/${zoneId}/instance/${instanceId}`,
            });
            setTableData(response);
        } catch (error: any) {
            toast({
                type: 'error',
                title: __('Error loading table rates', 'dokan'),
                subtitle: error.message,
            });
        } finally {
            setIsLoading(false);
        }
    }, [ zoneId, instanceId ] );

    useEffect(() => {
        fetchTableRates();
    }, [fetchTableRates]);

    // Selection handlers
    const handleSelectAll = useCallback((checked: boolean) => {
        setSelectedRows(checked ? tableData.map(row => row.rate_order) : []);
    }, [tableData]);

    const handleSelectRow = useCallback((rateOrder: string, checked: boolean) => {
        setSelectedRows(prev => {
            if (checked) {
                return [...prev, rateOrder];
            }
            return prev.filter(orderId => orderId !== rateOrder);
        });
    }, []);

    // Row operations
    const handleDuplicateRows = useCallback(() => {
        if (selectedRows.length === 0) return;

        const duplicatedRows = selectedRows.map((selectedRateOrder, index) => {
            const originalRow = tableData.find(row => row.rate_order === selectedRateOrder);
            if (!originalRow) return null;

            return {
                ...originalRow,
                rate_id: '0', // New row
                rate_order: String(tableData.length + index)
            };
        }).filter(Boolean) as TableRate[];

        setTableData(prev => [...prev, ...duplicatedRows]);
        setSelectedRows([]); // Clear selection after duplication
    }, [selectedRows, tableData]);

    const handleAddShippingRate = useCallback(() => {
        const newRow: TableRate = {
            rate_id: '0',
            zone_id: zoneId,
            instance_id: instanceId,
            rate_class: '',
            rate_condition: '',
            rate_min: '',
            rate_max: '',
            rate_cost: '',
            rate_cost_per_item: '',
            rate_cost_per_weight_unit: '',
            rate_cost_percent: '',
            rate_label: '',
            rate_priority: '0',
            rate_order: String(tableData.length),
            rate_abort: '0',
            rate_abort_reason: ''
        };

        setTableData(prev => [...prev, newRow]);
    }, [tableData.length, zoneId, instanceId]);

    const handleDeleteRows = useCallback(async () => {
        if (selectedRows.length === 0) return;

        // Filter out rows that are already stored in the database.
        const rowsToDelete = tableData.filter(row =>
            selectedRows.includes(row.rate_order) && parseInt(row.rate_id) > 0
        );

        // Optimistic update
        setTableData(prev => prev.filter(row => !selectedRows.includes(row.rate_order)));
        setSelectedRows([]);

        // API call for existing records
        if (rowsToDelete.length > 0) {
            try {
                await apiFetch({
                    path: `/dokan/v1/shipping/table-rate/rates/zone/${zoneId}/instance/${instanceId}`,
                    method: 'DELETE',
                    data: {
                        rate_ids: rowsToDelete.map(row => row.rate_id)
                    },
                });

                toast({
                    type: 'success',
                    title: __('Selected shipping rates deleted successfully', 'dokan'),
                });
            } catch (error: any) {
                // Revert on error
                setTableData(prev => [...prev, ...rowsToDelete]);
                toast({
                    type: 'error',
                    title: __('Error deleting shipping rates', 'dokan'),
                    subtitle: error.message,
                });
            }
        }
    }, [selectedRows, tableData, zoneId, instanceId]);

    // Data update handlers
    const handleTableDataUpdate = useCallback((rateOrder: string, field: keyof TableRate, value: string) => {
        setTableData(prev =>
            prev.map(item =>
                item.rate_order === rateOrder ? { ...item, [field]: value } : item
            )
        );
    }, []);

    const handleOrderUpdate = useCallback( ( updatedItems: TableRate[] ) => {
        const reorderedItems = updatedItems.map( ( item, index ) => ( {
            ...item,
            rate_order: String( index )
        } ) );
        setTableData( [ ...reorderedItems ] );
    }, [] );

    // Helper function to prepare table rate data.
    const prepareTableRateData = ( tableData ) => {
        if ( ! tableData?.length ) {
            return {};
        }

        const fieldMapping = {
            rate_id                   : 'rate_ids',
            rate_class                : 'shipping_class',
            rate_condition            : 'shipping_condition',
            rate_min                  : 'shipping_min',
            rate_max                  : 'shipping_max',
            rate_cost                 : 'shipping_cost',
            rate_cost_per_item        : 'shipping_per_item',
            rate_cost_per_weight_unit : 'shipping_cost_per_weight',
            rate_cost_percent         : 'cost_percent',
            rate_label                : 'shipping_label',
            rate_priority             : 'shipping_priority',
            rate_abort                : 'shipping_abort',
            rate_abort_reason         : 'shipping_abort_reason'
        };

        return Object.entries( fieldMapping ).reduce( ( acc, [ tableKey, apiKey ] ) => {
            acc[ apiKey ] = tableData.map( item => item[ tableKey ] );
            return acc;
        }, {} );
    };

    // Save all changes
    const handleSave = useCallback(async () => {
        setIsSaving(true);
        try {
            await apiFetch({
                path: `/dokan/v1/shipping/table-rate/rates/zone/${zoneId}/instance/${instanceId}`,
                method: 'POST',
                data: {
                    preparedData : prepareTableRateData( tableData ),
                    zone_id      : zoneId,
                    instance_id  : instanceId
                },
            })
                .then(( response ) => {
                    setTableData( [ ...response.rates.data ] );
                    toast({
                        type: 'success',
                        title: __('Table rates saved successfully', 'dokan'),
                    });
                });
        } catch (error: any) {
            toast({
                type: 'error',
                title: __('Error saving table rates', 'dokan'),
                subtitle: error.message,
            });
        } finally {
            setIsSaving(false);
        }
    }, [tableData, zoneId, instanceId]);

    return {
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
    };
};

import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { useToast } from '@getdokan/dokan-ui';
import {
    DataViews,
    DokanModal,
    PriceHtml,
    DateTimeHtml,
} from '@dokan/components';
import { SubscriptionOrder } from '../definition/SubscriptionOrders';

const SubscriptionOrders = ( { vendorId } ) => {
    const toast = useToast();
    const [ isLoading, setIsLoading ] = useState( true );
    const [ ordersData, setOrdersData ] = useState( [] );
    const [ totalOrders, setTotalOrders ] = useState( 0 );
    const [ orderCancellationUrl, setOrderCancellationUrl ] = useState( '' );
    const [ isConfirmationModalOpen, setIsConfirmationModalOpen ] =
        useState( false );

    // Fields for handle the table columns.
    const fields = [
        {
            id: 'id',
            label: __( 'Order', 'dokan' ),
            render: ( { item } ) => (
                <div>
                    { isLoading ? (
                        <span className="block w-10 h-3 rounded bg-gray-200 animate-pulse"></span>
                    ) : (
                        <a
                            className="font-semibold cursor-pointer dokan-link"
                            href={ item?.actions.view?.url }
                            target="_blank"
                            rel="noreferrer"
                        >
                            { item.id }
                        </a>
                    ) }
                </div>
            ),
            enableSorting: false,
            enableGlobalSearch: false,
        },
        {
            id: 'date_created',
            label: __( 'Date', 'dokan' ),
            render: ( { item } ) => (
                <div>
                    { isLoading ? (
                        <span className="block w-28 h-3 rounded bg-gray-200 animate-pulse"></span>
                    ) : (
                        <DateTimeHtml.Date date={ item.date_created } />
                    ) }
                </div>
            ),
            enableSorting: false,
            enableGlobalSearch: false,
        },
        {
            id: 'status',
            label: __( 'Status', 'dokan' ),
            render: ( { item } ) => (
                <div>
                    { isLoading ? (
                        <span className="block w-16 h-3 rounded bg-gray-200 animate-pulse"></span>
                    ) : (
                        capitalizeFirstLetter( item.status )
                    ) }
                </div>
            ),
            enableSorting: false,
            enableGlobalSearch: false,
        },
        {
            id: 'total',
            label: __( 'Total', 'dokan' ),
            render: ( { item } ) => (
                <div>
                    { isLoading ? (
                        <span className="block w-14 h-3 rounded bg-gray-200 animate-pulse"></span>
                    ) : (
                        <PriceHtml price={ item.total } />
                    ) }
                </div>
            ),
            enableSorting: false,
            enableGlobalSearch: false,
        },
    ];

    // Necessary actions for the table rows.
    const actions = [
        {
            id: 'order-pay',
            label: '',
            isPrimary: true,
            isEligible: ( item ) => !! item.actions.pay,
            callback: ( orders ) => {
                const order = orders[ 0 ];
                window.open( decodeURL( order?.actions?.pay?.url ), '_blank' );
            },
            icon: () => (
                <span
                    className={ `px-2 bg-transparent font-medium text-dokan-link hover:text-dokan-link-hover  pr-4 text-sm` }
                >
                    { __( 'Pay', 'dokan' ) }
                </span>
            ),
        },
        {
            id: 'order-view',
            label: '',
            isPrimary: true,
            isEligible: ( item ) => !! item.actions.view,
            callback: ( orders ) => {
                const order = orders[ 0 ];
                window.open( decodeURL( order?.actions?.view?.url ), '_blank' );
            },
            icon: () => (
                <span
                    className={ `px-2 bg-transparent font-medium text-dokan-link hover:text-dokan-link-hover pr-r text-sm` }
                >
                    { __( 'View', 'dokan' ) }
                </span>
            ),
        },
        {
            id: 'order-cancel',
            label: '',
            isPrimary: true,
            isEligible: ( item ) => !! item.actions.cancel,
            icon: () => {
                return (
                    <span
                        className={ `px-2 bg-transparent font-medium text-dokan-danger hover:text-dokan-danger-hover text-sm` }
                    >
                        { __( 'Cancel', 'dokan' ) }
                    </span>
                );
            },
            callback: ( orders ) => {
                handleOrderCancellation( orders[ 0 ] );
            },
        },
    ];

    // Capitalize the first letter of a word.
    const capitalizeFirstLetter = ( word ) => {
        if ( ! word ) {
            return '';
        }
        return word.charAt( 0 ).toUpperCase() + word.slice( 1 );
    };

    // Decode endoded URL.
    const decodeURL = ( url ) => {
        return decodeURIComponent( url.replace( /&amp;/g, '&' ) );
    };

    // Handle cancel order action.
    const handleOrderCancellation = ( order: SubscriptionOrder ) => {
        setIsConfirmationModalOpen( true );
        setOrderCancellationUrl( order?.actions?.cancel?.url );
    };

    // Handle cancel order confirmation.
    const cancelOrder = () => {
        if ( ! orderCancellationUrl ) {
            return;
        }

        window.open( decodeURL( orderCancellationUrl ), '_blank' );

        setIsConfirmationModalOpen( false );
    };

    // Data view default layout.
    const defaultLayouts = {
        table: {},
        grid: {},
        list: {},
        density: 'comfortable', // Use density pre-defined values: comfortable, compact, cozy
    };

    // View state for handle the table view.
    const [ view, setView ] = useState( {
        perPage: 10,
        page: 1,
        type: 'table',
        titleField: 'id',
        status: 'completed,failed,cancelled',
        layout: { ...defaultLayouts },
        fields: fields.map( ( field ) =>
            field.id !== 'id' ? field.id : ''
        ),
    } );

    // Handle orders fetching from the server.
    const fetchOrders = async () => {
        setIsLoading( true );

        try {
            // Query arguments for the post fetching.
            const queryArgs = {
                per_page: view?.perPage ?? 10,
                page: view?.page ?? 1,
            };

            const response: Response = await apiFetch( {
                path: addQueryArgs(
                    `/dokan/v1/vendor-subscription/orders/${ vendorId }`,
                    {
                        ...queryArgs,
                    }
                ),
                method: 'GET',
                parse: false,
            } );

            const orders = await response.json();
            const totalItems = parseInt( response.headers.get( 'X-WP-Total' ) );

            setOrdersData( orders );
            setTotalOrders( totalItems ); // Set total posts count.
        } catch ( error ) {
            // Handling the case where `error` is a Response object
            if ( error instanceof Response ) {
                const errorData = await error.json().catch( () => null );

                toast( {
                    type: 'error',
                    title:
                        __( 'Error fetching orders:', 'dokan' ) +
                        ( errorData?.message ||
                            error.statusText ||
                            __( 'Unknown error', 'dokan' ) ),
                } );
            } else {
                toast( {
                    type: 'error',
                    title: __( 'Error fetching orders:', 'dokan' ) + error,
                } );
            }
        } finally {
            setIsLoading( false );
        }
    };

    // Fetch orders when view changes.
    useEffect( () => {
        if ( ! vendorId ) {
            return;
        }

        fetchOrders();
    }, [ vendorId, view ] );

    return (
        <>
            <DataViews
                data={ ordersData }
                namespace="dokan-vendor-subscription-orders-data-view"
                defaultLayouts={ { ...defaultLayouts } }
                fields={ fields }
                getItemId={ ( item ) => item.id }
                onChangeView={ setView }
                search={ false }
                paginationInfo={ {
                    // Pagination data for the table.
                    totalItems: totalOrders,
                    totalPages: Math.ceil( totalOrders / view.perPage ),
                } }
                view={ view }
                actions={ actions }
                isLoading={ isLoading }
                topPanel={ false }
            />

            <DokanModal
                isOpen={ isConfirmationModalOpen }
                namespace="dokan-vendor-subscription-order-cancel"
                dialogTitle={ __( 'Cancel Order', 'dokan' ) }
                confirmationTitle={ __(
                    'Are you sure you want to proceed?',
                    'dokan'
                ) }
                confirmationDescription={ __(
                    'Cancelling this order will prevent further completion of this subscription purchase.',
                    'dokan'
                ) }
                confirmButtonText={ __( 'Yes, Cancel', 'dokan' ) }
                cancelButtonText={ __( 'Close', 'dokan' ) }
                onConfirm={ () => cancelOrder() }
                onClose={ () => setIsConfirmationModalOpen( false ) }
            />
        </>
    );
};

export default SubscriptionOrders;

import { addQueryArgs } from '@wordpress/url';
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Eye, Check, XCircle, Store, Crown, Pen } from 'lucide-react';
import { useToast, DokanToaster } from '@getdokan/dokan-ui';

// Import Dokan components
import {
    AdminDataViews,
    DokanModal,
    AsyncSelect,
    VendorAsyncSelect,
    DokanTooltip as Tooltip,
} from '@dokan/components';
import { truncate } from '@dokan/utilities';

// Define subscription statuses (only Active and Cancelled)
const SUBSCRIPTION_STATUSES = [
    { value: 'active', label: __( 'Active', 'dokan' ) },
    { value: 'cancelled', label: __( 'Cancelled', 'dokan' ) },
];

// Define default layouts for DataViews
const defaultLayouts = {
    table: {
        layout: {
            primaryField: 'vendor',
            combinedFields: [
                {
                    id: 'vendor',
                    label: __( 'Vendor', 'dokan' ),
                },
                {
                    id: 'package',
                    label: __( 'Package', 'dokan' ),
                },
            ],
        },
    },
    grid: {},
    list: {},
    density: 'comfortable',
};

type Subscription = {
    id: number;
    store_name: string;
    order_link: string;
    order_id: string;
    subscription_id: string;
    subscription_title: string;
    has_pending_subscription: boolean;
    can_post_product: boolean;
    no_of_allowed_products: string;
    pack_validity_days: string;
    is_on_trial: boolean;
    trial_range: string;
    trial_period_type: string;
    subscription_trial_until: string | null;
    start_date: string;
    end_date: string;
    current_date: string;
    status: boolean;
    is_recurring: boolean;
    recurring_interval: number;
    recurring_period_type: string;
    has_active_cancelled_sub: boolean;
};

const SubscriptionsPage = () => {
    const [ data, setData ] = useState< Subscription[] >( [] );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ totalItems, setTotalItems ] = useState( 0 );
    const [ filterArgs, setFilterArgs ] = useState< {
        vendor_id?: string | number;
        pack_id?: string | number;
    } >( {} );
    const [ modalOpen, setModalOpen ] = useState( false );
    const [ modalItem, setModalItem ] = useState< Subscription | null >( null );
    const [ modalCancelPeriod, setModalCancelPeriod ] = useState(
        'end_of_current_period'
    );

    const toast = useToast();

    const [ selection, setSelection ] = useState( [] );

    // Filter states
    const [ packageFilter, setPackageFilter ] = useState( null );
    const [ vendorFilter, setVendorFilter ] = useState( null );

    // Define fields for the table columns
    const fields = [
        {
            id: 'vendor',
            label: __( 'Vendor', 'dokan' ),
            enableGlobalSearch: true,
            enableSorting: true,
            render: ( { item }: { item: Subscription } ) => (
                <Tooltip content={ item.store_name }>
                    <div className="flex items-center space-x-2 font-bold text-[#7047EB]">
                        { truncate
                            ? truncate( item.store_name, 22 )
                            : item.store_name || __( 'N/A', 'dokan' ) }
                    </div>
                </Tooltip>
            ),
        },
        {
            id: 'package',
            label: __( 'Subscription Pack', 'dokan' ),
            enableGlobalSearch: true,
            enableSorting: true,
            render: ( { item }: { item: Subscription } ) => (
                <Tooltip content={ item.subscription_title }>
                    <div className="font-medium text-gray-600">
                        { truncate
                            ? truncate( item.subscription_title, 22 )
                            : item.subscription_title || __( 'N/A', 'dokan' ) }
                    </div>
                </Tooltip>
            ),
        },
        {
            id: 'type',
            label: __( 'Type', 'dokan' ),
            render: ( { item }: { item: Subscription } ) => {
                const typeText = item.is_recurring
                    ? __( 'Recurring', 'dokan' )
                    : __( 'One-time', 'dokan' );

                return (
                    <div className="flex flex-col gap-1.5">
                        <span className="inline-flex items-center align-middle text-xs font-medium gap-1.5">
                            <svg
                                width="6"
                                height="7"
                                viewBox="0 0 6 7"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <circle
                                    cx="3"
                                    cy="3.5"
                                    r="3"
                                    fill={
                                        item.is_recurring
                                            ? '#DE6F7A'
                                            : '#6c42e8'
                                    }
                                />
                            </svg>
                            { typeText }
                        </span>
                        { item.is_on_trial && (
                            <span className="inline-flex items-center align-middle text-xs font-medium gap-1.5">
                                <svg
                                    width="6"
                                    height="7"
                                    viewBox="0 0 6 7"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <circle
                                        cx="3"
                                        cy="3.5"
                                        r="3"
                                        fill="#997AF3"
                                    />
                                </svg>
                                { __( 'On Trial', 'dokan' ) }
                            </span>
                        ) }
                    </div>
                );
            },
        },
        {
            id: 'start_date',
            label: __( 'Start Date', 'dokan' ),
            enableSorting: true,
            render: ( { item }: { item: Subscription } ) => (
                <div className="text-gray-900">{ item.start_date || '-' }</div>
            ),
        },
        {
            id: 'end_date',
            label: __( 'End Date', 'dokan' ),
            enableSorting: true,
            render: ( { item }: { item: Subscription } ) => (
                <div className="text-gray-900">{ item.subscription_end_date || '-' }</div>
            ),
        },
        {
            id: 'order',
            label: __( 'Order', 'dokan' ),
            enableSorting: true,
            render: ( { item }: { item: Subscription } ) => (
                <div className="">
                    <a
                        className="!text-gray-900"
                        href={ item.order_link }
                        target="_blank"
                        rel="noreferrer"
                    >
                        { item.order_id }
                    </a>
                </div>
            ),
        },
        {
            id: 'status',
            label: __( 'Status', 'dokan' ),
            enableGlobalSearch: false,
            getValue: ( { item }: { item: Subscription } ) => {
                if ( ! item.status || item.has_active_cancelled_sub ) {
                    return 'cancelled';
                }
                return 'active';
            },
            render: ( { item }: { item: Subscription } ) => {
                let statusClass = '';
                let statusText = '';

                if ( ! item.status || item.has_active_cancelled_sub ) {
                    statusClass = 'bg-red-100 text-red-800';
                    statusText = __( 'Cancelled', 'dokan' );
                } else {
                    statusClass = 'bg-green-100 text-green-800';
                    statusText = __( 'Active', 'dokan' );
                }

                return (
                    <Tooltip
                        content={ sprintf(
                            // translators: %s: end date of the subscription.
                            __( 'Cancels %s', 'dokan' ),
                            item.end_date
                        ) }
                    >
                        <span
                            className={ `inline-flex items-center px-3.5 py-1.5 rounded-md text-xs font-medium ${ statusClass }` }
                        >
                            { statusText }
                        </span>
                    </Tooltip>
                );
            },
        },
    ];
    const [ view, setView ] = useState( {
        type: 'table',
        perPage: 20,
        page: 1,
        sort: {
            field: 'start_date',
            direction: 'desc',
        },
        layout: { ...defaultLayouts },
        search: '',
        filters: [],
        fields: fields.map( ( field ) => field.id ),
    } );
    // Define actions for table rows
    const actions = [
        {
            id: 'view_order',
            label: () => (
                <div className="dokan-layout">
                    <span className="inline-flex items-center gap-2.5">
                        <Eye size={ 16 } className="!fill-none" />
                        { __( 'View Order', 'dokan' ) }
                    </span>
                </div>
            ),
            icon: () => (
                <span className="px-3 py-2 inline-flex items-center rounded-md text-sm font-medium border border-[#E9E9E9]">
                    { __( 'View Order', 'dokan' ) }
                </span>
            ),
            isPrimary: false,
            isEligible: ( item ) => !! item.order_link,
            callback: ( items ) => {
                if ( items.length > 0 && items[ 0 ].order_link ) {
                    window.open( items[ 0 ].order_link, '_blank' );
                }
            },
        },
        {
            id: 'activate',
            label: () => (
                <div className="dokan-layout">
                    <span className="inline-flex items-center gap-2.5">
                        <Check size={ 16 } className="!fill-none" />
                        { __( 'Activate', 'dokan' ) }
                    </span>
                </div>
            ),
            icon: () => (
                <span className="px-2 py-1.5 inline-flex items-center rounded-md border border-[#E9E9E9]">
                    { __( 'Activate', 'dokan' ) }
                </span>
            ),
            isPrimary: false,
            supportsBulk: true,
            isEligible: ( item ) =>
                item.has_active_cancelled_sub && item.is_recurring,
            callback: async ( items: Subscription[] ) => {
                await handleBatchAction( 'activate', items );
            },
        },
        {
            id: 'cancel',
            label: () => (
                <div className="dokan-layout">
                    <span className="inline-flex items-center gap-2.5">
                        <XCircle size={ 16 } className="!fill-none" />
                        { __( 'Cancel', 'dokan' ) }
                    </span>
                </div>
            ),
            icon: () => (
                <span className="px-2 py-1.5 inline-flex items-center rounded-md border border-[#E9E9E9]">
                    { __( 'Cancel', 'dokan' ) }
                </span>
            ),
            isPrimary: false,
            supportsBulk: true,
            isEligible: ( item ) => item.status,
            callback: async ( items: Subscription[] ) => {
                if ( items.length === 1 ) {
                    setModalItem( items[ 0 ] );
                    setModalOpen( true );
                    return;
                }
                await handleBatchAction( 'cancel', items );
            },
        },
    ];

    // Handle data fetching from the server
    const fetchSubscriptions = useCallback( async () => {
        setIsLoading( true );
        try {
            const queryArgs = {
                per_page: view?.perPage ?? 20,
                page: view?.page ?? 1,
                ...filterArgs,
            };

            // Handle sorting
            if ( view?.sort?.field ) {
                queryArgs.orderby = view.sort.field;
            }
            if ( view?.sort?.direction ) {
                queryArgs.order = view.sort.direction;
            }

            // Fetch data from the REST API
            const response: Response = await apiFetch( {
                path: addQueryArgs( '/dokan/v1/subscription', queryArgs ),
                headers: {
                    'Content-Type': 'application/json',
                },
                parse: false, // Get raw response to access headers
            } );

            const responseData: Subscription[] = await response.json();
            let subscriptions = responseData || [];
            if ( responseData.hasOwnProperty( 'code' ) ) {
                subscriptions = [];
            }
            const total = response.headers.get( 'X-WP-Total' ) || '0';

            setTotalItems( parseInt( total ) );
            setData( subscriptions );
        } catch ( error ) {
            console.error( 'Error fetching subscriptions:', error );
            setData( [] );
            setTotalItems( 0 );
        } finally {
            setIsLoading( false );
        }
    }, [ view, filterArgs ] );

    // Handle bulk actions
    const handleBatchAction = async (
        action: string,
        items: Subscription[],
        immediately: boolean = false
    ) => {
        if ( items.length === 1 ) {
            try {
                await apiFetch( {
                    path: '/dokan/v1/subscription/' + items[ 0 ].id,
                    method: 'PATCH',
                    data: {
                        action,
                        immediately,
                    },
                } );

                // Refresh data after action
                fetchSubscriptions();
            } catch ( error ) {
                console.error( `Error performing ${ action } action:`, error );
                toast( {
                    type: 'error',
                    title: __( 'Something went wrong.', 'dokan' ),
                } );
            }

            setModalOpen( false );
            setModalItem( null );
            setModalCancelPeriod( 'end_of_current_period' );
            toast( {
                type: 'success',
                title: __( 'Subscription Updated Successfully.', 'dokan' ),
            } );
            return;
        }

        try {
            await apiFetch( {
                path: '/dokan/v1/subscription/batch',
                method: 'POST',
                data: {
                    action,
                    user_ids: items.map( ( item ) => item.id ),
                },
            } );

            toast( {
                type: 'success',
                title: __(
                    'Subscriptions Updated Successfully.',
                    'dokan-lite'
                ),
            } );
            // Refresh data after action
            fetchSubscriptions();
        } catch ( error ) {
            console.error( `Error performing ${ action } action:`, error );
            toast( {
                type: 'error',
                title: __( 'Something went wrong.', 'dokan' ),
            } );
        }
    };

    // Handle filtering
    const handleFilter = useCallback( () => {
        setView( ( prevView ) => ( {
            ...prevView,
            page: 1, // Reset to first page when filtering
        } ) );
    }, [] );

    // Clear filters
    const clearFilter = useCallback( () => {
        setFilterArgs( {} );
        setPackageFilter( null );
        setVendorFilter( null );
        setView( ( prevView ) => ( {
            ...prevView,
            page: 1,
        } ) );
    }, [] );

    // Load packages for AsyncSelect
    const loadPackages = async () => {
        try {
            const resp = await apiFetch( {
                path: '/dokan/v1/subscription/packages',
                parse: true,
            } );

            return Array.isArray( resp )
                ? resp.map( ( pkg ) => ( {
                      value: pkg.id,
                      label: pkg.title,
                  } ) )
                : [];
        } catch ( error ) {
            console.error( 'Error loading packages:', error );
            return [];
        }
    };

    useEffect( () => {
        fetchSubscriptions();
    }, [ view ] );

    const plans = [
        {
            title: __( 'Immediately', 'dokan' ),
            description: __( 'It will be canceled on' ),
            id: 'immediately',
        },
        {
            title: __( 'End of the current period', 'dokan' ),
            description: __( 'It will be canceled on' ),
            id: 'end_of_current_period',
        },
    ];

    const filterConfig = {
        fields: [
            {
                id: 'vendor',
                label: __( 'Vendor', 'dokan' ),
                field: (
                    <VendorAsyncSelect
                        key="vendor-select"
                        icon={ <Store size={ 16 } /> }
                        value={ vendorFilter }
                        onChange={ (
                            selectedVendorObj: null | {
                                value: string;
                                label: string;
                            }
                        ) => {
                            setFilterArgs( ( prev ) => {
                                const next = { ...prev };
                                delete next.vendor_id;
                                if ( selectedVendorObj ) {
                                    next.vendor_id = selectedVendorObj.value;
                                }
                                return next;
                            } );
                            setVendorFilter( selectedVendorObj );
                            handleFilter();
                        } }
                        placeholder={ __( 'Select Vendor', 'dokan' ) }
                        isClearable
                        prefetch
                        defaultOptions
                        cacheOptions
                    />
                ),
            },
            {
                id: 'package',
                label: __( 'Subscription Pack', 'dokan' ),
                field: (
                    <AsyncSelect
                        icon={ <Crown size={ 16 } /> }
                        key="package-select"
                        loadOptions={ loadPackages }
                        cacheOptions
                        defaultOptions
                        isClearable
                        value={ packageFilter }
                        onChange={ ( selectedPackage ) => {
                            setFilterArgs( ( prev ) => {
                                const next = { ...prev };
                                delete next.pack_id;

                                if ( selectedPackage ) {
                                    next.pack_id = selectedPackage.value;
                                }
                                return next;
                            } );
                            setPackageFilter( selectedPackage );
                            handleFilter();
                        } }
                        placeholder={ __( 'Select Package', 'dokan' ) }
                    />
                ),
            },
        ],
        onReset: () => {
            clearFilter();
        },
        onFilterRemove: ( id: string ) => {
            if ( id === 'vendor' ) {
                setFilterArgs( ( prev ) => {
                    const next = { ...prev };
                    delete next.vendor_id;
                    return next;
                } );
                setVendorFilter( null );
            }
            if ( id === 'package' ) {
                setFilterArgs( ( prev ) => {
                    const next = { ...prev };
                    delete next.pack_id;
                    return next;
                } );
                setPackageFilter( null );
            }
            handleFilter();
        },
    };

    return (
        <div className="subscription-admin-page">
            <div className="mb-6 flex items-center justify-between">
                <h2 className="text-2xl font-bold text-gray-900 leading-8">
                    { __( 'Subscription', 'dokan' ) }
                </h2>
            </div>

            <AdminDataViews
                data={ data }
                namespace="subscription-data-view"
                defaultLayouts={ defaultLayouts }
                fields={ fields }
                getItemId={ ( item ) => item.id }
                onChangeView={ setView }
                paginationInfo={ {
                    totalItems,
                    totalPages: Math.ceil( totalItems / view.perPage ),
                } }
                view={ view }
                selection={ selection }
                onChangeSelection={ setSelection }
                actions={ actions }
                isLoading={ isLoading }
                filter={ filterConfig }
                tabs={ {
                    tabs: [
                        {
                            name: __( 'All', 'dokan' ),
                            icon: (
                                <div className="flex items-center gap-1.5 px-2">
                                    { __( 'All', 'dokan' ) }
                                    <span className="text-xs font-light text-[#A5A5AA]">
                                        ({ totalItems })
                                    </span>
                                </div>
                            ),
                            title: __( 'All', 'dokan' ),
                        },
                    ],
                    activeTab: 'all',
                    onTabChange: () => {},
                } }
            />

            <DokanModal
                className={ `max-w-full w-[513px]` }
                isOpen={ modalOpen }
                namespace={ `view-abuse-reports-${ modalItem?.id ?? 0 }` }
                onClose={ () => setModalOpen( false ) }
                onConfirm={ async () => {
                    await handleBatchAction(
                        'activate' === modalCancelPeriod
                            ? 'activate'
                            : 'cancel',
                        [ modalItem ],
                        modalCancelPeriod === 'immediately'
                    );
                } }
                dialogTitle={ __(
                    'Are you sure to cancel Subscription?',
                    'dokan'
                ) }
                dialogIcon={ <XCircle size={ 24 } /> }
                dialogContent={
                    <div className="flex w-full">
                        { modalItem && (
                            <div className="space-y-4 w-full">
                                { plans
                                    .filter( ( plan ) => {
                                        if (
                                            modalItem.has_active_cancelled_sub
                                        ) {
                                            return (
                                                plan.id !==
                                                'end_of_current_period'
                                            );
                                        }
                                        return plan.id !== 'activate';
                                    } )
                                    .map( ( plan ) => (
                                        <label
                                            key={ plan.id }
                                            aria-label={ plan.title }
                                            aria-description={
                                                plan.description
                                            }
                                            className="group flex justify-between content-center gap-3 border border-gray-200 p-4 rounded-md focus:outline-none has-[:checked]:relative has-[:checked]:border-indigo-700 dark:border-gray-700 dark:has-[:checked]:border-indigo-800"
                                        >
                                            <span className="flex items-center">
                                                <span className="flex flex-col text-sm">
                                                    <span className="font-medium text-gray-900 dark:text-white">
                                                        { plan.title }
                                                    </span>
                                                    <span className="text-gray-500 dark:text-gray-400">
                                                        <span className="block sm:inline">
                                                            { sprintf(
                                                                // translators: %1$s: Date of cancellation.
                                                                __(
                                                                    'It will cancel on %1$s',
                                                                    'dokan'
                                                                ),
                                                                plan.id ===
                                                                    'immediately'
                                                                    ? modalItem.current_date
                                                                    : modalItem.end_date
                                                            ) }
                                                        </span>
                                                    </span>
                                                </span>
                                            </span>
                                            <input
                                                defaultValue={ plan.id }
                                                defaultChecked={
                                                    plan.id ===
                                                    modalCancelPeriod
                                                }
                                                name="cancel_subscription"
                                                type="radio"
                                                onChange={ ( e ) =>
                                                    setModalCancelPeriod(
                                                        e.target.value
                                                    )
                                                }
                                                className="relative mt-0.5 size-4 shrink-0 appearance-none rounded-full border border-gray-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white checked:border-indigo-600 checked:bg-indigo-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:before:bg-gray-400 dark:border-white/10 dark:bg-white/5 dark:checked:border-indigo-500 dark:checked:bg-indigo-500 dark:focus-visible:outline-indigo-500 dark:disabled:border-white/5 dark:disabled:bg-white/10 dark:disabled:before:bg-white/20 forced-colors:appearance-auto forced-colors:before:hidden [&:not(:checked)]:before:hidden [&:is(:checked)]:before:!hidden"
                                            />
                                        </label>
                                    ) ) }
                            </div>
                        ) }
                    </div>
                }
                hideCancelButton={ false }
                confirmButtonText={ __( 'Cancel Subscription', 'dokan' ) }
                cancelButtonText={ __( 'Don’t Cancel', 'dokan' ) }
            />
            <DokanToaster />
        </div>
    );
};

export default SubscriptionsPage;

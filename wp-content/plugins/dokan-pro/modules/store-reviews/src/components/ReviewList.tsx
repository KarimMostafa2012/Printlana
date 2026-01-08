// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { truncate } from '@dokan/utilities';
import apiFetch from '@wordpress/api-fetch';
import { RawHTML, useCallback, useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

// Import Dokan components
import {
    AdminDataViews as DataViews,
    DateTimeHtml,
    DokanModal,
    VendorAsyncSelect,
    DokanTooltip as Tooltip,
    // @ts-ignore
    // eslint-disable-next-line import/no-unresolved
} from '@dokan/components';

import { Check, Edit, Home, RotateCw, Star, Trash } from 'lucide-react';
import ItemEdit from './ItemEdit';
import { DokanToaster, useToast } from '@getdokan/dokan-ui';

type Customer = {
    id: number;
    first_name: string | null;
    last_name: string | null;
    email: string;
    display_name: string;
};

type Vendor = {
    id: number;
    first_name: string | null;
    last_name: string | null;
    shop_name: string;
    shop_url: string;
    avatar: string | false;
    banner: string;
};

export type StoreReview = {
    id: number;
    title: string;
    content: string;
    status: string;
    created_at: string; // RFC3339
    customer: Customer;
    vendor: Vendor;
    rating: number;
};

// Define review statuses for tab filtering
const REVIEW_STATUSES = [
    { value: 'all', label: __( 'All', 'dokan' ) },
    { value: 'trash', label: __( 'Trash', 'dokan' ) },
];

const PER_PAGE = 10;

const ReviewList = () => {
    const toast = useToast();
    const [ data, setData ] = useState< StoreReview[] >( [] );
    const [ vendorsData, setVendorsData ] = useState< any >( null );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ totalItems, setTotalItems ] = useState( 0 );
    const [ statusCounts, setStatusCounts ] = useState( {
        all: 0,
        trash: 0,
    } );

    const renderContent = ( content: string, length: number = 72 ) => {
        if ( content.length > length ) {
            return (
                <Tooltip content={ content }>
                    <div className="line-clamp-2 text-wrap text-sm text-gray-600">
                        <RawHTML>{ truncate( content, length ) }</RawHTML>
                    </div>
                </Tooltip>
            );
        }
        return (
            <div className="text-sm text-gray-600 text-wrap">
                <RawHTML>{ content }</RawHTML>
            </div>
        );
    };

    // Define fields for the table columns
    const fields = [
        {
            id: 'title',
            label: __( 'Title', 'dokan' ),
            enableGlobalSearch: true,
            enableSorting: true,
            render: ( { item }: { item: StoreReview } ) => {
                return renderContent( item.title || '-' );
            },
        },
        {
            id: 'content',
            label: __( 'Comment', 'dokan' ),
            enableGlobalSearch: true,
            render: ( { item }: { item: StoreReview } ) => {
                return renderContent( item.content || '-' );
            },
        },
        {
            id: 'customer',
            label: __( 'Reviewer', 'dokan' ),
            enableGlobalSearch: true,
            render: ( { item }: { item: StoreReview } ) => {
                return (
                    <div className="text-gray-600">
                        { item.customer?.display_name || __( 'N/A', 'dokan' ) }
                    </div>
                );
            },
        },
        {
            id: 'vendor',
            label: __( 'Store', 'dokan' ),
            enableGlobalSearch: true,
            enableSorting: true,
            render: ( { item }: { item: StoreReview } ) => {
                return renderContent( item.vendor?.shop_name, 32 );
            },
        },
        {
            id: 'rating',
            label: __( 'Rating', 'dokan' ),
            enableSorting: true,
            render: ( { item }: { item: StoreReview } ) => {
                return (
                    <div className="flex items-center">
                        <span className="text-yellow-600 mr-1">
                            <Star fill="#eab308" size={ 20 } />
                        </span>
                        <span className="text-sm text-gray-600">
                            { item.rating }
                        </span>
                    </div>
                );
            },
        },
        {
            id: 'date',
            label: __( 'Submitted On', 'dokan' ),
            enableSorting: true,
            render: ( { item }: { item: StoreReview } ) => {
                return (
                    <div className="text-gray-900">
                        <DateTimeHtml.Date date={ item.created_at } />
                    </div>
                );
            },
        },
    ];

    const getActionLabel = ( iconName: string, label: string ) => {
        if ( ! ( iconName && label ) ) {
            return null;
        }

        const icons = {
            Edit,
            Trash,
            Check,
            RotateCw,
        };

        const Icon = icons[ iconName as keyof typeof icons ];
        return (
            <div className="dokan-layout">
                <span className="inline-flex items-center gap-2.5">
                    <Icon size={ 16 } className="!fill-none" />
                    { label }
                </span>
            </div>
        );
    };

    // Define actions for table rows
    const actions = [
        {
            id: 'edit',
            label: () => getActionLabel( 'Edit', __( 'Edit', 'dokan' ) ),
            icon: () => {
                return (
                    <span
                        className={
                            'px-3 py-2 inline-flex items-center rounded-md text-sm font-medium border border-[#E9E9E9]'
                        }
                    >
                        { __( 'Edit', 'dokan' ) }
                    </span>
                );
            },
            isPrimary: false,
            callback: ( items: StoreReview[] ) => {
                openModal( 'edit', items );
            },
        },
        {
            id: 'trash',
            label: () =>
                getActionLabel( 'Trash', __( 'Move to Trash', 'dokan' ) ),
            icon: () => {
                return (
                    <span
                        className={
                            'px-2 py-1.5 inline-flex items-center rounded-md border border-[#E9E9E9]'
                        }
                    >
                        { __( 'Move to Trash', 'dokan' ) }
                    </span>
                );
            },
            supportsBulk: true,
            isEligible: ( item: StoreReview ) => item?.status !== 'trash',
            callback: ( items: StoreReview[] ) => {
                openModal( 'trash', items );
            },
        },
        {
            id: 'restore',
            label: () => getActionLabel( 'RotateCw', __( 'Restore', 'dokan' ) ),
            icon: () => {
                return (
                    <span
                        className={
                            'px-2 py-1.5 inline-flex items-center rounded-md border border-[#E9E9E9]'
                        }
                    >
                        { __( 'Restore', 'dokan' ) }
                    </span>
                );
            },
            supportsBulk: true,
            isEligible: ( item: StoreReview ) => item?.status === 'trash',
            callback: ( items: StoreReview[] ) => {
                openModal( 'restore', items );
            },
        },
        {
            id: 'delete',
            label: () =>
                getActionLabel( 'Trash', __( 'Delete Permanently', 'dokan' ) ),
            icon: () => {
                return (
                    <span
                        className={
                            'px-2 py-1.5 inline-flex items-center rounded-md border border-[#E9E9E9] text-red-600'
                        }
                    >
                        { __( 'Delete Permanently', 'dokan' ) }
                    </span>
                );
            },
            supportsBulk: true,
            isEligible: ( item: StoreReview ) => item?.status === 'trash',
            callback: ( items: StoreReview[] ) => {
                openModal( 'delete', items );
            },
        },
    ];

    // Set for handling bulk selection
    const [ selection, setSelection ] = useState( [] );

    // Modal state management
    const [ modalState, setModalState ] = useState< {
        isOpen: boolean;
        type: string;
        items: StoreReview[];
    } >( {
        isOpen: false,
        type: '',
        items: [],
    } );

    // Modal helper functions
    const openModal = ( type: string, items: StoreReview[] ) => {
        setModalState( {
            isOpen: true,
            type,
            items,
        } );
    };

    const closeModal = () => {
        setModalState( {
            isOpen: false,
            type: '',
            items: [],
        } );
    };

    // Set data view default layout
    const defaultLayouts = {
        table: { density: 'comfortable' },
        grid: {},
        list: {},
    };

    // Set view state for handling the table view
    const [ view, setView ] = useState( {
        perPage: PER_PAGE,
        page: 1,
        search: '',
        type: 'table',
        titleField: 'title',
        status: 'all',
        layout: defaultLayouts,
        fields: fields.map( ( field ) =>
            field.id !== 'title' ? field.id : ''
        ),
    } );

    // Handle tab selection for status filtering
    const handleTabSelect = ( tabName: string ) => {
        setView( ( prevView ) => ( {
            ...prevView,
            status: tabName,
            page: 1, // Reset to first page when changing status
        } ) );
    };

    // Create tabs with status counts
    const tabs = REVIEW_STATUSES.map( ( status ) => ( {
        name: status.value,
        title: status.label,
        icon: (
            <div className="flex items-center gap-1.5 px-2">
                { status.label }
                <span className="text-xs font-light text-[#A5A5AA]">
                    ({ statusCounts[ status.value ] })
                </span>
            </div>
        ),
    } ) );

    const filterFields = [
        {
            id: 'vendor',
            label: __( 'Vendor', 'dokan' ),
            field: (
                <VendorAsyncSelect
                    icon={ <Home size={ 16 } /> }
                    key="vendor-select"
                    value={ vendorsData }
                    onChange={ setVendorsData }
                    placeholder={ __( 'Select Vendor', 'dokan' ) }
                    prefetch
                    defaultOptions
                    cacheOptions
                />
            ),
        },
    ];

    // Handle data fetching from the server
    const fetchReviews = useCallback( async () => {
        setIsLoading( true );
        try {
            const queryArgs = {
                per_page: view?.perPage ?? PER_PAGE,
                page: view?.page ?? 1,
                status: view.status || 'all',
                vendor_id: vendorsData?.value || undefined,
            };

            // Fetch data from the REST API
            const response = await apiFetch< any >( {
                path: addQueryArgs( 'dokan/v1/store-reviews', queryArgs ),
                // @ts-ignore
                parse: false, // Get raw response to access headers
            } );

            const responseData = await response.json();
            setTotalItems(
                parseInt( response.headers.get( 'X-WP-Total' ) || 0 )
            );

            setData( Array.isArray( responseData ) ? responseData : [] );

            // Extract status counts from response headers
            const counts = {
                all: parseInt( response.headers.get( 'X-Status-All' ) || 0 ),
                trash: parseInt(
                    response.headers.get( 'X-Status-Trash' ) || 0
                ),
            };
            setStatusCounts( counts );
        } catch ( error ) {
            setData( [] );
        } finally {
            setIsLoading( false );
        }
    }, [ vendorsData, view?.page, view?.perPage, view.status ] );

    // Handle bulk actions
    const handleBulkAction = async ( action: string, ids: number[] ) => {
        try {
            const deletedData = { [ action ]: ids };

            await apiFetch( {
                path: `/dokan/v1/store-reviews/batch`,
                method: 'POST',
                data: deletedData,
            } );

            void fetchReviews(); // Refresh data
            setSelection( [] ); // Clear selection
            const messages = {
                trash: __( 'Review(s) moved to trash.', 'dokan' ),
                restore: __( 'Review(s) restored successfully.', 'dokan' ),
                delete: __( 'Review(s) deleted permanently.', 'dokan' ),
            };
            toast( {
                type: 'success',
                title: messages[ action as keyof typeof messages ],
            } );
        } catch ( error ) {
            toast( {
                type: 'error',
                title: __( 'Failed to perform the action.', 'dokan' ),
            } );
        }
    };

    // Clear filters
    const clearFilter = () => {
        setVendorsData( null );
        void fetchReviews();
    };

    const saveChanges = async () => {
        if ( ! modalState.items[ 0 ] ) {
            toast( {
                type: 'error',
                title: __( 'No review selected for editing.', 'dokan' ),
            } );
            return;
        }

        try {
            const reviewToUpdate = modalState.items[ 0 ];
            await apiFetch( {
                path: `/dokan/v1/store-reviews/${ reviewToUpdate.id }`,
                method: 'PUT',
                data: {
                    title: reviewToUpdate.title,
                    content: reviewToUpdate.content,
                    rating: reviewToUpdate.rating,
                },
            } );

            // Refresh the reviews list
            void fetchReviews();
            closeModal();
            toast( {
                type: 'success',
                title: __( 'Review updated successfully.', 'dokan' ),
            } );
        } catch ( error ) {
            toast( {
                type: 'error',
                title: __( 'Failed to update the review.', 'dokan' ),
            } );
        }
    };

    const reviewEdit = ( e: any ) => {
        const { name, value } = e.target;
        setModalState( ( prev ) => {
            if ( ! prev.items[ 0 ] ) {
                return prev;
            }
            return {
                ...prev,
                items: [
                    {
                        ...prev.items[ 0 ],
                        [ name ]:
                            name === 'rating'
                                ? parseInt( String( value ), 10 )
                                : value,
                    },
                ],
            };
        } );
    };

    // Fetch reviews when view changes
    useEffect( () => {
        void fetchReviews();
    }, [ fetchReviews ] );

    return (
        <div className="store-reviews-admin-page">
            <div className="flex items-center justify-between mb-4">
                <h2 className="text-xl font-bold text-gray-900 leading-8">
                    { __( 'Store Reviews', 'dokan' ) }
                </h2>
            </div>

            { /* Data Table */ }
            <DataViews
                data={ data }
                namespace="store-reviews-data-view"
                defaultLayouts={ defaultLayouts }
                fields={ fields }
                getItemId={ ( item ) => item.id }
                // @ts-ignore
                onChangeView={ setView }
                paginationInfo={ {
                    totalItems,
                    totalPages: Math.ceil( totalItems / view.perPage ),
                } }
                // @ts-ignore
                view={ view }
                selection={ selection }
                // @ts-ignore
                onChangeSelection={ setSelection }
                // @ts-ignore
                actions={ actions }
                isLoading={ isLoading }
                tabs={ {
                    tabs,
                    onSelect: handleTabSelect,
                    initialTabName: 'all',
                } }
                filter={ {
                    fields: filterFields,
                    onFilterRemove: clearFilter,
                    onReset: () => clearFilter(),
                } }
            />

            { modalState.isOpen && modalState.type === 'trash' && (
                <DokanModal
                    isOpen={ modalState.isOpen }
                    namespace={ `trash-review-${ modalState.items.length }` }
                    onClose={ closeModal }
                    onConfirm={ async () => {
                        await handleBulkAction(
                            'trash',
                            modalState.items.map( ( item ) => item.id )
                        );
                        closeModal();
                    } }
                    dialogTitle={ __( 'Move to Trash', 'dokan' ) }
                    confirmButtonText={ __( 'Move to Trash', 'dokan' ) }
                    confirmationTitle={ __( 'Confirm Move to Trash', 'dokan' ) }
                    confirmationDescription={ __(
                        'Are you sure you want to move this review to trash?',
                        'dokan'
                    ) }
                    confirmButtonVariant="primary"
                    dialogIcon={
                        <div className="flex items-center justify-center flex-shrink-0 w-14 h-14 bg-orange-50 border border-orange-50 rounded-full">
                            <div className="w-6 h-6 text-orange-600">
                                <Trash />
                            </div>
                        </div>
                    }
                />
            ) }

            { modalState.isOpen && modalState.type === 'restore' && (
                <DokanModal
                    isOpen={ modalState.isOpen }
                    namespace={ `restore-review-${ modalState.items.length }` }
                    onClose={ closeModal }
                    onConfirm={ async () => {
                        await handleBulkAction(
                            'restore',
                            modalState.items.map( ( item ) => item.id )
                        );
                        closeModal();
                    } }
                    dialogTitle={ __( 'Restore Review', 'dokan' ) }
                    confirmButtonText={ __( 'Restore', 'dokan' ) }
                    confirmationTitle={ __( 'Confirm Restore', 'dokan' ) }
                    confirmationDescription={ sprintf(
                        // translators: 1: number of reviews.
                        // eslint-disable-next-line @wordpress/i18n-translator-comments
                        __(
                            'Are you sure you want to restore these %d reviews?',
                            'dokan'
                        ),
                        modalState.items.length
                    ) }
                    confirmButtonVariant="primary"
                    dialogIcon={
                        <div className="flex items-center justify-center flex-shrink-0 w-14 h-14 bg-blue-50 border border-blue-50 rounded-full">
                            <svg
                                className="w-6 h-6 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                />
                            </svg>
                        </div>
                    }
                />
            ) }

            { modalState.isOpen &&
                modalState.type === 'edit' &&
                modalState.items.length > 0 && (
                    <DokanModal
                        className={ `w-[800px]` }
                        isOpen={ modalState.isOpen }
                        namespace={ `edit-review-${ modalState.items[ 0 ]?.id }` }
                        onClose={ closeModal }
                        onConfirm={ saveChanges }
                        dialogTitle={ __( 'Edit Review', 'dokan' ) }
                        confirmButtonText={ __( 'Save Changes', 'dokan' ) }
                        cancelButtonText={ __( 'Cancel', 'dokan' ) }
                        dialogContent={
                            <ItemEdit
                                item={ modalState.items[ 0 ] }
                                onChange={ reviewEdit }
                            />
                        }
                    />
                ) }

            { modalState.isOpen && modalState.type === 'delete' && (
                <DokanModal
                    isOpen={ modalState.isOpen }
                    namespace={ `delete-review-${ modalState.items.length }` }
                    onClose={ closeModal }
                    onConfirm={ async () => {
                        await handleBulkAction(
                            'delete',
                            modalState.items.map( ( item ) => item.id )
                        );
                        closeModal();
                    } }
                    dialogTitle={ __( 'Delete Review', 'dokan' ) }
                    confirmButtonText={ __( 'Delete', 'dokan' ) }
                    confirmationTitle={ __( 'Confirm Deletion', 'dokan' ) }
                    confirmationDescription={
                        modalState.items.length === 1
                            ? __(
                                  'Are you sure you want to permanently delete this review? This action cannot be undone.',
                                  'dokan'
                              )
                            : sprintf(
                                  // translators: 1: number of reviews.
                                  // eslint-disable-next-line @wordpress/i18n-translator-comments
                                  __(
                                      'Are you sure you want to permanently delete these %d reviews? This action cannot be undone.',
                                      'dokan'
                                  ),
                                  modalState.items.length
                              )
                    }
                    confirmButtonVariant="primary"
                    dialogIcon={
                        <div className="flex items-center justify-center flex-shrink-0 w-14 h-14 bg-red-50 border border-red-50 rounded-full">
                            <Trash size={ 24 } className="text-red-600" />
                        </div>
                    }
                />
            ) }
            <DokanToaster />
        </div>
    );
};

export default ReviewList;

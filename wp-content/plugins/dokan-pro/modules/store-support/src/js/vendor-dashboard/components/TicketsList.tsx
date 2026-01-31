import { useEffect, useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { type Moment } from '@wordpress/date';

// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { capitalCase } from '@dokan/utilities';
import {
    DokanBadge,
    DataViews,
    DateTimeHtml,
    Filter,
    CustomerFilter,
    // @ts-ignore
    // eslint-disable-next-line import/no-unresolved
} from '@dokan/components';
import { DokanToaster, SimpleInput, useToast } from '@getdokan/dokan-ui';

import '../../../../../../src/definitions/window-types';
import {
    SupportTicket,
    SupportTicketStatus,
    TicketsListProps,
    FilterState,
    CustomerOption,
    SimpleInputEvent,
} from '../../types/store-support';
import StatusFilter from './Navigation/StatusFilter';
import DateRangeFilter from './Navigation/DateRangeFilter';
import { useTickets } from '../hooks/useTickets';

const DEFAULT_LAYOUTS = {
    table: { density: 'comfortable' },
    list: {},
};

const DEFAULT_FILTERS = {
    page: 1,
    per_page: 10,
    status: 'all' as SupportTicketStatus,
    customer_id: 0,
    start_date: '',
    end_date: '',
    search: '',
};

const DEFAULT_VIEW = {
    perPage: 10,
    page: 1,
    search: '',
    type: 'table',
    selection: null,
    layout: { ...DEFAULT_LAYOUTS },
};

// https://momentjs.com/docs/#/parsing/string-format/
const DATE_FORMAT = 'YYYY-MM-DD';

export type DateRange = {
    startDate: Moment;
    endDate: Moment;
};

export default function TicketsList( { navigate }: TicketsListProps ) {
    const toast = useToast();

    // State management
    const [ loadFilters, setLoadFilters ] = useState< boolean >( false );
    const [ selectedStatus, setSelectedStatus ] =
        useState< SupportTicketStatus >( 'all' );
    const [ filterArgs, setFilterArgs ] =
        useState< FilterState >( DEFAULT_FILTERS );
    const [ selectedCustomer, setSelectedCustomer ] =
        useState< CustomerOption | null >( null );
    const [ selectedDateRange, setSelectedDateRange ] =
        useState< DateRange | null >( null );
    const [ ticketSearch, setTicketSearch ] = useState< string >( '' );

    // Generate field definitions
    const fields = [
        {
            id: 'topic',
            label: __( 'Topic', 'dokan' ),
            render: ( { item }: { item: SupportTicket } ) => (
                <div className="topic-id-column">
                    <span
                        role="button"
                        onClick={ ( e ) => {
                            e.preventDefault();
                            navigate( `/support/${ item.id }/` );
                        } }
                        tabIndex={ 0 }
                        onKeyDown={ ( e ) => {
                            if ( e.key === 'Enter' || e.key === ' ' ) {
                                navigate( `/support/${ item.id }/` );
                            }
                        } }
                        className="font-bold hover:underline cursor-pointer text-dokan-link relative z-10"
                    >
                        #{ item.id }
                    </span>
                </div>
            ),
            enableSorting: false,
            maxWidth: 80,
        },
        {
            id: 'title',
            label: __( 'Title', 'dokan' ),
            render: ( { item }: { item: SupportTicket } ) => (
                <div className="title-column">
                    <span
                        role="button"
                        onClick={ ( e ) => {
                            e.preventDefault();
                            navigate( `/support/${ item.id }/` );
                        } }
                        tabIndex={ 0 }
                        onKeyDown={ ( e ) => {
                            if ( e.key === 'Enter' || e.key === ' ' ) {
                                navigate( `/support/${ item.id }/` );
                            }
                        } }
                        className="text-dokan-link hover:underline cursor-pointer relative z-10"
                        title={ item.title }
                    >
                        { item.title }
                    </span>

                    { /* Mobile responsive toggle button */ }
                    <button
                        type="button"
                        className="toggle-row md:hidden"
                        onClick={ () => navigate( `/support/${ item.id }/` ) }
                        aria-label={ __( 'View ticket details', 'dokan' ) }
                    />
                </div>
            ),
            enableSorting: false,
            isPrimary: true,
        },
        {
            id: 'customer',
            label: __( 'Customer', 'dokan' ),
            render: ( { item }: { item: SupportTicket } ) => (
                <div
                    className="customer-column"
                    data-title={ __( 'Customer', 'dokan' ) }
                >
                    <div className="flex items-center space-x-3">
                        <img
                            src={
                                item.customer?.avatar ||
                                `https://www.gravatar.com/avatar/?s=50&d=identicon`
                            }
                            alt={ item.customer?.name || '' }
                            className="w-12 h-12 rounded-full"
                        />
                        <strong>
                            { item.customer?.name || __( 'Unknown', 'dokan' ) }
                        </strong>
                    </div>
                </div>
            ),
            enableSorting: false,
            maxWidth: 200,
        },
        {
            id: 'status',
            label: __( 'Status', 'dokan' ),
            render: ( { item }: { item: SupportTicket } ) => (
                <div
                    className="status-column"
                    data-title={ __( 'Status', 'dokan' ) }
                >
                    <DokanBadge
                        variant={
                            item.status === 'open' ? 'success' : 'danger'
                        }
                        label={ capitalCase( item.status ) }
                    />
                </div>
            ),
            enableSorting: false,
            maxWidth: 100,
        },
        {
            id: 'created_at',
            label: __( 'Date', 'dokan' ),
            render: ( { item }: { item: SupportTicket } ) => (
                <div
                    className="date-column"
                    data-title={ __( 'Date', 'dokan' ) }
                >
                    <span className="text-sm text-gray-600">
                        <DateTimeHtml.Date date={ item.date_formatted } />
                    </span>
                </div>
            ),
            enableSorting: false,
            maxWidth: 120,
        },
    ];

    // Initialize view state with callback
    const [ view, setView ] = useState( {
        ...DEFAULT_VIEW,
        fields: fields.map( ( field ) => field.id ),
    } );

    // Data fetching hook
    const {
        tickets,
        isLoading,
        fetchTickets,
        updateStatus,
        totalItems,
        totalPages,
    } = useTickets( filterArgs );

    // Actions for DataViews dropdown
    const actions = [
        {
            id: 'support-ticket-status-close-toggle',
            label: () => __( 'Close', 'dokan' ),
            isPrimary: true,
            disabled: isLoading,
            isEligible: ( item: SupportTicket ) => item.status === 'open',
            callback: ( [ item ]: [ item: SupportTicket ] ) =>
                handleStatusToggle( item ),
        },
        {
            id: 'support-ticket-status-reopen-toggle',
            label: () => __( 'Re-open', 'dokan' ),
            isPrimary: true,
            disabled: isLoading,
            isEligible: ( item: SupportTicket ) => item.status === 'closed',
            callback: ( [ item ]: [ item: SupportTicket ] ) =>
                handleStatusToggle( item ),
        },
    ];

    // Event Handlers
    const handleLoadComplete = useCallback( () => {
        setLoadFilters( false );
    }, [] );

    const onFilter = useCallback( () => {
        // Build new filter args from temporary filter data
        const newFilterArgs = {
            ...filterArgs,
            customer_id: selectedCustomer?.value || 0,
            start_date:
                selectedDateRange?.startDate?.format( DATE_FORMAT ) || '',
            end_date: selectedDateRange?.endDate?.format( DATE_FORMAT ) || '',
            search: ticketSearch || '',
            page: 1,
        };

        // Update states
        setFilterArgs( newFilterArgs );
        setView( ( prev ) => ( { ...prev, page: 1 } ) );
        setLoadFilters( true );
    }, [ filterArgs, selectedCustomer, selectedDateRange, ticketSearch ] );

    const onStatusClick = useCallback( ( status: SupportTicketStatus ) => {
        // Update the local status state
        setSelectedStatus( status );

        // Also update filterArgs for API calls
        setFilterArgs( ( prev ) => ( {
            ...prev,
            status,
            page: 1,
            search: '',
            start_date: '',
            end_date: '',
            customer_id: 0,
        } ) );

        setView( ( prev ) => ( {
            ...prev,
            page: 1,
        } ) );
    }, [] );

    const onItemView = useCallback(
        ( item: SupportTicket ) => {
            navigate( `/support/${ item.id }/` );
        },
        [ navigate ]
    );

    const onViewChange = useCallback( ( newView: typeof view ) => {
        setView( newView );
        setFilterArgs( ( prevState ) => ( {
            ...prevState,
            page: newView.page,
            per_page: newView.perPage,
        } ) );
    }, [] );

    const handleStatusToggle = useCallback(
        async ( ticket: SupportTicket ) => {
            try {
                const newStatus = ticket.status === 'open' ? 'closed' : 'open';
                
                await updateStatus( ticket, newStatus );

                toast( {
                    type: 'success',
                    title: __( 'Ticket status updated successfully', 'dokan' ),
                } );
            } catch ( error ) {
                toast( {
                    type: 'error',
                    title: __( 'Failed to update ticket status', 'dokan' ),
                } );
            }
        },
        [ updateStatus, fetchTickets, filterArgs?.status, toast ]
    );

    // Effects
    useEffect( () => {
        setLoadFilters( true );
        void fetchTickets( filterArgs?.status );
    }, [ filterArgs ] );

    return (
        <div className="dokan-support-wrapper">
            <Filter
                fields={ [
                    <CustomerFilter
                        key="customer-filter"
                        id="dokan-filter-by-customer"
                        className="max-h-[44px]"
                        value={ selectedCustomer }
                        onChange={ ( selected: CustomerOption ) => {
                            setSelectedCustomer( selected );
                        } }
                        placeholder={ __( 'Search customer', 'dokan' ) }
                        label={ '' }
                    />,
                    <DateRangeFilter
                        key="date-range-filter"
                        startDate={ selectedDateRange?.startDate ?? null }
                        endDate={ selectedDateRange?.endDate ?? null }
                        onChange={ ( startDate: Moment, endDate: Moment ) => {
                            setSelectedDateRange( { startDate, endDate } );
                        } }
                        loadFilters={ loadFilters }
                        onLoadComplete={ handleLoadComplete }
                    />,
                    <div key="keyword-filter">
                        <SimpleInput
                            input={ {
                                autoComplete: 'off',
                                id: 'ticket-id-keyword',
                                name: 'ticket-id-keyword',
                                placeholder: __(
                                    'Ticket ID or Keyword',
                                    'dokan'
                                ),
                            } }
                            className="bg-white"
                            value={ ticketSearch || '' }
                            onChange={ ( event: SimpleInputEvent ) => {
                                setTicketSearch( event.target.value );
                            } }
                        />
                    </div>,
                ] }
                showFilter={ true }
                showReset={ true }
                onFilter={ onFilter }
                onReset={ () => {
                    setSelectedCustomer( null );
                    setSelectedDateRange( null );
                    setTicketSearch( '' );
                    setFilterArgs( DEFAULT_FILTERS );
                    setView( ( prev ) => ( { ...prev, page: 1 } ) );
                    setLoadFilters( true );
                } }
                namespace="support-tickets"
            />
            <StatusFilter
                key="status-filter"
                statusParam={ selectedStatus }
                loadFilters={ loadFilters }
                onChange={ onStatusClick }
                onLoadComplete={ handleLoadComplete }
            />

            <div className="dokan-support-topics-list mt-6">
                <DataViews
                    namespace="dokan-support-tickets-data-view"
                    data={ tickets ?? [] }
                    defaultLayouts={ DEFAULT_LAYOUTS }
                    fields={ fields }
                    search={ false }
                    view={ view }
                    actions={ actions }
                    isLoading={ isLoading }
                    paginationInfo={ {
                        totalItems,
                        totalPages,
                    } }
                    getItemId={ ( item: SupportTicket ) => item.id }
                    onChangeView={ onViewChange }
                    onClickItem={ onItemView }
                    isItemClickable={ () => true }
                />
            </div>
            <DokanToaster />
        </div>
    );
}

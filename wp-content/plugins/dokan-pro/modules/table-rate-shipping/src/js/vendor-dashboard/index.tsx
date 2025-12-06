import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import { NotFound, VisitStore } from '@dokan/components';
import TableRateShippingSettings from './table-settings';
import '../../scss/tailwind.scss';

domReady( () => {
    if ( ! dokanShippingHelper?.enable_woo_shipping ) {
        return <NotFound />;
    }

    // @ts-ignore
    wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-pro-table-rate-shipping-settings',
        ( routes ) => {
            routes.push( {
                id: 'table-rate-shipping-settings',
                title: (
                    <VisitStore>
                        { __( 'Shipping Settings', 'dokan' ) }
                    </VisitStore>
                ),
                element: <TableRateShippingSettings />,
                path: '/settings/shipping/:zoneID/table-rate/:instanceID',
                backUrl: '/settings/shipping/:zoneID',
                exact: true,
                order: 10,
                parent: 'settings',
                capabilities: [ 'dokan_view_store_shipping_menu' ],
            } );

            return routes;
        }
    );

    // @ts-ignore
    wp.hooks.addAction(
        'dokan_shipping_edit_shipping_method',
        'dokan-shipping-edit-shipping-method-callback',
        ( method, zone, navigate ) => {
            if ( method.id === 'dokan_table_rate_shipping' ) {
                navigate(
                    `/settings/shipping/${ zone?.data?.id }/table-rate/${ method?.instance_id }`
                );
            }
        }
    );
} );

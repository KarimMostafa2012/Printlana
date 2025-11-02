<?php 

class pi_corw_auto_cancel_order{

    static $instance = null;

    public $new_state = null;

    static function get_instance(){
        if(self::$instance == null){
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct(){
        add_action('pisol_corw_cancel_request_received', array($this, 'cancellation_request_received'), 10, 2);

        add_filter('pisol_corw_cancel_request_new_status', [$this, 'cancel_request_new_status'], 10);
    }

    function cancellation_request_received($order_id, $reason){

        if(!pisol_corw_wallet_refund::waller_refund_enabled()) return;

        $order = wc_get_order($order_id);

        if(!$order) return;
        
        if(!pisol_corw_wallet_refund::user_opted_for_wallet_refund($order)) return;


        $this->create_refund_order($order);
    }

    function create_refund_order($order){

        $refund_amount = $this->get_refund_amount($order);

        $refund = wc_create_refund(array(
            'amount' => $refund_amount,
            'reason' => sprintf('Refund for cancellation request'),
            'order_id' => $order->get_id()
        ));


        if (is_wp_error($refund)) {
            // Handle refund creation error
            error_log( 'Error creating refund: ' . $refund->get_error_message());
        }else{
            
            $this->new_state = 'refunded';
            
            pisol_corw_wallet_refund::refund_in_wallet($order, $refund_amount);
        }
    }

    function get_refund_amount($order){

        $total_paid = $order->get_total();

        $total_refunded = $order->get_total_refunded();

        $total_refundable = $total_paid - $total_refunded;

        return $total_refundable;
    }


    function cancel_request_new_status($state){
        if($this->new_state){
            return $this->new_state;
        }
        return $state;
    }

    static function atLeastOneProductRemainInOrder($order){
        $at_least_one_product_remain_in_order = false;
        foreach($order->get_items() as $item_id => $item){
            $initial_qty = $item->get_quantity();
            $returned_qty = $order->get_qty_refunded_for_item( $item->get_id() );
            $remaining_qty = $initial_qty + $returned_qty;
            if($remaining_qty > 0){
                $at_least_one_product_remain_in_order = true;
                break;
            }
        }
        return $at_least_one_product_remain_in_order;
    }
}

pi_corw_auto_cancel_order::get_instance();
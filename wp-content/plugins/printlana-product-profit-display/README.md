# Printlana Product Profit Display

## Description
This plugin calculates and displays the actual vendor profit from sub-orders in the Dokan product listing page "Earning" column.

## How It Works

### Default Behavior (Before)
- The "Earning" column showed potential commission based on product price
- Did not reflect actual sales or profits from orders

### New Behavior (After)
- The "Earning" column now shows actual profit from sub-orders only
- Calculates based on completed and processing orders
- Uses vendor earning data from sub-orders
- Only counts orders where `parent_id != 0` (sub-orders)

## Calculation Method

The plugin:
1. Queries all sub-orders for the current vendor using `_dokan_vendor_id` meta
2. Filters for completed and processing orders only
3. For each order item matching the product:
   - Gets the item subtotal
   - Gets the vendor earning from order meta (`_dokan_vendor_earning`)
   - Calculates profit ratio: `vendor_earning / order_total`
   - Calculates item profit: `item_subtotal * profit_ratio`
4. Sums all item profits across all sub-orders

## Technical Details

### Template Override
- Overrides: `dokan-lite/templates/products/products-listing-row.php`
- Uses Dokan's template override system via `dokan_get_template_part` filter
- Template location: `templates/products-listing-row.php`

### Main Function
`Printlana_Product_Profit_Display::calculate_product_profit_from_suborders($product, $vendor_id)`

### Filter Hook
`printlana_product_profit_from_suborders` - Allows customization of the profit calculation

Example:
```php
add_filter('printlana_product_profit_from_suborders', function($total_profit, $product_id, $vendor_id) {
    // Custom calculation logic here
    return $total_profit;
}, 10, 3);
```

## Installation

1. Upload the plugin folder to `wp-content/plugins/`
2. Activate the plugin through the WordPress admin panel
3. No configuration needed - it works automatically

## Requirements

- WordPress 5.0+
- WooCommerce 3.0+
- Dokan Lite or Dokan Pro
- Existing sub-order system (orders with parent orders)

## Notes

- Performance: For large stores with many orders, consider implementing caching
- Only counts sub-orders (parent_id != 0)
- Excludes parent orders from calculation
- Works with the vendor's `_dokan_vendor_id` meta key

# Custom Product Table - Translation Guide

This plugin is fully translatable and WPML-ready.

## Translation Methods

### Method 1: Using WPML (Recommended for multilingual sites)

1. **Install WPML** and **WPML String Translation** plugins
2. Go to **WPML → String Translation** in WordPress admin
3. Search for domain: `custom-product-table`
4. All plugin strings will be listed there
5. Translate each string for your desired languages

#### WPML Features:
- Automatic string detection via `icl_register_string()`
- Full support for Arabic and RTL languages
- Strings are automatically available in WPML String Translation interface
- Language switcher integration

### Method 2: Using Standard WordPress .po/.mo Files

1. **Locate Translation Files**: Navigate to `wp-content/mu-plugins/languages/`
2. **Choose Your Method**:
   - Use the provided `custom-product-table-ar.po` for Arabic
   - Or create a new translation from `custom-product-table.pot`

3. **Using Poedit** (Recommended):
   - Download [Poedit](https://poedit.net/)
   - Open `custom-product-table.pot` or `custom-product-table-ar.po`
   - Translate all strings
   - Save the file (this generates the `.mo` file automatically)
   - Name format: `custom-product-table-{language_code}.po` and `.mo`
     - Arabic: `custom-product-table-ar.po` / `custom-product-table-ar.mo`
     - French: `custom-product-table-fr_FR.po` / `custom-product-table-fr_FR.mo`
     - Spanish: `custom-product-table-es_ES.po` / `custom-product-table-es_ES.mo`

4. **Upload Files**: Place both `.po` and `.mo` files in `wp-content/mu-plugins/languages/`

## Translatable Strings

The following strings are available for translation:

### Banner Section
- "Minimum Order" (الحد الأدنى للطلب)
- "Piece" (قطعة)
- "Price per piece decreases with increased order quantity" (سعر القطعة ينخفض مع زيادة كمية الطلب)

### Table Headers
- "Product" (المنتج)
- "Price of Piece" (سعر القطعة)
- "Time of Production" (وقت الإنتاج)
- "Quantity" (الكمية)

### Button Labels & Accessibility
- "Decrease quantity" (تقليل الكمية)
- "Increase quantity" (زيادة الكمية)

### System Messages
- "Loading..." (جاري التحميل...)
- "No image" (لا توجد صورة)
- "N/A" (غير متوفر)

## Language Code Reference

Common WordPress language codes:
- Arabic: `ar`
- English (US): `en_US`
- French: `fr_FR`
- Spanish: `es_ES`
- German: `de_DE`
- Italian: `it_IT`
- Portuguese (Brazil): `pt_BR`
- Russian: `ru_RU`
- Turkish: `tr_TR`

## Testing Your Translation

1. Go to **Settings → General** in WordPress admin
2. Change **Site Language** to your desired language
3. Clear cache if using a caching plugin
4. Reload the product page with the `[product_table]` shortcode
5. All strings should now display in the selected language

## For Developers

### Adding New Translatable Strings

When adding new strings to the plugin:

1. **In PHP**: Use `__()`, `_e()`, or `esc_html_e()` with text domain `custom-product-table`
   ```php
   echo __('My String', 'custom-product-table');
   esc_html_e('My String', 'custom-product-table');
   ```

2. **Register with WPML** (in `register_wpml_strings()` method):
   ```php
   icl_register_string('custom-product-table', 'My String', 'My String');
   ```

3. **For JavaScript strings**: Add to `$i18n_strings` array in `render_scripts()`:
   ```php
   'my_string' => function_exists('icl_t') ?
       icl_t('custom-product-table', 'My String', __('My String', 'custom-product-table')) :
       __('My String', 'custom-product-table'),
   ```

4. **Update translation files**: Regenerate `.pot` file and update existing `.po` files

## Troubleshooting

### Translations not showing?
1. Ensure `.mo` file exists alongside `.po` file
2. Check file permissions (should be readable by web server)
3. Clear all caches (site cache, object cache, WPML cache)
4. Verify correct language code in filename
5. Check WordPress language setting in **Settings → General**

### WPML not detecting strings?
1. Ensure WPML String Translation is activated
2. Go to **WPML → Theme and plugins localization** and scan
3. Check that strings are registered in `register_wpml_strings()` method

## Support

For issues or questions:
- GitHub: https://github.com/yourrepo
- Website: https://printlana.com
- Text Domain: `custom-product-table`

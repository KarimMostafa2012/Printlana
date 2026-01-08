# PageSpeed Insights - Action Plan for Printlana.com

## 📊 CURRENT PERFORMANCE SCORES

- **Mobile: 59/100** (NEEDS IMPROVEMENT) 🟡
- **Desktop: 83/100** (GOOD) 🟢

---

## 🚨 CRITICAL ISSUES FOUND

### 1. **Render-Blocking Resources: 442ms**
Resources preventing page from loading quickly:
- CSS files loading in `<head>` block rendering
- JavaScript files blocking HTML parsing

### 2. **Unused JavaScript: 38.8 KB**
Scripts loading but not being used on the page

### 3. **Unused CSS: 128.4 KB**
Style rules loading but not applied to any elements

---

## ✅ FIXES ALREADY IMPLEMENTED

Your mu-plugins are already addressing some issues:

1. ✅ **performance-optimization.php**
   - Defers non-critical JavaScript
   - Removes unused WooCommerce scripts
   - Lazy loads images

2. ✅ **custom-toast-notification.php**
   - Replaced heavy jQuery toast libraries
   - Removed duplicate toast scripts

---

## 🛠️ IMMEDIATE ACTIONS NEEDED

### Priority 1: Critical Path Optimization (30 minutes)

#### A. **Inline Critical CSS**
```php
// Add to performance-optimization.php

add_action('wp_head', 'pl_inline_critical_css', 2);
function pl_inline_critical_css() {
    // Extract above-the-fold CSS and inline it
    ?>
    <style id="critical-css">
    /* Add your critical CSS here - extracted from main stylesheet */
    /* Header, navigation, hero section styles */
    </style>
    <?php
}
```

#### B. **Defer All Non-Critical CSS**
```php
// Add to performance-optimization.php after line 165

add_filter('style_loader_tag', 'pl_defer_non_critical_css', 10, 4);
function pl_defer_non_critical_css($html, $handle, $href, $media) {
    // Don't defer critical CSS
    $critical_styles = array('critical-css', 'elementor-frontend');

    if (in_array($handle, $critical_styles)) {
        return $html;
    }

    // Defer non-critical CSS using media="print" trick
    return str_replace(
        "media='all'",
        "media='print' onload=\"this.media='all'\"",
        $html
    );
}
```

---

### Priority 2: Eliminate Render-Blocking (15 minutes)

The mu-plugin already defers JavaScript, but let's be more aggressive:

```php
// Update pl_defer_non_critical_scripts function in performance-optimization.php

// Add these to the defer list:
$defer_scripts = array(
    // Existing ones...
    'wc-add-to-cart',
    'woocommerce',

    // ADD THESE:
    'jquery-ui-core',
    'jquery-ui-widget',
    'jquery-ui-mouse',
    'selectWoo',
    'react',
    'react-dom',
    'alg-wc-wish-list',
    'wish-list-counter',
);
```

---

### Priority 3: Reduce Unused Code (Automatic)

#### A. **Install Autoptimize Plugin**
```
WordPress Admin → Plugins → Add New → Search "Autoptimize"

Settings:
✅ Optimize JavaScript Code
✅ Aggregate JS-files
✅ Remove unused CSS (Pro feature or use alternative)
✅ Inline and Defer CSS
✅ Optimize Google Fonts
```

**OR use Asset CleanUp (Better control)**
```
WordPress Admin → Plugins → Add New → Search "Asset CleanUp"

Then visit each page type and disable unused scripts:
- Home: Disable checkout, cart, account scripts
- Product: Disable checkout, cart scripts
- Shop: Disable single-product, checkout scripts
```

---

### Priority 4: Image Optimization (Immediate Impact)

From HAR analysis, images are taking 3-4 seconds to load!

#### A. **Install ShortPixel or Imagify**
```
Recommended: ShortPixel Image Optimizer

Settings:
✅ Compression level: Lossy (best balance)
✅ Convert to WebP: Yes
✅ Lazy Load: Yes
✅ Resize large images: Max 1920px width
✅ Optimize existing images: Yes (bulk optimize)
```

#### B. **Use CDN for Images**
```
Install: Cloudflare (Free)

1. Sign up at cloudflare.com
2. Add your domain
3. Update nameservers
4. Enable:
   - Auto Minify (JS, CSS, HTML)
   - Brotli compression
   - Rocket Loader (defer JS)
   - Polish (image optimization)
```

---

### Priority 5: Enable Compression & Caching

#### A. **Enable GZIP/Brotli**
Add to `.htaccess`:
```apache
# Enable GZIP compression
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json application/xml
</IfModule>

# Enable Brotli if available
<IfModule mod_brotli.c>
  AddOutputFilterByType BROTLI_COMPRESS text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>
```

#### B. **Install WP Rocket or LiteSpeed Cache**
```
Recommended: WP Rocket ($59/year)

Auto-configured settings will:
✅ Page caching
✅ Cache preloading
✅ GZIP compression
✅ Minify JS/CSS
✅ Combine JS/CSS files
✅ Defer JavaScript
✅ Remove unused CSS
✅ Lazy load images
✅ Database optimization
```

**Free Alternative:**
```
LiteSpeed Cache (if using LiteSpeed server)
OR
W3 Total Cache + Autoptimize
```

---

## 📈 EXPECTED IMPROVEMENTS

### Before (Current):
- Mobile: 59/100 🟡
- Desktop: 83/100 🟢
- FCP: ~3-4 seconds
- LCP: ~4-5 seconds
- TBT: 442ms

### After (With all optimizations):
- Mobile: 85-90/100 🟢
- Desktop: 95-100/100 🟢
- FCP: <1.5 seconds
- LCP: <2.5 seconds
- TBT: <150ms

**Estimated Improvement: +30-40 points on mobile!**

---

## 🎯 QUICK WINS (Do These First)

### 1. Install & Configure WP Rocket (10 min)
**Impact:** +20-30 points immediately
- Automatic caching
- Minification
- Defer JavaScript
- Remove unused CSS

### 2. Install ShortPixel & Bulk Optimize (30 min)
**Impact:** +10-15 points
- Compress all images
- Convert to WebP
- Lazy load images

### 3. Setup Cloudflare CDN (20 min)
**Impact:** +5-10 points
- Free CDN
- Additional minification
- Image optimization
- DDoS protection

### 4. Remove Debug Logging (5 min)
**Impact:** +2-5 points
- Disable debug mode in functions.php
- Remove console.log() calls
- Clean up debug code (lines 1855-1918)

**Total Time: ~65 minutes**
**Expected Result: Mobile score 80-85/100**

---

## 📋 IMPLEMENTATION CHECKLIST

### Week 1: Critical Fixes
- [ ] Install WP Rocket or LiteSpeed Cache
- [ ] Install ShortPixel Image Optimizer
- [ ] Bulk optimize all images
- [ ] Setup Cloudflare CDN
- [ ] Remove debug logging from functions.php
- [ ] Test site on mobile

### Week 2: Fine-Tuning
- [ ] Add critical CSS inlining (code above)
- [ ] Defer non-critical CSS (code above)
- [ ] Install Asset CleanUp
- [ ] Disable unused scripts per page
- [ ] Enable Elementor performance experiments
- [ ] Run PageSpeed Insights again

### Week 3: Advanced Optimization
- [ ] Database optimization (WP-Optimize)
- [ ] Reduce external requests
- [ ] Implement preconnect/prefetch
- [ ] Review and optimize fonts
- [ ] Enable HTTP/2 Push (if supported)

---

## 🔍 TESTING AFTER CHANGES

### 1. Clear All Caches
```bash
# WordPress cache (via plugin)
# Browser cache (Ctrl+Shift+Delete)
# CDN cache (if using Cloudflare)
```

### 2. Test Performance
```
PageSpeed Insights: https://pagespeed.web.dev/
GTmetrix: https://gtmetrix.com/
WebPageTest: https://www.webpagetest.org/
```

### 3. Test Functionality
- [ ] Homepage loads correctly
- [ ] Product pages work
- [ ] Add to cart functions
- [ ] Checkout process
- [ ] My Account area
- [ ] Mobile responsiveness

---

## ⚠️ WARNINGS

### Don't Do These:
❌ Don't enable "Remove Query Strings" in WP Rocket (we already handle this)
❌ Don't enable "Delay JavaScript Execution" initially (test first)
❌ Don't combine ALL JavaScript (can break some plugins)
❌ Don't lazy load above-the-fold images
❌ Don't remove jQuery (many plugins depend on it)

### Safe to Enable:
✅ Page caching
✅ GZIP compression
✅ Minify CSS/JS
✅ Defer JavaScript (we already do this)
✅ Lazy load images
✅ Database optimization
✅ CDN integration

---

## 🆘 IF SOMETHING BREAKS

### Quick Rollback:
1. Deactivate recently installed plugins
2. Clear all caches
3. Test again
4. Re-enable one at a time

### Common Issues:
**Site not loading:**
- Disable page caching
- Check .htaccess file
- Restore from backup

**JavaScript errors:**
- Disable JS minification
- Disable JS combining
- Check browser console

**Images not loading:**
- Disable lazy load
- Regenerate WebP images
- Check CDN settings

---

## 📞 NEXT STEPS

Would you like me to:
1. **Add the critical CSS and defer CSS code to the mu-plugin?**
2. **Create a script to identify and remove debug code from functions.php?**
3. **Generate a list of specific plugins to install with configurations?**
4. **Create a pre-launch performance checklist?**

Let me know which you'd like to tackle first!

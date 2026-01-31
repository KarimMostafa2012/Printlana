# Client-Side Background Remover Plugin

## Canvas-Based Background Removal for WordPress

This plugin removes backgrounds from images directly in your browser using a custom canvas-based algorithm. No external APIs or services required!

## 🎯 How It Works

### Algorithm Overview

1. **Background Detection**: Analyzes the four corners of the image to determine the dominant background color
2. **Color Matching**: Compares each pixel against the background color using Euclidean distance
3. **Transparency Application**: Makes similar pixels transparent with gradual alpha based on similarity
4. **Edge Smoothing**: Optional smoothing algorithm to clean up rough edges
5. **Feathering**: Optional edge softening for natural blending

### Best For

✅ Product photos with solid backgrounds (white, gray, colored)
✅ Images with high contrast between subject and background
✅ Graphics with uniform backgrounds
✅ E-commerce product images

### Limitations

❌ Complex backgrounds (patterns, gradients, multiple colors)
❌ Subjects with colors similar to the background
❌ Images where subject touches all four corners
❌ Photos with hair/fur details (use ML-based solutions instead)

## 📦 Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Create a `js` subfolder in the plugin directory
3. Place `bg-remover.js` in the `js` folder
4. Activate the plugin through the WordPress admin

**Folder Structure:**
```
wp-content/plugins/client-side-bg-remover/
├── bg-remover-plugin.php
└── js/
    └── bg-remover.js
```

## 🚀 Usage

### Basic Usage

1. Go to **Media Library** in WordPress admin
2. Click on any image
3. Click the **"Remove Background"** button
4. Wait for processing (happens in your browser)
5. The processed image will be added to your media library

### Settings

Access settings at **Settings > BG Remover**

#### Available Settings

**Color Tolerance** (5-100, default: 30)
- Controls how aggressively colors are removed
- Lower = only exact matches
- Higher = removes more similar colors
- Increase for backgrounds with slight variations

**Edge Smoothing** (0-5, default: 2)
- Smooths jagged edges
- Uses averaging algorithm on neighboring pixels
- Higher values = smoother but possibly blurrier edges

**Edge Feathering** (0-5, default: 1)
- Softens edge transitions
- Creates natural blending
- Useful for compositing on other backgrounds

**Output Quality** (0.5-1.0, default: 0.8)
- PNG compression quality
- Higher = better quality, larger file size
- Lower = smaller file, slight quality loss

## 🔧 How to Adjust Settings for Different Images

### White Background Products
```
Tolerance: 25-35
Smoothing: 2-3
Feather: 1-2
Quality: 0.8
```

### Gray Background Products
```
Tolerance: 30-40
Smoothing: 2-3
Feather: 1
Quality: 0.8
```

### Colored Backgrounds (Solid)
```
Tolerance: 20-30
Smoothing: 2
Feather: 1-2
Quality: 0.8
```

### Gradient Backgrounds
```
Tolerance: 40-60
Smoothing: 3-4
Feather: 2-3
Quality: 0.8
```

## 🎨 Technical Details

### Algorithm Steps

1. **Image Loading**
    - Loads image into HTML5 Canvas
    - Extracts pixel data as RGBA array

2. **Background Color Detection**
   ```javascript
   // Samples four corners
   corners = [topLeft, topRight, bottomLeft, bottomRight]
   backgroundColor = average(corners)
   ```

3. **Color Distance Calculation**
   ```javascript
   // Euclidean distance in RGB space
   distance = √((r1-r2)² + (g1-g2)² + (b1-b2)²)
   ```

4. **Transparency Application**
   ```javascript
   if (distance < tolerance) {
       alpha = (distance / tolerance) * 255
   }
   ```

5. **Edge Smoothing** (optional)
    - Averages alpha values with 8 neighboring pixels
    - Repeated for smoothing iterations

6. **Edge Feathering** (optional)
    - Gaussian-like blur on semi-transparent pixels
    - Only affects edge areas

### Performance

- **Processing Time**: 1-3 seconds for typical images (depending on size)
- **Browser Requirements**: Modern browsers with Canvas support
- **Memory Usage**: Proportional to image dimensions
- **File Size**: PNG output, typically 20-50% larger than JPG

## 💡 Pro Tips

1. **Best Results**: Use images where the subject doesn't touch the edges
2. **Testing**: Start with default settings, adjust tolerance first
3. **Quality vs Speed**: Lower resolution images process faster
4. **Duplicates**: Plugin detects and reuses processed images
5. **Batch Processing**: Process images one at a time for best results

## 🔒 Security Features

- AJAX nonce verification
- Hash-based duplicate detection
- Sanitized inputs
- Client-side processing (no data sent to external servers)

## 🐛 Troubleshooting

### Button Doesn't Appear
- Check if plugin is activated
- Ensure you're viewing an image attachment
- Verify plugin is enabled in settings

### Poor Results
- Increase tolerance if background isn't fully removed
- Decrease tolerance if subject is being removed
- Ensure subject contrasts with background
- Try increasing smoothing for cleaner edges

### Processing Errors
- Check browser console for errors
- Ensure image is accessible (CORS)
- Try with smaller images first
- Clear browser cache

## 📊 Browser Compatibility

- ✅ Chrome/Edge 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Opera 76+

## 🔄 Future Enhancements

Possible improvements for future versions:

- [ ] Multi-point background sampling
- [ ] Manual background color picker
- [ ] Batch processing interface
- [ ] Preview before save
- [ ] Adjustable sample points
- [ ] Magic wand selection tool
- [ ] Undo/redo functionality

## 📝 License

This is a custom plugin for PrintLana.

## 🤝 Support

For issues or questions, contact your development team.

---

**Version**: 2.0.0  
**Author**: PrintLana Development Team  
**Tested up to**: WordPress 6.4
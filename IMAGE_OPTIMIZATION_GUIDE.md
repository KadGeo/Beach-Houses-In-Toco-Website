# Image Optimization Guide

## Critical Issues from PageSpeed Insights

### 1. Background Image (704KB - **HIGHEST PRIORITY**)
**File**: `Beach houses in Toco Logo Background Image.png`
**Current Size**: 704.7 KB
**Potential Savings**: 448.7 KB (64% reduction)

#### Solution Options:

**Option A: Convert to WebP (Recommended)**
```bash
# Using online tool: https://squoosh.app
# Or CLI tool:
cwebp "Beach houses in Toco Logo Background Image.png" -q 85 -o "Beach houses in Toco Logo Background Image.webp"
```

**Expected result**: ~200-250 KB (65% smaller)

**Option B: Optimize PNG**
```bash
# Using online tool: https://tinypng.com
# Or CLI tool:
pngquant --quality=65-80 "Beach houses in Toco Logo Background Image.png" --output "Beach houses in Toco Logo Background Image-optimized.png"
```

**Expected result**: ~350 KB (50% smaller)

**Implementation** (after conversion):
Update `main.css` or wherever this background is set:
```css
/* Before */
background-image: url('../img/Beach houses in Toco Logo Background Image.png');

/* After (with fallback) */
background-image: url('../img/Beach houses in Toco Logo Background Image.webp');
background-image: image-set(
  url('../img/Beach houses in Toco Logo Background Image.webp') type('image/webp'),
  url('../img/Beach houses in Toco Logo Background Image.png') type('image/png')
);
```

---

### 2. Logo Image (65KB displayed as 50x50px)
**File**: `Beach houses in Toco Logo.png`
**Current Size**: 65.3 KB (512x512px)
**Display Size**: 50x50px (mobile), 100x100px (desktop)
**Potential Savings**: 64.9 KB

#### Solution: Create Multiple Sizes

**Step 1: Create optimized sizes**
```bash
# Small logo for mobile (50x50)
convert "Beach houses in Toco Logo.png" -resize 50x50 "Beach houses in Toco Logo-50.webp"

# Medium logo for desktop (100x100)
convert "Beach houses in Toco Logo.png" -resize 100x100 "Beach houses in Toco Logo-100.webp"

# Large logo for retina displays (200x200)
convert "Beach houses in Toco Logo.png" -resize 200x200 "Beach houses in Toco Logo-200.webp"
```

**Step 2: Update HTML with responsive images**

Replace in `index.html` and `blog.html`:
```html
<!-- Before -->
<img src="assets\img\Beach houses in Toco Logo.png" alt="Beach Houses in Toco Logo" class="logo-img" loading="eager" width="100" height="100">

<!-- After -->
<img
  srcset="
    assets/img/Beach houses in Toco Logo-50.webp 50w,
    assets/img/Beach houses in Toco Logo-100.webp 100w,
    assets/img/Beach houses in Toco Logo-200.webp 200w
  "
  sizes="(max-width: 768px) 50px, 100px"
  src="assets/img/Beach houses in Toco Logo-100.webp"
  alt="Beach Houses in Toco Logo"
  class="logo-img"
  loading="eager"
  width="100"
  height="100">
```

**Expected savings**:
- Mobile: ~2 KB (97% smaller)
- Desktop: ~5 KB (92% smaller)

---

## Tools for Image Optimization

### Online Tools (No Installation)
1. **Squoosh** - https://squoosh.app (Best for WebP conversion)
2. **TinyPNG** - https://tinypng.com (Good for PNG compression)
3. **CloudConvert** - https://cloudconvert.com (Batch conversion)

### Command Line Tools

**ImageMagick** (Resize & Convert)
```bash
# Install
# Windows: Download from https://imagemagick.org/
# Mac: brew install imagemagick
# Linux: sudo apt-get install imagemagick

# Usage
convert input.png -resize 100x100 output.webp
```

**cwebp** (Google's WebP encoder)
```bash
# Install
# Download from https://developers.google.com/speed/webp/download

# Usage
cwebp -q 85 input.png -o output.webp
```

**pngquant** (PNG compression)
```bash
# Install
# Windows: Download from https://pngquant.org/
# Mac: brew install pngquant
# Linux: sudo apt-get install pngquant

# Usage
pngquant --quality=65-80 input.png --output output.png
```

---

## Step-by-Step Optimization Process

### For Background Image

1. **Download the image** from your server or locate it locally:
   - `assets/img/Beach houses in Toco Logo Background Image.png`

2. **Go to Squoosh.app**:
   - Upload the image
   - Select "WebP" from the right panel
   - Set quality to 85
   - Download as `Beach houses in Toco Logo Background Image.webp`

3. **Upload to server**:
   - Place in `assets/img/` folder

4. **Update CSS** (find where this image is used):
   - Search for "Background Image.png" in your CSS files
   - Update to use WebP with PNG fallback

5. **Test**:
   - Verify image displays correctly
   - Check file size reduction

---

### For Logo Images

1. **Use Squoosh.app** to create 3 versions:

**Version 1: 50x50 (Mobile)**
   - Upload original
   - Resize to 50x50
   - Format: WebP, Quality: 90
   - Save as: `Beach houses in Toco Logo-50.webp`

**Version 2: 100x100 (Desktop)**
   - Upload original
   - Resize to 100x100
   - Format: WebP, Quality: 90
   - Save as: `Beach houses in Toco Logo-100.webp`

**Version 3: 200x200 (Retina)**
   - Upload original
   - Resize to 200x200
   - Format: WebP, Quality: 90
   - Save as: `Beach houses in Toco Logo-200.webp`

2. **Upload all 3 files** to `assets/img/`

3. **Update HTML** in both `index.html` and `blog.html`:
   - Replace `<img>` tag with responsive version (see code above)

4. **Test**:
   - Check logo displays on mobile (50px)
   - Check logo displays on desktop (100px)
   - Verify sharp on retina displays

---

## Expected Performance Gains

### Before Optimization:
- Background Image: 704 KB
- Logo Image: 65 KB
- **Total**: 769 KB

### After Optimization:
- Background Image (WebP): ~230 KB
- Logo Images (all 3 combined): ~10 KB
- **Total**: 240 KB

**Savings**: 529 KB (69% reduction)

**PageSpeed Impact**:
- Reduces "Largest Contentful Paint" by ~1-1.5 seconds
- Improves "Speed Index" significantly
- Better "Time to Interactive"

---

## Verification Checklist

After completing optimizations:

- [ ] Background image is WebP format
- [ ] Background image is under 250 KB
- [ ] Logo has 3 sizes (50, 100, 200)
- [ ] Logo uses `srcset` attribute
- [ ] All images display correctly on mobile
- [ ] All images display correctly on desktop
- [ ] No broken images
- [ ] Run PageSpeed Insights again
- [ ] Score improved by 15-20 points

---

## Fallback for Older Browsers

If you need to support very old browsers that don't support WebP:

```html
<picture>
  <source srcset="assets/img/logo-50.webp 50w, assets/img/logo-100.webp 100w" type="image/webp">
  <source srcset="assets/img/logo-50.png 50w, assets/img/logo-100.png 100w" type="image/png">
  <img src="assets/img/logo-100.png" alt="Beach Houses in Toco Logo" class="logo-img" loading="eager" width="100" height="100">
</picture>
```

**Note**: 97% of browsers support WebP as of 2024, so fallbacks are usually unnecessary.

---

## Quick Win: Lazy Load Background

If you can't optimize the background image immediately, at least defer it:

```css
/* In custom.css */
body {
  /* Remove or comment out: */
  /* background-image: url('../img/Beach houses in Toco Logo Background Image.png'); */
}

/* Add this JavaScript to defer loading */
```

```javascript
// At end of index.html
window.addEventListener('load', function() {
  document.body.style.backgroundImage = "url('assets/img/Beach houses in Toco Logo Background Image.png')";
});
```

This loads the background after page content, improving initial load time.

---

## Maintenance

### When Adding New Images:
1. Always convert to WebP first
2. Create multiple sizes for responsive images
3. Use `loading="lazy"` for below-the-fold images
4. Specify width and height attributes
5. Compress before uploading (target: under 100 KB per image)

### Recommended Image Sizes:
- **Logo**: 50px, 100px, 200px (WebP, ~2-5 KB each)
- **Hero images**: 800px, 1200px, 1920px (WebP, quality 75-85)
- **Content images**: 600px, 800px (WebP, quality 75-85)
- **Thumbnails**: 150px, 300px (WebP, quality 80)

---

Last Updated: 2025-12-27

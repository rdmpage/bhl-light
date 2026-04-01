# IIIF Implementation Notes

## Overview

BHL-Light serves page images via the [IIIF Image API 2.1](https://iiif.io/api/image/2.1/) at **level 1** compliance, and item-level manifests via the [IIIF Presentation API 3.0](https://iiif.io/api/presentation/3.0/).

There is no dedicated IIIF image server. Instead, PHP handles IIIF URL routing and proxies image requests through an [imgproxy](https://imgproxy.net/) installation at `https://images.bionames.org`. The source images are stored on Hetzner S3-compatible object storage.

## URL Structure

All IIIF URLs use the page identifier and stay on the application's own domain — the imgproxy server URL is never exposed to clients.

| Endpoint | Example |
|---|---|
| Image info | `page/{PageID}/info.json` |
| Image request | `page/{PageID}/{region}/{size}/{rotation}/{quality}.{format}` |
| Manifest | `item/{ItemID}/manifest.json` |

`.htaccess` rewrites these clean URLs to query parameters handled by `index.php`.

## Architecture

### Request flow

1. IIIF viewer requests e.g. `page/14210178/0,0,400,600/300,/0/default.jpg`
2. `.htaccess` rewrites to `?pageimage=14210178&region=0,0,400,600&size=300,&rotation=0&quality=default&format=jpg`
3. `display_page_iiif_image()` in `index.php`:
   - Validates parameters (rotation, quality, format, region)
   - Looks up the source image path on Hetzner S3 via `get_page_image_url_ia()`
   - Calls `imgproxy_path_iiif()` to build a signed imgproxy processing URL
   - Fetches the processed image from imgproxy via cURL
   - Streams it back to the client with appropriate headers (CORS, Cache-Control, Content-Type)

### Key files

| File | Role |
|---|---|
| `.htaccess` | URL rewriting for IIIF routes |
| `index.php` | `display_page_iiif_info()` — serves `info.json`; `display_page_iiif_image()` — proxies image requests |
| `imgproxy.php` | `imgproxy_path_iiif()` — translates IIIF parameters to imgproxy processing options |
| `core.php` | `get_item_manifest()` — generates IIIF Presentation API 3.0 manifests |

### imgproxy mapping (`imgproxy_path_iiif`)

| IIIF parameter | imgproxy processing option |
|---|---|
| `region=x,y,w,h` | `c:{w}:{h}/g:nowe:{x}:{y}` (crop with north-west gravity offset) |
| `region=full` | *(no crop)* |
| `size=w,` | `rs:auto:{w}:0:0` |
| `size=,h` | `rs:auto:0:{h}:0` |
| `size=w,h` | `rs:force:{w}:{h}:0` (exact, may distort) |
| `size=!w,h` | `rs:fit:{w}:{h}:0` (best fit, maintains aspect ratio) |
| `size=full` or `max` | *(no resize)* |
| `format=jpg\|png\|webp` | `f:{ext}` |

## Supported IIIF Features

Declared in `info.json` under the level 1 profile:

- **Region**: `full`, pixel coordinates (`regionByPx`)
- **Size**: `full`, `max`, `w,` (`sizeByW`), `,h` (`sizeByH`), `w,h` (`sizeByWh`), `!w,h` (`sizeByConfinedWh`)
- **Rotation**: `0` only
- **Quality**: `default` only
- **Formats**: `jpg`, `webp`, `png`
- **Other**: `cors`, `baseUriRedirect`

## Image Dimension Bug (Fixed)

### Symptom

When zooming in with IIIF viewers (tested with TIFY), the page image would split into multiple tiled copies of the same page.

### Root cause

The source jp2 images from Internet Archive are converted to webp at **800px wide** during import (see `jp2towebp()` in `import/upload-images.php`, parameter `$resize_width = 800`). However, `info.json` and the manifest were reporting the **original scan dimensions** from CouchDB (e.g. 1986×3279).

When a viewer requested a crop region based on those larger dimensions (e.g. `0,0,993,1640`), imgproxy attempted to crop a region larger than the actual 800px-wide source image. This caused imgproxy to tile/repeat the source image to fill the requested area.

### Fix

Both `info.json` and the manifest now scale the reported dimensions to match the actual stored resolution:

```php
$stored_width = 800;
$scale = $stored_width / $original_width;
$actual_height = round($original_height * $scale);
```

The `$stored_width = 800` constant appears in three places:
- `index.php` → `display_page_iiif_info()`
- `core.php` → `get_item_manifest()`

## Improving Zoom Quality

The current maximum zoom resolution is limited to 800px wide per page. This is adequate for reading text but not ideal for examining fine details (illustrations, maps, specimen labels).

### Option 1: Re-import at higher resolution

Re-run the jp2→webp conversion at a higher width. In `import/upload-images.php`:

```php
// Current:
function jp2towebp($basedir, $resize_width = 800, $force = false)

// Higher resolution:
function jp2towebp($basedir, $resize_width = 2400, $force = false)
```

Then update `$stored_width` in `index.php` and `core.php` to match.

**Trade-offs**: S3 storage cost increases (~9× for 3× linear increase). Import time increases. But this is probably the simplest path to better zoom and still keeps file sizes reasonable with webp compression.

### Option 2: Store full-resolution images

Remove the resize entirely and store the original jp2 dimensions as webp. Some BHL scans are 4000–6000px wide.

```php
// In upload-images.php, skip the mogrify resize:
$command = "mogrify -format jpg $source_filename";  // no -resize
```

Update `$stored_width` references to use the actual stored width per image (would need to query or store this). The `info.json` and manifest could then report the true dimensions directly from CouchDB again.

**Trade-offs**: Significantly higher S3 storage. Full-resolution webp files can still be large (500KB–2MB per page). imgproxy handles on-the-fly downscaling well, so serving smaller sizes to viewers remains fast.

### Option 3: Add IIIF tiling support

Declare tile sizes in `info.json` so viewers can request individual tiles at specific scale factors:

```json
{
  "tiles": [
    {
      "width": 512,
      "scaleFactors": [1, 2, 4, 8]
    }
  ]
}
```

This requires either full-resolution source images (Option 2) or pre-generated tile pyramids. imgproxy can generate tiles on the fly from full-resolution sources, making Option 2 + Option 3 a natural combination.

**Trade-offs**: Best user experience for deep zoom. Requires full-resolution sources. Many more HTTP requests per page view (one per tile), but each is small and cacheable.

### Recommended path

**Option 1** (re-import at 2400px) is the quickest win — a single parameter change in the import script, a bulk re-conversion of existing images, and updating the `$stored_width` constant. It roughly triples zoom depth with modest storage increase.

For a production-quality deep zoom experience, **Option 2 + 3** (full-resolution + tiling) is the long-term goal.

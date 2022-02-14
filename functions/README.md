# Cloud Functions

These are Google Cloud Functions used for tasks that DreamHost can't handle.

## Development

A GoLand/Intellij config is included in the fmnas-site project.

These functions are deployed by the deploy-functions GitHub Actions workflow.

## Image functions

### resize-image

This resizes images in cases (such as HEIC and WebP) where the ImageMagick version on Dreamhost is too outdated to read
the original image or otherwise fails.

### image-size

This gets the size of an image in cases (such as HEIC and WebP) where the ImageMagick version on Dreamhost is too
outdated to read the image or otherwise fails.

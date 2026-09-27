#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
rsvg-convert -w 128 -h 128 assets/icon.svg -o assets/icon-128x128.png
rsvg-convert -w 256 -h 256 assets/icon.svg -o assets/icon-256x256.png
rsvg-convert -w 772 -h 250 assets/banner.svg -o assets/banner-772x250.png
rsvg-convert -w 1544 -h 500 assets/banner.svg -o assets/banner-1544x500.png

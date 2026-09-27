#!/usr/bin/env python3
"""Build a deterministic plugin ZIP from an explicit runtime allowlist."""
from pathlib import Path
import re
import zipfile

SLUG = 'beautiful-recent-posts-widget'
ROOT = Path(__file__).resolve().parents[1]
REQUIRED = (
    f'{SLUG}.php', 'readme.txt', 'LICENSE', 'includes/render.php',
    'includes/class-brp-widget.php', 'blocks/recent-posts/block.json',
    'blocks/recent-posts/editor.js', 'blocks/recent-posts/render.php', 'css/brpw.css',
)


def build(root=ROOT):
    version = re.search(r"define\( 'BRPW_VERSION', '([^']+)'", (root / f'{SLUG}.php').read_text()).group(1)
    if not re.fullmatch(r'\d+\.\d+\.\d+(?:-(?:alpha|beta|rc)\.\d+)?', version):
        raise ValueError('Invalid package version')
    files = [root / name for name in (f'{SLUG}.php', 'readme.txt', 'LICENSE')]
    for directory in ('includes', 'blocks', 'css', 'languages'):
        folder = root / directory
        if folder.exists():
            files += [path for path in folder.rglob('*') if path.is_file()
                      and path.suffix in {'.php', '.js', '.json', '.css', '.mo'}
                      and not any(part.startswith('.') for part in path.relative_to(root).parts)]
    for required in REQUIRED:
        if root / required not in files or not (root / required).is_file():
            raise ValueError(f'Missing runtime file: {required}')
    if any(path.is_symlink() or not path.resolve().is_relative_to(root.resolve()) for path in files):
        raise ValueError('Runtime symlinks are not allowed')
    output = root / 'dist' / f'{SLUG}-{version}.zip'
    output.parent.mkdir(exist_ok=True)
    with zipfile.ZipFile(output, 'w', zipfile.ZIP_DEFLATED) as archive:
        for path in sorted(files):
            info = zipfile.ZipInfo(str(Path(SLUG) / path.relative_to(root)), date_time=(1980, 1, 1, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            archive.writestr(info, path.read_bytes())
    return output


if __name__ == '__main__':
    print(build())

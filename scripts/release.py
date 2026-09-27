#!/usr/bin/env python3
"""Version gates, release staging, and exact-artifact verification. No credentials."""
import argparse
import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import re
import shutil
import subprocess
import tempfile
import zipfile

from package import ROOT, SLUG, REQUIRED, build

VERSION_PATTERN = r'(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-(alpha|beta|rc)\.(0|[1-9]\d*))?'
ARTWORK = ('icon.svg', 'icon-128x128.png', 'icon-256x256.png', 'banner-772x250.png', 'banner-1544x500.png', 'screenshot-1.jpg', 'screenshot-2.jpg', 'screenshot-3.jpg', 'screenshot-4.jpg', 'screenshot-5.jpg', 'screenshot-6.jpg', 'screenshot-7.jpg')


def field(text, name):
    match = re.search(r'^\s*(?:\*\s*)?' + re.escape(name) + r':\s*(.*?)\s*$', text, re.M | re.I)
    if not match:
        raise ValueError(f'Missing {name}')
    return match[1]


def versions(root, tag=None, source=True):
    main = (root / 'BRPWidget.php').read_text()
    bootstrap = (root / f'{SLUG}.php').read_text()
    readme = (root / 'readme.txt').read_text()
    version = field(main, 'Version')
    if not re.fullmatch(VERSION_PATTERN, version):
        raise ValueError(f'Unsupported version: {version}')
    constant = re.search(r"define\(\s*'BRPW_VERSION',\s*'([^']+)'", bootstrap)
    found = {
        'constant': constant[1] if constant else '',
        'readme stable tag': field(readme, 'Stable tag'),
        'block': json.loads((root / 'blocks/recent-posts/block.json').read_text())['version'],
    }
    if source:
        found['package.json'] = json.loads((root / 'package.json').read_text())['version']
    if tag is not None and tag != f'v{version}':
        raise ValueError(f'Release tag {tag!r} must equal v{version}')
    for label, actual in found.items():
        if actual != version:
            raise ValueError(f'{label} version {actual!r} differs from {version}')
    for name in ('Requires at least', 'Requires PHP'):
        if field(main, name) != field(readme, name):
            raise ValueError(f'Mismatched {name}')
    if re.search(r'^\s*\*?\s*Update URI:', main, re.M | re.I):
        raise ValueError('WordPress.org package must not set Update URI')
    notes = changelog(readme, version)
    return {'version': version, 'tag': f'v{version}', 'prerelease': '-' in version, 'notes': notes}


def changelog(readme, version):
    sections = readme.split('== Changelog ==', 1)
    if len(sections) != 2:
        raise ValueError('Missing Changelog section')
    match = re.search(r'^= ' + re.escape(version) + r' =\s*\n(.*?)(?=^=|\Z)', sections[1], re.M | re.S)
    if not match or not match[1].strip():
        raise ValueError(f'Missing changelog for {version}')
    return match[1].strip()


def release_state(info, release):
    if release.get('tag_name') != info['tag'] or release.get('draft') or not release.get('published_at'):
        raise ValueError('Expected a published, non-draft release for this exact tag')
    if bool(release.get('prerelease')) != info['prerelease']:
        raise ValueError('Release prerelease flag does not match the version suffix')


def stage(root=ROOT):
    info = versions(root)
    archive = build(root)
    digest = hashlib.sha256(archive.read_bytes()).hexdigest()
    archive.with_suffix('.zip.sha256').write_text(f'{digest}  {archive.name}\n')
    (archive.parent / 'release-notes.md').write_text(
        f"# Beautiful Recent Posts {info['version']}\n\n" + info['notes'] + '\n\n'
        + ('Development prerelease. Not deployed to WordPress.org.\n' if info['prerelease'] else '')
    )
    destination = archive.parent / 'wordpress-assets'
    destination.mkdir(exist_ok=True)
    for filename in ARTWORK:
        shutil.copyfile(root / 'assets' / filename, destination / filename)
    return archive


def verify(root, bundle, destination):
    info = versions(root)
    archive = bundle / f"{SLUG}-{info['version']}.zip"
    expected = archive.with_suffix('.zip.sha256').read_text().strip()
    actual = f'{hashlib.sha256(archive.read_bytes()).hexdigest()}  {archive.name}'
    if expected != actual:
        raise ValueError('ZIP checksum does not match')
    with zipfile.ZipFile(archive) as package:
        names = package.namelist()
        if len(names) != len(set(names)):
            raise ValueError('Duplicate ZIP paths')
        for name in names:
            path = PurePosixPath(name)
            if path.is_absolute() or '..' in path.parts or len(path.parts) < 2 or path.parts[0] != SLUG:
                raise ValueError(f'Unsafe archive path: {name}')
            rel = Path(*path.parts[1:])
            allowed = str(rel) in REQUIRED or (rel.parts[0] in ('includes', 'blocks', 'css', 'languages')
                      and rel.suffix in ('.php', '.js', '.json', '.css', '.mo')
                      and not any(part.startswith('.') for part in rel.parts))
            if not allowed or not (root / rel).is_file() or package.read(name) != (root / rel).read_bytes():
                raise ValueError(f'Unexpected or altered runtime file: {name}')
        for required in REQUIRED:
            if f'{SLUG}/{required}' not in names:
                raise ValueError(f'Missing runtime file: {required}')
        package.extractall(destination)
    versions(destination / SLUG, info['tag'], source=False)
    for filename in ARTWORK:
        if (bundle / 'wordpress-assets' / filename).read_bytes() != (root / 'assets' / filename).read_bytes():
            raise ValueError(f'Altered directory asset: {filename}')
    return destination / SLUG


def tree_hashes(root):
    return {str(p.relative_to(root)): hashlib.sha256(p.read_bytes()).hexdigest()
            for p in root.rglob('*') if p.is_file()}


def verify_svn(root, exported):
    if tree_hashes(root) != tree_hashes(exported):
        raise ValueError('WordPress.org SVN tag differs from the verified ZIP contents')


def live_release(info, expected_sha):
    repository = os.environ['GITHUB_REPOSITORY']
    release = json.loads(subprocess.check_output(['gh', 'api', f"repos/{repository}/releases/tags/{info['tag']}"]))
    release_state(info, release)
    event_path = os.environ.get('GITHUB_EVENT_PATH')
    if event_path:
        original = json.loads(Path(event_path).read_text()).get('release')
        if original and original['id'] != release['id']:
            raise ValueError('The release was replaced after this run started')
    subprocess.run(['git', 'fetch', '--force', 'origin', f"refs/tags/{info['tag']}:refs/tags/{info['tag']}"], check=True)
    actual_sha = subprocess.check_output(['git', 'rev-parse', f"{info['tag']}^{{commit}}"], text=True).strip()
    if expected_sha != actual_sha:
        raise ValueError('Release tag moved after validation')
    return release


def publish(root, bundle, expected_sha):
    info = versions(root)
    release = live_release(info, expected_sha)
    # Never silently replace an already published artifact with different bytes.
    filenames = (f"{SLUG}-{info['version']}.zip", f"{SLUG}-{info['version']}.zip.sha256")
    assets = {asset['name'] for asset in release['assets']}
    for filename in filenames:
        path = bundle / filename
        if filename in assets:
            with tempfile.TemporaryDirectory() as temp:
                previous = Path(temp) / filename
                subprocess.run(['gh', 'release', 'download', info['tag'], '--repo', os.environ['GITHUB_REPOSITORY'], '--pattern', filename, '--output', str(previous)], check=True)
                if previous.read_bytes() != path.read_bytes():
                    raise ValueError(f'Published asset {filename} differs; refusing to overwrite')
        else:
            subprocess.run(['gh', 'release', 'upload', info['tag'], str(path), '--repo', os.environ['GITHUB_REPOSITORY']], check=True)
    # Preserve intentionally written release notes; fill only an empty body.
    if not (release.get('body') or '').strip():
        subprocess.run(['gh', 'release', 'edit', info['tag'], '--repo', os.environ['GITHUB_REPOSITORY'], '--notes-file', str(bundle / 'release-notes.md')], check=True)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('command', choices=('metadata', 'build', 'verify', 'guard', 'publish', 'verify-svn', 'preflight-svn'))
    parser.add_argument('--tag')
    parser.add_argument('--bundle', type=Path, default=ROOT / 'dist')
    parser.add_argument('--destination', type=Path, default=ROOT / '.release')
    parser.add_argument('--sha')
    parser.add_argument('--event', type=Path)
    args = parser.parse_args()
    info = versions(ROOT, args.tag)
    if args.command == 'metadata':
        if args.event:
            release_state(info, json.loads(args.event.read_text())['release'])
        sha = subprocess.check_output(['git', 'rev-parse', 'HEAD'], text=True).strip()
        values = dict(version=info['version'], tag=info['tag'], prerelease=str(info['prerelease']).lower(), sha=sha)
        if os.environ.get('GITHUB_OUTPUT'):
            with open(os.environ['GITHUB_OUTPUT'], 'a') as output:
                output.write(''.join(f'{key}={value}\n' for key, value in values.items()))
        print(json.dumps(values))
    elif args.command == 'build':
        print(stage())
    elif args.command == 'verify':
        print(verify(ROOT, args.bundle, args.destination))
    elif args.command == 'preflight-svn':
        if info['prerelease']:
            raise ValueError('Prereleases must never deploy to WordPress.org')
        current = subprocess.check_output(['svn', 'cat', '--non-interactive', f'https://plugins.svn.wordpress.org/{SLUG}/trunk/readme.txt'], text=True)
        previous = field(current, 'Stable tag')
        if not re.fullmatch(r'\d+(?:\.\d+){1,2}', previous):
            raise ValueError(f'Unrecognized live stable tag: {previous}')
        parts = lambda value: tuple((list(map(int, value.split('.'))) + [0, 0])[:3])
        if parts(previous) > parts(info['version']):
            raise ValueError('Refusing to replace a newer WordPress.org version')
    elif args.command == 'guard':
        live_release(info, args.sha)
    elif args.command == 'publish':
        publish(ROOT, args.bundle, args.sha)
    elif args.command == 'verify-svn':
        verify_svn(args.bundle, args.destination)


if __name__ == '__main__':
    main()

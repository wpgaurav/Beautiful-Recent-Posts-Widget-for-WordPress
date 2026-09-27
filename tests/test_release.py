"""Release safeguards, exercised without publishing or accessing credentials."""
import hashlib
import json
from pathlib import Path
import shutil
import sys
import tempfile
import unittest
import zipfile

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'scripts'))
import package
import release


class ReleaseTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name) / 'source'
        self.root.mkdir()
        for filename in package.REQUIRED + ('package.json',):
            destination = self.root / filename
            destination.parent.mkdir(parents=True, exist_ok=True)
            shutil.copyfile(package.ROOT / filename, destination)
        (self.root / 'assets').mkdir()
        for filename in release.ARTWORK:
            shutil.copyfile(package.ROOT / 'assets' / filename, self.root / 'assets' / filename)
        self.info = release.versions(self.root)

    def event(self):
        return {'tag_name': self.info['tag'], 'draft': False,
                'prerelease': self.info['prerelease'], 'published_at': '2026-09-27T00:00:00Z'}

    def test_all_current_versions_and_changelog_match(self):
        self.assertEqual(release.versions(self.root, self.info['tag'])['version'], self.info['version'])
        self.assertIn('* ', self.info['notes'])

    def test_wrong_tag_rejected(self):
        with self.assertRaises(ValueError):
            release.versions(self.root, 'v999.0.0')

    def test_shell_like_tag_rejected(self):
        with self.assertRaises(ValueError):
            release.versions(self.root, 'v5.0.0; echo unsafe')

    def test_mismatched_readme_rejected(self):
        p = self.root / 'readme.txt'
        p.write_text(p.read_text().replace(f"Stable tag: {self.info['version']}", 'Stable tag: 1.0.0'))
        with self.assertRaises(ValueError):
            release.versions(self.root)

    def test_mismatched_npm_version_rejected(self):
        p = self.root / 'package.json'
        data = json.loads(p.read_text()); data['version'] = '999.0.0'
        p.write_text(json.dumps(data))
        with self.assertRaises(ValueError):
            release.versions(self.root)

    def test_missing_changelog_rejected(self):
        p = self.root / 'readme.txt'
        p.write_text(p.read_text().replace(f"= {self.info['version']} =", '= 1.0.0 ='))
        with self.assertRaises(ValueError):
            release.versions(self.root)

    def test_draft_and_unpublished_release_rejected(self):
        for key, value in (('draft', True), ('published_at', None)):
            with self.subTest(key=key), self.assertRaises(ValueError):
                release.release_state(self.info, dict(self.event(), **{key: value}))

    def test_beta_cannot_be_mislabeled_stable(self):
        info = dict(self.info, tag='v5.0.0-beta.1', prerelease=True)
        event = dict(self.event(), tag_name=info['tag'], prerelease=False)
        with self.assertRaises(ValueError):
            release.release_state(info, event)

    def test_stable_cannot_be_mislabeled_beta(self):
        info = dict(self.info, tag='v5.0.0', prerelease=False)
        event = dict(self.event(), tag_name=info['tag'], prerelease=True)
        with self.assertRaises(ValueError):
            release.release_state(info, event)

    def test_correct_release_state_accepted(self):
        release.release_state(self.info, self.event())

    def test_package_is_deterministic_and_excludes_development(self):
        (self.root / '.env').write_text('NOT_A_REAL_SECRET=test')
        (self.root / 'tests').mkdir()
        (self.root / 'tests' / 'private.php').write_text('test fixture')
        first = package.build(self.root).read_bytes()
        second = package.build(self.root).read_bytes()
        self.assertEqual(first, second)
        with zipfile.ZipFile(package.build(self.root)) as archive:
            self.assertEqual(set(archive.namelist()), {f'{package.SLUG}/{p}' for p in package.REQUIRED})

    def test_missing_runtime_file_fails_build(self):
        (self.root / 'blocks/recent-posts/editor.js').unlink()
        with self.assertRaises(ValueError):
            package.build(self.root)

    def test_runtime_symlinks_rejected(self):
        target = self.root / 'includes/render.php'
        content = target.read_bytes(); target.unlink()
        external = Path(self.temp.name) / 'external.php'; external.write_bytes(content)
        target.symlink_to(external)
        with self.assertRaises(ValueError):
            package.build(self.root)

    def test_bundle_verifies_and_stages_identical_runtime(self):
        release.stage(self.root)
        result = release.verify(self.root, self.root / 'dist', Path(self.temp.name) / 'stage')
        for filename in package.REQUIRED:
            self.assertEqual((result / filename).read_bytes(), (self.root / filename).read_bytes())
        self.assertEqual(set(p.name for p in (self.root / 'dist/wordpress-assets').iterdir()), set(release.ARTWORK))

    def test_changed_checksum_rejected(self):
        archive = release.stage(self.root)
        archive.write_bytes(archive.read_bytes() + b'changed')
        with self.assertRaises(ValueError):
            release.verify(self.root, archive.parent, Path(self.temp.name) / 'stage')

    def test_traversal_rejected_even_with_recomputed_checksum(self):
        archive = release.stage(self.root)
        with zipfile.ZipFile(archive, 'a') as zipped:
            zipped.writestr(f'{package.SLUG}/../../escape.php', 'unsafe')
        archive.with_suffix('.zip.sha256').write_text(f'{hashlib.sha256(archive.read_bytes()).hexdigest()}  {archive.name}\n')
        with self.assertRaises(ValueError):
            release.verify(self.root, archive.parent, Path(self.temp.name) / 'stage')
        self.assertFalse((Path(self.temp.name) / 'escape.php').exists())

    def test_changed_artwork_rejected(self):
        archive = release.stage(self.root)
        (archive.parent / 'wordpress-assets/icon.svg').write_text('changed')
        with self.assertRaises(ValueError):
            release.verify(self.root, archive.parent, Path(self.temp.name) / 'stage')

    def test_svn_comparison_rejects_missing_extra_or_changed_files(self):
        release.stage(self.root)
        original = release.verify(self.root, self.root / 'dist', Path(self.temp.name) / 'stage')
        copied = Path(self.temp.name) / 'svn'; shutil.copytree(original, copied)
        release.verify_svn(original, copied)
        (copied / 'unexpected.php').write_text('unexpected')
        with self.assertRaises(ValueError):
            release.verify_svn(original, copied)


if __name__ == '__main__':
    unittest.main()

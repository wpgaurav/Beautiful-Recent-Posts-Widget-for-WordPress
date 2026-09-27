# Releases and WordPress.org deployment

The release workflow follows Functionalities' published-GitHub-Release model. It is installed separately from publishing a plugin version. Creating a commit, pushing a branch, opening/merging a PR, or pushing a tag alone does **not** deploy the plugin.

## What triggers each action

| Event | Quality checks and bundle | GitHub Release assets | WordPress.org |
| --- | --- | --- | --- |
| Branch push / pull request | Checks, including installed-ZIP integration tests | No | No |
| Manual **Release → Run workflow** | Full dry run, downloadable `release-bundle` artifact | No | No |
| Published prerelease, e.g. `v5.0.0-beta.1` | Full checks | ZIP and SHA-256 | Never |
| Published stable release, e.g. `v5.0.0` | Full checks | ZIP and SHA-256 | Same ZIP contents plus directory branding |

The workflow never creates a release implicitly. Publishing the GitHub Release is the deliberate publication step. Release notes you write are preserved; if empty, the workflow fills them from the matching `readme.txt` changelog section.

## Configured credentials

`SVN_USERNAME` and `SVN_PASSWORD` must be GitHub repository secrets or secrets in the `wordpress.org` environment. Both repository secret names were present when the automation was installed. Values were not read or copied. Use the WordPress.org account's SVN password, and ensure it has commit access to `beautiful-recent-posts-widget`.

GitHub uploads use the job's short-lived `GITHUB_TOKEN`. No personal access token is needed. Only the GitHub upload job has `contents: write`; quality and SVN jobs have read-only GitHub permissions. SVN deployment is serialized through a dedicated concurrency group.

## Prepare a future version

Keep these values identical:

1. Main PHP header `Version:`.
2. Main PHP `BRPW_VERSION` constant.
3. `readme.txt` `Stable tag:` and the new changelog heading.
4. `blocks/recent-posts/block.json` version.
5. `package.json` version.

Also keep the PHP and WordPress requirements synchronized between the main header and readme. Update the README's status and remove development-beta notices when preparing a stable version. For an exact `vX.Y.Z` stable release, the tag's commit must be reachable from the repository's default branch.

Run locally:

```sh
npm run check
python3 -m unittest discover -s tests -p 'test_*.py' -v
python3 scripts/release.py metadata
python3 scripts/release.py build
python3 scripts/release.py verify
```

`actionlint .github/workflows/*.yml` validates workflow syntax. The GitHub workflow also runs the PHP/WordPress and Node matrices defined in `checks.yml`. The PHP matrix installs the ZIP into a disposable WordPress site before running the real integration tests.

## Dry run without publishing

In GitHub, open **Actions → Release → Run workflow** and select the branch to check. Or run:

```sh
gh workflow run release.yml --ref master
```

The run validates versions, tests the selected commit, and uploads `release-bundle`. Both publication jobs are unconditionally skipped for manual runs, even on a stable-version branch. This mode never invokes the SVN deployment action.

## Publish when the version is approved

After the release changes have been merged and checks have passed, create the version tag on the reviewed commit and publish a GitHub Release for that tag. For example, for a future stable 5.0.0:

```sh
git tag -a v5.0.0 <reviewed-commit-sha> -m 'Beautiful Recent Posts 5.0.0'
git push origin refs/tags/v5.0.0
python3 scripts/release.py build
gh release create v5.0.0 --verify-tag --title 'Beautiful Recent Posts 5.0.0' --notes-file dist/release-notes.md
```

Run the local build from the tagged checkout. A beta/RC release must have both a version suffix (`-alpha.N`, `-beta.N`, or `-rc.N`) and GitHub's **Set as a pre-release** flag. The workflow rejects disagreement between those two signals. Do not promote a beta tag by merely clearing the prerelease checkbox; prepare and publish a new stable tag.

## Artifact and deployment guarantees

- Plugin header, constant, readme, block and npm versions must match the tag; a matching changelog is mandatory.
- The same deterministic ZIP is used for GitHub and SVN. SVN receives its extracted runtime files, never an independent source-tree rebuild.
- The ZIP excludes tests, screenshots, working documents, hidden files and development tools. Required runtime files must all exist.
- Consumers verify the ZIP checksum and compare every file against the tested source commit. Directory branding is checked against that commit too.
- Immediately before uploading or deploying, the workflow rereads the live GitHub Release and tag. Drafts, replaced releases, changed tags and mismatched prerelease flags fail closed.
- Existing GitHub assets must match byte-for-byte; the workflow refuses to overwrite different published ZIPs.
- WordPress.org deployment rejects prereleases and downgrades. The 10up action is pinned to an inspected commit.
- Deployment sends the standard/retina icons and banners plus `icon.svg` to SVN's top-level `assets/`. Editable `banner.svg` and QA screenshots stay in GitHub.
- After deployment, the workflow exports the SVN tag and directory assets and compares every file with the verified bundle. A pre-existing conflicting SVN tag fails verification rather than being silently treated as success.

## Failures and retries

If a release job fails, open its log and fix the reported cause. A package or metadata change requires a new version; do not move a published tag. Once credentials or a transient service problem is resolved, rerun the failed workflow jobs. An unchanged existing GitHub artifact is reused, and an existing matching SVN tag is verified.

GitHub publication and WordPress.org deployment are separate jobs. A successful GitHub upload does not prove SVN publication. The workflow proves the committed SVN files; the WordPress.org directory/API/CDN can update later. Verify the public plugin page, API version, downloadable ZIP and directory artwork after a real publication before announcing it complete.

## Current rollout

The source is still **5.0.0-beta.1**. Installing and dry-running this workflow does not publish that beta or change the existing WordPress.org release.

The workflow was installed through [PR #3](https://github.com/wpgaurav/Beautiful-Recent-Posts-Widget-for-WordPress/pull/3). The [first GitHub dry run](https://github.com/wpgaurav/Beautiful-Recent-Posts-Widget-for-WordPress/actions/runs/36320481308) passed all eight matrix jobs and built the release bundle. The downloaded bundle was independently verified against the tested source. GitHub publication and WordPress.org deployment were both skipped. There were still zero release tags/releases, and SVN trunk remained at last-changed revision `1048824`. SVN authentication and a real commit intentionally remain unexercised until a stable release is published.

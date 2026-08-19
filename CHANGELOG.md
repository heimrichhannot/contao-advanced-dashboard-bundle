# Changelog
All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
- Added: provide `VersionListFilterEvent` for adding custom filters to version count and result queries
- Added: provide `VersionListRowEvent` for modifying prepared version rows
- Changed: require PHP 8.4, Contao 5.7, Symfony 7.4 and Doctrine DBAL 3.7 or 4.3
- Changed: use Contao's native Twig templates, pagination and operation menus
- Changed: replace dashboard position and visibility variables with the `dashboard`, `messages`, `shortcuts`, `versions` and `credits` Twig blocks
- Changed: render a fixed set of version columns and remove the `versions_rights.*.columns` option
- Changed: replace `VersionListGenerator` with `VersionListBuilder` and `VersionList`
- Changed: replace the version access-level constants with the `AccessLevel` enum and require a scalar `user_access_level`
- Changed: show 15 version entries per page by default
- Changed: use the modern root-level `config/`, `contao/` and `translations/` bundle structure, Symfony PHP translations and Contao PHP attributes
- Fixed: filter inaccessible `tl_user` entries consistently from the version count and result list
- Fixed: generate Contao 5-compatible, CSRF-protected version operations and correctly detect deleted records
- Removed: dependency on `heimrichhannot/contao-twig-support-bundle`
- Removed: `VersionListDatabaseColumnsEvent` and `VersionListTableColumnsEvent`

## [0.1.2] - 2021-11-10
- Changed: make credit links target blank
- Fixed: wrong license file
- Fixed: last edited margin when no versions available

## [0.1.1] - 2021-07-07
- added postions and flags to make dashboard template more customizable

## [0.1.0] - 2021-07-07
Initial release

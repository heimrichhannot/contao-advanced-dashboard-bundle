# Changelog
All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0] - 2026-08-19
This release is a complete refactor of the bundle to support Contao 5.7.

- Changed: replaced VersionListGenerator with VersionListBuilder and VersionList (BREAKING!)
- Changed: introduced new Events, `VersionListFilterEvent` and `VersionListRowEvent`, replacing the old `VersionListDatabaseColumnsEvent` and `VersionListTableColumnsEvent` (BREAKING!)
- Changed: moved the [be_advanced_dashboard.html.twig](contao/templates/be_advanced_dashboard.html.twig) to the Contao namespace (BREAKING!)
- Changed: updated the blocks and positions in dashboard template (BREAKING!)
- Changed: require PHP 8.4, Contao 5.7, Symfony 7.4 and Doctrine DBAL 3.7 or 4.3
- Changed: use Contao's native Twig templates, pagination and operation menus
- Changed: replace the version access-level constants with the `AccessLevel` enum and require a scalar `user_access_level` (BREAKING!)
- Changed: show 15 version entries per page by default
- Changed: use the modern root-level `config/`, `contao/` and `translations/` bundle structure, Symfony PHP translations and Contao PHP attributes
- Removed: dependency on `heimrichhannot/contao-twig-support-bundle`

## [0.1.2] - 2021-11-10
- Changed: make credit links target blank
- Fixed: wrong license file
- Fixed: last edited margin when no versions available

## [0.1.1] - 2021-07-07
- added postions and flags to make dashboard template more customizable

## [0.1.0] - 2021-07-07
Initial release

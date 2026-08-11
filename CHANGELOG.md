# Changelog
All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
- Changed: require PHP 8.1 and Contao 5.3 or newer
- Changed: use Contao's native Twig template integration and PHP attributes
- Changed: use the modern root-level `config/` and `contao/` bundle structure
- Changed: replace template position variables with overridable Twig blocks
- Fixed: generate valid CSRF-protected version URLs in Contao 5
- Fixed: render row actions on PHP 8 and correctly detect deleted records
- Removed: dependency on `heimrichhannot/contao-twig-support-bundle`

## [0.1.2] - 2021-11-10
- Changed: make credit links target blank
- Fixed: wrong license file
- Fixed: last edited margin when no versions available

## [0.1.1] - 2021-07-07
- added postions and flags to make dashboard template more customizable

## [0.1.0] - 2021-07-07
Initial release

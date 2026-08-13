# Upgrade guide

This guide describes the breaking changes between the committed state of `main` and `feature/contao5`.

## Requirements

The minimum supported versions have changed:

| Dependency | Before | Now |
| --- | --- | --- |
| PHP | `^7.2 \| ^8.0` | `^8.1` |
| Contao | `^4.9` | `^5.3` |
| Symfony components | `^4.4 \| ^5.0` | `^6.4 \| ^7.0` |

Doctrine DBAL `^3.6` or `^4.3` and Contao Manager Plugin `^2.0` are now explicit dependencies.

Upgrade PHP and Contao before updating this bundle. Afterwards, update the Composer dependencies, run the Contao migrations and rebuild the application cache.

## Twig Support Bundle integration removed

`heimrichhannot/contao-twig-support-bundle` is no longer a dependency. If the application does not use it elsewhere, remove its root requirement:

```bash
composer remove heimrichhannot/contao-twig-support-bundle
```

The dashboard now uses Contao's native Twig integration. Dashboard listeners for these Twig Support Bundle events are no longer executed:

- `BeforeParseTwigTemplateEvent`
- `BeforeRenderTwigTemplateEvent`

Move these customizations to a native Contao template override.

## Dashboard template migration

The old bundle template at `backend/be_advanced_dashboard.html.twig` has been replaced with the managed Contao template `@Contao/be_advanced_dashboard.html.twig`.

Create `templates/be_advanced_dashboard.html.twig` in the application:

```twig
{% extends "@Contao/be_advanced_dashboard.html.twig" %}

{% block dashboard_top %}
    <section id="tl_custom_welcome">
        <h2>Welcome</h2>
    </section>
{% endblock %}

{% block shortcuts %}{% endblock %}
```

Overrides stored below `templates/bundles/HeimrichHannotAdvancedDashboardBundle/backend/` are no longer used.

### Template variables replaced by blocks

The position and visibility variables have been removed:

| Removed variable | Replacement |
| --- | --- |
| `positionTop` | Override `dashboard_top` |
| `positionBeforeShortcuts` | Override `before_shortcuts` |
| `positionBeforeVersions` | Override `before_versions` |
| `positonBottom` | Override `dashboard_bottom` |
| `showMessages = false` | Override `messages` with an empty block |
| `showShortcuts = false` | Override `shortcuts` with an empty block |
| `showVersions = false` | Override `versions` with an empty block |

The available blocks are:

- `dashboard`
- `dashboard_top`
- `messages`
- `before_shortcuts`
- `shortcuts`
- `before_versions`
- `versions`
- `dashboard_bottom`
- `credits`

`dashboard_bottom` renders `credits` by default. Call `{{ parent() }}` when adding bottom content if the credits should remain visible. Override `credits` with an empty block to remove them.

Values assigned to the removed variables through the `parseTemplate` hook no longer have an effect.

## Configuration migration

`user_access_level` must be a scalar. Replace the array form shown by the old configuration reference:

```yaml
# Before
user_access_level: [self]

# Now
user_access_level: self
```

The valid values remain `self` and `all`.

The right names and the `huhAdvDash_versionsRights` fields are unchanged. Existing user and user-group assignments do not require a bundle-specific data migration.

## Version-list extension API

`VersionListDatabaseColumnsEvent` and `VersionListTableColumnsEvent` are no longer dispatched. Use `VersionListRowEvent` to modify prepared rows and override the dashboard template to render additional values or columns.

## Direct PHP API changes

Applications using only the bundle configuration and events do not need the changes in this section. They affect direct construction, decoration, inheritance and calls to bundle classes.

### `VersionListGenerator`

`VersionListGenerator` has been removed. Use `VersionListBuilder` to build a `VersionList` instance.

### `VersionListConfiguration`

The `USER_ACCESS_LEVEL_SELF` and `USER_ACCESS_LEVEL_ALL` constants have been replaced by the `AccessLevel` enum.

The `$columns` constructor argument, `getColumns()` method and `versions_rights.*.columns` configuration option have been removed. Override the dashboard template to customize rendered columns.

The constructor now accepts only `array|int` for `$allowedUsers`. Arrays must be non-empty and contain only integers. Use integer `0` to allow all users instead of passing an empty array.

Its state is now private and readonly. Subclasses can no longer access or modify the former protected `$allowedUsers` property.

### `VersionListConfigurationFactory`

Use `createConfigurationForUser()` to create a version-list configuration for a specific `BackendUser`. `createConfigurationForCurrentUser()` delegates to this method.

The constructor now requires `Symfony\Bundle\SecurityBundle\Security` instead of the removed `Symfony\Component\Security\Core\Security` class. Update decorators, test doubles and manual construction accordingly.

The factory now throws a `LogicException` if the authenticated user is not a Contao `BackendUser`.

### Native method signatures

Bundle classes now use strict types and native parameter and return types. Subclasses, decorators and test doubles must declare compatible signatures.

The former protected injected-service properties of `VersionListConfigurationFactory`, `UserGroupContainer` and `ParseTemplateListener` are now private and readonly. Subclasses accessing or replacing those properties must be refactored to use their own dependencies or composition.

`ParseTemplateListener` no longer accepts the Twig Support Bundle's `RenderListener`, `VersionListGenerator` or `VersionListConfigurationFactory`. Its constructor now accepts only `VersionListBuilder`.

Hook and DCA callback registration has moved from Contao service annotations to the `#[AsHook]` and `#[AsCallback]` attributes. Code inspecting the old annotations must use the attributes instead.

## Internal resource paths

The bundle uses the modern root-level directory structure. Update Composer patches, tooling or integrations that reference files inside this package:

| Previous path | New path |
| --- | --- |
| `src/Resources/config/services.yml` | `config/services.yaml` |
| `src/Resources/contao/config/` | `contao/config/` |
| `src/Resources/contao/dca/` | `contao/dca/` |
| `src/Resources/views/backend/be_advanced_dashboard.html.twig` | `contao/templates/be_advanced_dashboard.html.twig` |
| `src/Resources/contao/languages/de/tl_user.php` | `translations/contao_tl_user.de.php` |
| `src/Resources/contao/languages/de/tl_user_group.php` | `translations/contao_tl_user_group.de.php` |

The German translation keys themselves are unchanged.

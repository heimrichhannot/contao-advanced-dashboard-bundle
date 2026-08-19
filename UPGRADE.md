# Upgrade guide

This guide lists the migration steps for upgrading from version 0.1.x to the unreleased Contao 5 version.

## Requirements

The minimum supported versions have changed:

| Dependency | Before | Now |
| --- | --- | --- |
| PHP | `^7.2 \| ^8.0` | `^8.4` |
| Contao | `^4.9` | `^5.7` |
| Symfony components | `^4.4 \| ^5.0` | `^7.4` |

Doctrine DBAL `^3.7` or `^4.3` and Contao Manager Plugin `^2.0` are now explicit dependencies.

Upgrade PHP and Contao before updating this bundle. Afterwards, update the Composer dependencies, run the Contao migrations and rebuild the application cache.

## Twig Support Bundle integration removed

`heimrichhannot/contao-twig-support-bundle` is no longer a dependency. If the application does not use it elsewhere, remove its root requirement:

```bash
composer remove heimrichhannot/contao-twig-support-bundle
```

The dashboard now uses Contao's native Twig integration. Listeners for `BeforeParseTwigTemplateEvent` and `BeforeRenderTwigTemplateEvent` no longer customize the dashboard. Move those customizations to a native template override.

## Dashboard template migration

The old bundle template `backend/be_advanced_dashboard.html.twig` has been replaced with `@Contao/be_advanced_dashboard.html.twig`. Overrides below `templates/bundles/HeimrichHannotAdvancedDashboardBundle/backend/` are no longer used.

Create `templates/be_advanced_dashboard.html.twig` in the application instead:

```twig
{% extends "@Contao/be_advanced_dashboard.html.twig" %}

{% block messages %}
    <section id="tl_custom_welcome">
        <h2>Welcome</h2>
    </section>

    {{ parent() }}
{% endblock %}

{% block shortcuts %}{% endblock %}
```

The template exposes only these blocks:

- `dashboard`
- `messages`
- `shortcuts`
- `versions`
- `credits`

The position variables `positionTop`, `positionBeforeShortcuts`, `positionBeforeVersions` and `positonBottom` have no direct block equivalents. Override `dashboard` to change the complete layout, or prepend/append content in one of the section blocks and call `{{ parent() }}` to retain its original content.

Replace the visibility variables as follows:

| Removed variable | Replacement |
| --- | --- |
| `showMessages = false` | Override `messages` with an empty block |
| `showShortcuts = false` | Override `shortcuts` with an empty block |
| `showVersions = false` | Override `versions` with an empty block |

Values assigned to the removed variables through the `parseTemplate` hook no longer have an effect.

### Template data

Custom dashboard templates must also adapt to the new version-list data:

- The `columns` variable and each row's rendered `cols` array have been removed. Rows now expose named values such as `date`, `username`, `shortTable`, `pid`, `description`, `version`, `active` and `operations`.
- `pagination` is now a `PaginationInterface` object instead of rendered HTML. Render it with `@Contao/backend/component/_pagination.html.twig` as shown in the bundle template.
- The standard version columns are defined by the Twig template. Override the `versions` block to change the table structure.

## Configuration migration

Remove every `versions_rights.*.columns` entry. Configurable columns are no longer supported; customize the `versions` Twig block instead.

`user_access_level` must be a scalar. Replace the array form shown by the old configuration reference:

```yaml
# Before
user_access_level: [self]

# Now
user_access_level: self
```

The valid values remain `self` and `all`. Version-right names and the `huhAdvDash_versionsRights` fields are unchanged, so existing user and user-group assignments need no bundle-specific data migration.

## Version-list events

`VersionListDatabaseColumnsEvent` and `VersionListTableColumnsEvent` have been removed. Delete their listeners.

Use the new `VersionListFilterEvent` to add conditions and parameters to the Doctrine DBAL query after the configured user and table restrictions have been applied:

```php
public function onVersionListFilter(VersionListFilterEvent $event): void
{
    $event->queryBuilder
        ->andWhere('fromTable != :advancedDashboardExcludedTable')
        ->setParameter('advancedDashboardExcludedTable', 'tl_internal_record')
    ;
}
```

The event also exposes the active `VersionListConfiguration`. It is dispatched for both the count and result queries, so listeners must add deterministic conditions that work with both queries and should use unique parameter names.

Use `VersionListRowEvent` to modify a prepared row:

```php
public function onVersionListRow(VersionListRowEvent $event): void
{
    $event->row['description'] = strtoupper((string) $event->row['description']);
}
```

Complete `tl_version` rows are now selected automatically. To display additional values or columns, combine `VersionListRowEvent` with an override of the `versions` Twig block.

## Direct PHP API changes

This section applies only to applications that construct, decorate, extend or call bundle classes directly.

### `VersionListGenerator` replaced

`VersionListGenerator` and its `generate()` and `columns()` methods have been removed. Use the autowired `VersionListBuilder` instead:

```php
$list = $builder->build($configuration);
// Or: $list = $builder->buildForCurrentUser();

$rows = $list->rows();
$pagination = $list->pagination();
```

`VersionListBuilder::build()` returns a `VersionList` object instead of the old `versions`, `columns` and rendered `pagination` array.

### `VersionListConfiguration`

The constructor changed from

```php
new VersionListConfiguration($tables, $columns, $allowedUsers);
```

to

```php
new VersionListConfiguration($tables, $allowedUsers);
```

`getColumns()` has been removed. The `USER_ACCESS_LEVEL_SELF` and `USER_ACCESS_LEVEL_ALL` constants have been replaced by the `AccessLevel::SELF` and `AccessLevel::ALL` enum cases.

`$allowedUsers` accepts only an integer or a non-empty list of integers. Use integer `0` to allow all users instead of passing an empty array.

The default page size changed from 30 to 15. Call `setPerPage(30)` before building the list if the previous page size must be retained.

The former protected `$allowedUsers` property is now private and readonly. Replace subclasses that access or mutate it with composition.

### `VersionListConfigurationFactory`

The constructor now requires `Symfony\Bundle\SecurityBundle\Security` instead of `Symfony\Component\Security\Core\Security`. Update decorators, test doubles and manual construction accordingly.

`createConfigurationForCurrentUser()` now throws a `LogicException` unless the authenticated user is a Contao `BackendUser`. Use the new `createConfigurationForUser(BackendUser $user)` method when building a configuration for a specific back end user.

### Existing service subclasses

Several existing methods now have native parameter and return types. Subclasses, decorators and test doubles must use compatible signatures.

The former protected injected-service properties of `VersionListConfigurationFactory`, `UserGroupContainer` and `ParseTemplateListener` are now private and readonly. `ParseTemplateListener` is itself readonly and its constructor now accepts only `VersionListBuilder`. Refactor subclasses that access those properties or use the old constructor to composition.

## Bundle resource paths

Only integrations or Composer patches that reference files inside this package need to update these paths:

| Previous path | New path |
| --- | --- |
| `src/Resources/config/services.yml` | `config/services.yaml` |
| `src/Resources/contao/config/` | `contao/config/` |
| `src/Resources/contao/dca/` | `contao/dca/` |
| `src/Resources/views/backend/be_advanced_dashboard.html.twig` | `contao/templates/be_advanced_dashboard.html.twig` |
| `src/Resources/contao/languages/de/tl_user.php` | `translations/contao_tl_user.de.php` |
| `src/Resources/contao/languages/de/tl_user_group.php` | `translations/contao_tl_user_group.de.php` |

The German translation keys themselves are unchanged.

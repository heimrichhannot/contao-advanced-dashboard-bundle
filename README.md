# Contao Advanced Dashboard Bundle

Customize the Contao back end dashboard and control which version-log entries and columns are visible to back end users.

## Features

- Replaces the Contao dashboard with an extensible native Twig template.
- Restricts version entries by user and database table.
- Allows individual version-table columns per user or user group.
- Provides events for adding database data and custom rendered columns.

![](docs/img/screenshot.png)

## Requirements

- PHP 8.1 or newer
- Contao 5.3 or newer

## Installation

Install the bundle with Composer or the Contao Manager and then update the database:

```bash
composer require heimrichhannot/contao-advanced-dashboard-bundle
```

## Configure version rights

Add one or more `versions_rights` entries to the project configuration, usually in `config/config.yaml`:

```yaml
huh_advanced_dashboard:
  versions_rights:
    # Show changes from all users, but only for tl_news.
    editor_news:
      user_access_level: all
      columns:
        - date
        - user
        - table
        - id
        - description
        - version
        - actions
      tables:
        - tl_news
```

Clear the application cache, then assign the new version right in the settings of a back end user or user group.

If no assigned configuration matches, the bundle uses the `default` configuration. Administrators are always unrestricted. An empty `tables` or `columns` list means that the corresponding value is unrestricted.

## Customize the dashboard template

The dashboard uses Contao's native template hierarchy. Create `templates/be_advanced_dashboard.html.twig` and extend the bundle template:

```twig
{% extends "@Contao/be_advanced_dashboard.html.twig" %}

{% block dashboard_top %}
    <section id="tl_custom_welcome">
        <h2>Welcome</h2>
        <p>This could be your message!</p>
    </section>
{% endblock %}

{% block shortcuts %}{% endblock %}
{% block credits %}{% endblock %}
```

The following blocks are available:

- `dashboard`
- `dashboard_top`
- `messages`
- `before_shortcuts`
- `shortcuts`
- `before_versions`
- `versions`
- `dashboard_bottom`
- `credits`

The old position and visibility variables and the Twig Support Bundle events are no longer supported.

## Add or modify version columns

Use `VersionListDatabaseColumnsEvent` to fetch additional `tl_version` columns and `VersionListTableColumnsEvent` to add or modify rendered columns:

```php
<?php

declare(strict_types=1);

namespace App\EventListener;

use HeimrichHannot\AdvancedDashboardBundle\Event\VersionListDatabaseColumnsEvent;
use HeimrichHannot\AdvancedDashboardBundle\Event\VersionListTableColumnsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AdvancedDashboardEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            VersionListDatabaseColumnsEvent::class => 'onDatabaseColumns',
            VersionListTableColumnsEvent::class => 'onTableColumns',
        ];
    }

    public function onDatabaseColumns(VersionListDatabaseColumnsEvent $event): void
    {
        $event->addColumn('custom_information');
    }

    public function onTableColumns(VersionListTableColumnsEvent $event): void
    {
        $event->setColumn('custom_column', [
            'label' => 'Custom information',
            'renderCallback' => static fn (array $version): string => $version['custom_information'] ?: 'No custom information',
        ]);
    }
}
```

Rendered callback values are treated as trusted back end HTML. Escape any data that is not controlled by the application.

## Configuration reference

```yaml
huh_advanced_dashboard:
  versions_rights:
    name:
      # Empty means all columns. The default configuration uses these columns:
      columns:
        - date
        - user
        - table
        - id
        - description
        - version
        - actions

      # Empty means all tables.
      tables: []

      # One of "all" or "self".
      user_access_level: self
```

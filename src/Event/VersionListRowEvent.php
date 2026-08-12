<?php

namespace HeimrichHannot\AdvancedDashboardBundle\Event;

class VersionListRowEvent
{
    public function __construct(
        public array $row,
    ) {}

}
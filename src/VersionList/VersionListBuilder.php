<?php

namespace HeimrichHannot\AdvancedDashboardBundle\VersionList;

use Contao\Config;
use Contao\CoreBundle\DataContainer\DataContainerOperationsBuilder;
use Contao\CoreBundle\Pagination\PaginationConfig;
use Contao\CoreBundle\Pagination\PaginationFactoryInterface;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\Database;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use HeimrichHannot\AdvancedDashboardBundle\Event\VersionListRowEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class VersionListBuilder
{
    public function __construct(
        private VersionListConfigurationFactory $configurationFactory,
        private AuthorizationCheckerInterface   $auth,
        private TranslatorInterface             $translator,
        private Connection                      $connection,
        private PaginationFactoryInterface      $paginationFactory,
        private UrlGeneratorInterface           $urlGenerator,
        private EventDispatcherInterface        $eventDispatcher,
        private RequestStack $requestStack,
    ) {}

    public function buildForCurrentUser(): VersionList
    {
        $config = $this->configurationFactory->createConfigurationForCurrentUser();
        return $this->build($config);
    }

    public function build(VersionListConfiguration $config): VersionList
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new \RuntimeException('No current request available.');
        }
        $filter = $this->createFilter($config);
        $total = $this->total($filter);
        $paginationConfig = (new PaginationConfig('vp', $total, $config->perPage))->withIgnoreOutOfBounds();
        $pagination = $this->paginationFactory->create($paginationConfig);


        $arrVersions = array();
        $offset = $pagination->getOffset();
        $rows = $this->fetchEntries($offset, $config->perPage, $filter);

        foreach ($rows as $arrRow)
        {
            $objVersions = (object)$arrRow;
            $arrVersions[] = $this->buildRow($objVersions, $arrRow, $request);
        }

        $arrVersions = array_values($arrVersions);

        $this->enhanceRows($arrVersions, Database::getInstance(), $this->urlGenerator, $this->translator);

        return new VersionList(
            rows: $arrVersions,
            pagination: $pagination,
        );
    }

    private function total(\Closure $filter): int
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('COUNT(*) AS count')
            ->from('tl_version')
            ->where('editUrl IS NOT NULL');

        ($filter)($qb);

        $result = $qb->executeQuery();
        $total = $result->fetchOne();
        $result->free();
        return (int)$total;
    }

    private function createFilter(VersionListConfiguration $config): \Closure
    {
        return function(QueryBuilder $builder) use ($config) {
            if (is_array($config->getAllowedUsers())) {
                $builder->andWhere('userid IN (:userIds)')
                    ->setParameter('userIds', $config->getAllowedUsers(), ArrayParameterType::INTEGER);
            } elseif ($config->getAllowedUsers() !== 0) {
                $builder->andWhere('userid = :userId')
                    ->setParameter('userId', $config->getAllowedUsers());
            }

            if ([] !== $config->getTables()) {
                $builder->andWhere('fromTable IN (:tables)')
                    ->setParameter('tables', $config->getTables(), ArrayParameterType::STRING);
            }

            if (!in_array('tl_user', $config->getTables()) && !$this->auth->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'user')) {
                $builder->andWhere('fromTable != :userTable')
                    ->setParameter('userTable', 'tl_user');
            }
        };
    }

    private function fetchEntries(int $offset, int $perPage, \Closure $filter): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('tl_version')
            ->where('editUrl IS NOT NULL')
            ->orderBy('tstamp', 'DESC')
            ->addOrderBy('pid')
            ->addOrderBy('version', 'DESC')
            ->setMaxResults($perPage)
            ->setFirstResult($offset);

        ($filter)($qb);

        return $qb->fetchAllAssociative();
    }

    /**
     * copy from core
     */
    private function buildRow(object $objVersions, mixed $arrRow, Request $request): mixed
    {
        // Add some parameters
        $arrRow['from'] = max($objVersions->version - 1, 1); // see #4828
        $arrRow['to'] = $objVersions->version;
        $arrRow['date'] = date(Config::get('datimFormat'), $objVersions->tstamp);
        $arrRow['description'] = StringUtil::substr($arrRow['description'], 32);
        $arrRow['shortTable'] = StringUtil::substr($arrRow['fromTable'], 18); // see #5769

        if (isset($arrRow['editUrl']))
        {
            // Adjust the edit URL of files in case they have been renamed (see #671)
            if ($arrRow['fromTable'] == 'tl_files' && ($filesModel = FilesModel::findById($arrRow['pid'])))
            {
                $arrRow['editUrl'] = preg_replace('/id=[^&]+/', 'id=' . $filesModel->path, $arrRow['editUrl']);
            }

            $arrRow['editUrl'] = $request->getBasePath() . '/' . preg_replace(array('/&(amp;)?popup=1/', '/&(amp;)?rt=[^&]+/'), array('', '&amp;rt=' . htmlspecialchars(System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5)), StringUtil::ampersand(ltrim($arrRow['editUrl'], '/')));
        }

        return $arrRow;
    }

    private function enhanceRows(array &$arrVersions, Database $objDatabase, UrlGeneratorInterface $urlGenerator, TranslatorInterface $translator): void
    {
        foreach ($arrVersions as $k=>$v)
        {
            try
            {
                // Mark deleted versions (see #4336)
                $objDeleted = $objDatabase->prepare("SELECT COUNT(*) AS count FROM " . $v['fromTable'] . " WHERE id=?")
                    ->execute($v['pid']);

                $arrVersions[$k]['deleted'] = $objDeleted->count < 1;
            }
            catch (\Exception $e)
            {
                // Probably a disabled module
                unset($arrVersions[$k]);
                continue;
            }

            // Skip deleted files (see #8480)
            if (($v['fromTable'] ?? null) == 'tl_files' && ($arrVersions[$k]['deleted'] ?? null))
            {
                unset($arrVersions[$k]);
                continue;
            }

            $arrVersions[$k]['operations'] = $this->operations($arrVersions, $translator, $urlGenerator, $k, $v);

            $arrVersions[$k] = $this->eventDispatcher->dispatch(new VersionListRowEvent(
                row: $arrVersions[$k],
            ))->row;
        }
    }

    private function operations(array $arrVersions, TranslatorInterface $translator, UrlGeneratorInterface $urlGenerator, int $k, array $v): DataContainerOperationsBuilder
    {
        $operations = System::getContainer()->get('contao.data_container.operations_builder')->initialize('tl_version');

        if ($arrVersions[$k]['deleted'] ?? null)
        {
            $operations->append(array(
                'label' => $translator->trans('MSC.restore', array(), 'contao_default'),
                'href' => $urlGenerator->generate('contao_backend', array('do' => 'undo')),
                'icon' => 'undo.svg',
                'attribtues' => new HtmlAttributes('data-contao--deeplink-target="primary"'),
            ));
        }
        else
        {
            $operations->append(array(
                'label' => $translator->trans('MSC.editElement', array(), 'contao_default'),
                'href' => $v['editUrl'] ?? null,
                'icon' => ($v['editUrl'] ?? null) ? 'edit.svg' : 'edit--disabled.svg',
            ));

            $operations->append(array(
                'label' => $translator->trans('MSC.showDifferences', array(), 'contao_default'),
                'href' => $v['to'] > 1 ? $v['editUrl'] . '&amp;from=' . $v['from'] . '&amp;to=' . $v['to'] . '&amp;versions=1' ?? null : null,
                'icon' => $v['to'] > 1 ? 'diff.svg' : 'diff--disabled.svg',
                'attributes' => (new HtmlAttributes())->set('onclick', "Backend.openModalIframe({title:'" . $translator->trans('MSC.recordOfTable', array($v['pid'], $v['fromTable']), 'contao_default') . "',url:`\${this.href}&amp;popup=1`});return false"),
            ));
        }

        return $operations;
    }
}
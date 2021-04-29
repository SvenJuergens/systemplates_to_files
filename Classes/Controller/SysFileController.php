<?php

namespace SvenJuergens\SystemplatesToFiles\Controller;

use SvenJuergens\SystemplatesToFiles\Utility\Helper;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class SysFileController extends ActionController
{
    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
     */
    protected $listUtility;

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility
     */
    public function injectListUtility(\TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility)
    {
        $this->listUtility = $listUtility;
    }

    public function indexAction()
    {
        $this->view->assign('extensions', $this->getLocalExtensions());

        return $this->view->render();
    }

    public function convertToFilesAction()
    {
        if (!$this->request->hasArgument('pageUid')) {
            $this->forward('index');
        }
        $pageUid = $this->request->getArgument('pageUid');
        $extension = $this->request->getArgument('extension');
        $pageUids = Helper::extendPidListByChildren($pageUid, '99');
        $templates = $this->getSysTemplates($pageUids);
        $pages = $this->getPages($pageUids);

        foreach ($templates as $template) {
            $pages[$template['pid']]['templates'][] =  $template;
        }
        $this->writeFilesToExtension($pages, $extension);
    }

    protected function getLocalExtensions(): array
    {
        $availableExtensions = $this->listUtility->getAvailableExtensions();
        $extensions = array_filter($availableExtensions, function ($extension, $key) {
            return $extension['type'] == 'Local' && ExtensionManagementUtility::isLoaded($key);
        }, ARRAY_FILTER_USE_BOTH);
        ksort($extensions);
        return $extensions;
    }

    protected function getSysTemplates($pageUids): array
    {
        $pidArray = GeneralUtility::intExplode(',', $pageUids, true);
        if (count($pidArray) === 0) {
            return [];
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_template');

        return $queryBuilder
            ->select('uid', 'pid', 'title', 'constants', 'config')
            ->from('sys_template')
            ->where(
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter(
                        $pidArray,
                        Connection::PARAM_INT_ARRAY
                    )
                )
            )
            ->execute()
            ->fetchAll();
    }

    protected function getPages($pageUids)
    {
        $uidArray = GeneralUtility::intExplode(',', $pageUids, true);
        if (count($uidArray) === 0) {
            return [];
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');

        $rows =  $queryBuilder
            ->select('uid', 'pid', 'title')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter(
                        $uidArray,
                        Connection::PARAM_INT_ARRAY
                    )
                )
            )
            ->execute()
            ->fetchAll();
        $tempRows = [];
        foreach ($rows as $row) {
            $tempRows[$row['uid']] = $row;
        }
        return $tempRows;
    }

    protected function writeFilesToExtension($pagesWithTemplates, $extension): void
    {
        $extensionPath = ExtensionManagementUtility::extPath($extension) . 'Configuration/TypoScript/';
        GeneralUtility::mkdir_deep($extensionPath);
        $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getDefaultStorage();

        $constants = [];
        $setup = [];
        foreach ($pagesWithTemplates as $pagesToWrite) {
            $newPath = PathUtility::sanitizeTrailingSeparator(
                $extensionPath . GeneralUtility::underscoredToUpperCamelCase($storage->sanitizeFileName($pagesToWrite['title']))
            );

            foreach ($pagesToWrite['templates'] ?? [] as $template) {
                if (!empty($template['constants'])) {
                    GeneralUtility::mkdir_deep($newPath . 'Constants');
                    $filePath = $newPath . 'Constants/' . $storage->sanitizeFileName($template['title'] . '.typoscript');
                    GeneralUtility::writeFile(
                        $filePath,
                        $template['constants'],
                        true
                    );
                    $constants[] = (explode('ext/', $filePath))[1];
                }
                if (!empty($template['config'])) {
                    GeneralUtility::mkdir_deep($newPath . 'Setup');
                    $filePathConfig = $newPath . 'Setup/' . $storage->sanitizeFileName($template['title'] . '.typoscript');
                    GeneralUtility::writeFile(
                        $filePathConfig,
                        $template['config'],
                        true
                    );
                    $setup[] = (explode('ext/', $filePathConfig))[1];
                }
            }
        }

        if (!empty($constants)) {
            file_put_contents(
                $extensionPath . 'constants.typoscript',
                '@import \'EXT:' . implode('\'' . PHP_EOL . '@import \'EXT:', $constants) . '\'' . PHP_EOL,
                FILE_APPEND
            );
        }
        if (!empty($setup)) {
            file_put_contents(
                $extensionPath . 'setup.typoscript',
                '@import \'EXT:' . implode('\'' . PHP_EOL . '@import \'EXT:', $setup) . '\'' . PHP_EOL,
                FILE_APPEND
            );
        }
    }
}

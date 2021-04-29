<?php

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function () {
        if (TYPO3_MODE === 'BE') {
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                'SvenJuergens.SystemplatesToFiles',
                'tools', // Make module a submodule of 'tools'
                'main', // Submodule key
                'after:extensionmanager',
                [
                    'SysFile' => 'index,convertToFiles'
                ],
                [
                    'access' => 'user,group',
                    'icon'   => 'EXT:systemplates_to_files/Resources/Public/Icons/user_mod_main.svg',
                    'labels' => 'LLL:EXT:systemplates_to_files/Resources/Private/Language/locallang_main.xlf',
                ]
            );
        }

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
            'systemplates_to_files',
            'Configuration/TypoScript',
            'Sys_templates to Files'
        );
    }
);

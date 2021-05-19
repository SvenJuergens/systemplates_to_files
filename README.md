# TYPO3 Extension Sys_templates to files

[![Latest Stable Version](https://img.shields.io/packagist/v/svenjuergens/systemplates-to-files.svg)](https://packagist.org/packages/svenjuergens/systemplates-to-files)

The extension makes it possible to write all TypoScript templates (sys_template records) stored in the database to files. This means that the data can be versioned together with a sitepackage extension.
## Installation

Simply install the extension with Extension Manager or Composer
`composer req svenjuergens/systemplates-to-files`
.

## Usage
In the backend module, a target extension must be selected and the ParentID of the page containing the TypoScript templates.
Then all files are written to the subfolder Configuration/TypoScript.

*Caution: existing files with the same name will be overwritten*.

### Many Thanks for Inspiration
To Sebastian Fischer Extension author of [ew_llxml2xliff](https://extensions.typo3.org/extension/ew_llxml2xliff)
and Sybille Peters Extension author of [migrate2composer](https://extensions.typo3.org/extension/migrate2composer)

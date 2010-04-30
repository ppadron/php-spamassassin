<?php

require_once 'PEAR/PackageFileManager2.php';

$pkg = new PEAR_PackageFileManager2();

$pkg->setOptions(
    array(
        'filelistgenerator' => 'file',
        'packagedirectory'  => dirname(__FILE__),
        'baseinstalldir'    => '/',
        'ignore'            => array('makepackage.php', 'updatepackage.php'),
        'simpleoutput'      => true
    )
);

$pkg->setPackageType('php');
$pkg->addRelease();
$pkg->generateContents();
$pkg->setPackage('SpamAssassin_Client');
$pkg->setChannel('pear.php.net');
$pkg->setReleaseVersion('0.1.0');
$pkg->setAPIVersion('0.1.0');
$pkg->setReleaseStability('alpha');
$pkg->setAPIStability('alpha');
$pkg->setSummary('Spamd protocol client for PHP');
$pkg->setDescription('PHP package that implements the spamd protocol specification');
$pkg->setNotes('Initial release');
$pkg->setPhpDep('5.2.0');
$pkg->setPearinstallerDep('1.7.0');
$pkg->addMaintainer('lead', 'ppadron', 'Pedro Padron', 'ppadron@php.net');
$pkg->setLicense('Apache License 2.0', 'http://www.apache.org/licenses/LICENSE-2.0.html');
$pkg->generateContents();

if (isset($_GET['make']) || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')) {
    $pkg->writePackageFile();
} else {
    $pkg->debugPackageFile();
}

?>

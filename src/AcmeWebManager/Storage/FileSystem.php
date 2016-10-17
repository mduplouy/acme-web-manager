<?php

namespace AcmeWebManager\Storage;

use Octopuce\Acme\Storage\FileSystem as BaseFileSystem;
use AcmeWebManager\CollectionableStorageInterface;
use AcmeWebManager\Domain;

class FileSystem extends BaseFileSystem implements CollectionableStorageInterface
{
    /**
     * @inheritDoc
     */
    public function findAll()
    {
        try {

            $domainFiles = $this->finder
                ->files('*')
                ->depth('>= 1')
                ->in($this->baseDir);

        } catch (\InvalidArgumentException $e) {
            $domainFiles = array();
        }

        $output = array();
        foreach ($domainFiles as $file) {

            $domainName = explode('/', $file->getPath());
            $domainName = array_pop($domainName);

            if (!array_key_exists($domainName, $output)) {

                $domainDir = new \SplFileInfo($file->getPath());
                $creationDate = $domainDir->getCTime();

                $output[$domainName] = new Domain($creationDate);
            }

            $domain = $output[$domainName];
            $domain->addFile($file, $this->read($file->getPathName()));
        }

        return $output;
    }

    /**
     * Remove domain
     *
     * @param string $fqdn
     *
     * @return void
     */
    public function deleteDomain($fqdn)
    {
        $target = $this->baseDir.DIRECTORY_SEPARATOR.$fqdn;

        $domainFiles = $this->finder
            ->files()
            ->in($target);

        foreach ($domainFiles as $file) {
            unlink($target.DIRECTORY_SEPARATOR.$file->getBaseName());
        }

        unlink($target);
    }
}

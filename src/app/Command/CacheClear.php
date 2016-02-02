<?php

namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ClearableCache;

/**
 * CacheClear Command
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Command
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class CacheClear extends Command
{
    /**
     * Command name
     *
     * @var string
     */
    protected $name = 'cache:clear';

    /**
     * Cache (to be cleared).
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Filesystem.
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Cache directory that will be forced cleared.
     *
     * @var string
     */
    protected $cacheDir;

    /**
     * Constructor.
     *
     * @param Cache  $cache    The cache service to be cleared.
     * @param string $cacheDir Cache directory that will be forced cleared.
     */
    public function __construct(Cache $cache, Filesystem $filesystem, $cacheDir)
    {
        parent::__construct($this->name);
        $this->cache = $cache;
        $this->filesystem = $filesystem;
        $this->cacheDir = $cacheDir;
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription('Clear cache.')
            ->setHelp(<<<EOT
The <info>cache:clear</info> command clears the configured cache service
as well as force-clears the application's cache dir.
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->cache instanceof ClearableCache) {
            throw new \RuntimeException(sprintf(
                'Cannot clear cache of class "%s" because it does not implement %s interface',
                get_class($this->cache),
                'Doctrine\Common\Cache\ClearableCache'
            ));
        }

        $success = $this->cache->deleteAll();
        if ($success) {
            $output->writeln('Cleared the application cache.');
        } else {
            $output->writeln('<error>There was a problem clearing the application cache.</error>');
        }

        // also clear the cache dir
        $this->filesystem->remove($this->cacheDir);
        $output->writeln(sprintf(
            'Cleared the file cache dir at <info>"%s"</info>',
            $this->cacheDir
        ));
    }
}

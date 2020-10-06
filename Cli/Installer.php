<?php


namespace Nomess\Component\Orm\Cli;


use Nomess\Component\Config\ConfigStoreInterface;

/**
 * @author Romain Lavabre <webmaster@newwebsouth.fr>
 */
class Installer implements \Nomess\Installer\ExecuteInstallInterface
{
    private const FILENAME = ROOT . 'config/components/orm.yaml';
    private ConfigStoreInterface $configStore;
    
    public function __construct(ConfigStoreInterface $configStore)
    {
        $this->configStore = $configStore;
    }
    
    
    /**
     * @inheritDoc
     */
    public function exec(): void
    {
        copy( __DIR__ . '/orm.yaml', self::FILENAME);
        chown( self::FILENAME, $this->configStore->get( ConfigStoreInterface::DEFAULT_NOMESS)['server']['user']);
    }
}

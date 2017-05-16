<?php

namespace Iris\Config\CRM\sections\Email\Fetcher;

use Iris\Config\CRM\sections\Email\EmailFetcher;
use Iris\Config\CRM\sections\Email\Imap;
use Iris\Iris;
use Iris\IrisException;
use IrisDomain;
use PDO;

class FetcherFactory
{
    protected $connection;

    public function __construct()
    {
        $this->connection = Iris::$app->getContainer()->get('db_access')->connection;
    }

    /**
     * @param string $emailAccountId
     * @return FetcherInterface
     * @throws IrisException
     */
    public function create($emailAccountId)
    {
        $sql = "select fetch_protocol 
            from iris_emailaccount 
            where id = :email_account_id";
        $cmd = $this->connection->prepare($sql);
        $cmd->execute([
            ':email_account_id' => $emailAccountId
        ]);
        $protocol = $cmd->fetch(PDO::FETCH_COLUMN);

        if ($protocol == IrisDomain::getDomain('d_fetch_protocol')->get('imap', 'code', 'db_value')) {
            return new Imap\Fetcher();
        }
        elseif ($protocol == IrisDomain::getDomain('d_fetch_protocol')->get('pop3', 'code', 'db_value')) {
            return new FetcherPop3Adapter();
        }

        throw new IrisException('Unknown email protocol ' . $protocol);
    }
}
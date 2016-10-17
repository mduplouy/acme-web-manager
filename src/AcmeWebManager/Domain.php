<?php

namespace AcmeWebManager;


class Domain
{
    const STATUS_HAS_OWNERSHIP = 'Challenge Pending';
    const STATUS_CERTIFICATE_OK = 'Certificate is valid (expire on %s)';
    const STATUS_CERTIFICATE_RENEW = 'Certificate should be renewed';
    const STATUS_CERTIFICATE_EXPIRED = 'Certificate is expired';
    const STATUS_CERTIFICATE_REVOKED = 'Certificate has been revoked';


    public $createdAt;
    protected $expireAt;

    /**
     * Int status value
     * @var int
     */
    protected $statusValue = 0;

    /**
     * Status workflow
     * @var array
     */
    protected $status = array(
        0 => self::STATUS_HAS_OWNERSHIP,
        1 => self::STATUS_CERTIFICATE_OK,
        2 => self::STATUS_CERTIFICATE_RENEW,
        3 => self::STATUS_CERTIFICATE_EXPIRED,
        3 => self::STATUS_CERTIFICATE_REVOKED,
    );


    public function __construct($createdAt)
    {
        $this->createdAt = date('Y / m / d ', $createdAt);
    }


    public function getStatus()
    {
        return sprintf($this->status[$this->statusValue], $this->getExpirationDate());
    }

    public function getExpirationDate()
    {
        return date('d M Y H:i', $this->expireAt);
    }

    public function addFile($fileInfo, $fileContent)
    {
        switch ($fileInfo->getBaseName()) {
            case 'certificate.json':
                $this->statusValue++;

                $this->expireAt = $fileContent['expireAt'];

                if ($this->expireAt <= time() + (3600 * 24 * 5)) {
                    $this->statusValue++;
                }
                if ($this->expireAt < time()) {
                    $this->statusValue++;
                }

            default:
                break;
        }
    }

}

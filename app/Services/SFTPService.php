<?php

namespace App\Services;

use phpseclib3\Net\SFTP;

class SFTPService
{
    protected $sftp;

    public function __construct()
    {
        $this->sftp = new SFTP('8.8.8.8');
        $username = 'your_username';
        $password = 'your_password';

        if (!$this->sftp->login($username, $password)) {
            throw new \Exception('Login failed on SFTP server');
        }
    }

    public function listFiles($remoteDirectory)
    {
        return $this->sftp->nlist($remoteDirectory);
    }

    public function downloadFile($remoteFilePath, $localFilePath)
    {
        return $this->sftp->get($remoteFilePath, $localFilePath);
    }

    public function deleteFile($remoteFilePath)
    {
        return $this->sftp->delete($remoteFilePath);
    }
}

<?php

namespace Lms\KSeF;

class KSeFSynchronizationLock
{
    private $handle;

    public function acquire(string $path): bool
    {
        $this->handle = fopen($path, 'c');
        if ($this->handle === false) {
            throw new \RuntimeException('Unable to open KSeF synchronization lock file.');
        }

        if (!flock($this->handle, LOCK_EX | LOCK_NB)) {
            fclose($this->handle);
            $this->handle = null;
            return false;
        }

        return true;
    }

    public function release(): void
    {
        if (is_resource($this->handle)) {
            flock($this->handle, LOCK_UN);
            fclose($this->handle);
        }
        $this->handle = null;
    }

    public function __destruct()
    {
        $this->release();
    }
}

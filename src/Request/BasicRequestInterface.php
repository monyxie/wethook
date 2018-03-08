<?php

namespace Monyxie\Webhooked\Request;

interface BasicRequestInterface {
    public function getEventName() : string;
    public function getRepositoryFullName() : string;
    public function validateSecret(string $secret) : bool;
}
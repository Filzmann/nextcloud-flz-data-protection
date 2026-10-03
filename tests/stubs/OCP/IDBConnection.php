<?php
declare(strict_types=1);
namespace OCP;
interface IDBConnection { public function beginTransaction():void; public function commit():void; public function rollBack():void; }

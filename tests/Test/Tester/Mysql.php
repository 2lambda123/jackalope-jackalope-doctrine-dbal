<?php

namespace Jackalope\Tests\Test\Tester;

/**
 * MySQL specific tester class.
 *
 * @author  cryptocompress <cryptocompress@googlemail.com>
 */
class Mysql extends Generic
{
    public function onSetUp(): void
    {
        // mysql from version 5.5.7 does not like to truncate tables with foreign key references: http://bugs.mysql.com/bug.php?id=58788
        $this->getConnection()->executeStatement('SET foreign_key_checks = 0');

        parent::onSetUp();

        $this->getConnection()->executeStatement('SET foreign_key_checks = 1');
    }
}

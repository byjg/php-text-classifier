<?php

#   Copyright (C) 2010-2014 Tobias Leupold <tobias.leupold@web.de>
#
#   This file is part of the b8 package
#
#   This program is free software; you can redistribute it and/or modify it
#   under the terms of the GNU Lesser General Public License as published by
#   the Free Software Foundation in version 2.1 of the License.
#
#   This program is distributed in the hope that it will be useful, but
#   WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
#   or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public
#   License for more details.
#
#   You should have received a copy of the GNU Lesser General Public License
#   along with this program; if not, write to the Free Software Foundation,
#   Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.

/**
 * Functions used by all storage backends
 * Copyright (C) 2010-2014 Tobias Leupold <tobias.leupold@web.de>
 *
 * @license LGPL 2.1
 * @access public
 * @package b8
 * @author Tobias Leupold
 */

namespace ByJG\TextClassifier\Storage;

use ByJG\TextClassifier\Degenerator\DegeneratorInterface;
use ByJG\TextClassifier\Word;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\Factory;
use ByJG\DbMigration\Database\MySqlDatabase;
use ByJG\DbMigration\Database\PgsqlDatabase;
use ByJG\DbMigration\Database\SqliteDatabase;
use ByJG\DbMigration\Migration;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Exception\OrmBeforeInvalidException;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\Exception\OrmModelInvalidException;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Repository;
use ByJG\Util\Uri;

class Rdbms extends Base
{
    protected Uri $uri;

    /**
     * @var Mapper
     */
    protected $mapper;

    /**
     * @var Repository
     */
    protected $repository;

    /**
     * Rdbms constructor.
     * @param Uri $uri
     * @param DegeneratorInterface $degenerator
     * @throws OrmModelInvalidException
     */
    public function __construct($uri, $degenerator)
    {
        $this->uri = $uri instanceof Uri ? $uri : new Uri((string)$uri);

        $this->mapper = new Mapper(
            Word::class,
            'tc_wordlist',
            'token'
        );

        $dataset = Factory::getDbRelationalInstance((string)$this->uri);

        $this->repository = new Repository(new DatabaseExecutor($dataset), $this->mapper);

        $this->degenerator = $degenerator;
    }

    /**
     * Creates the required database tables using migrations.
     * Call this once to set up a new database.
     */
    public function createDatabase(): void
    {
        Migration::registerDatabase(SqliteDatabase::class);
        Migration::registerDatabase(MySqlDatabase::class);
        Migration::registerDatabase(PgsqlDatabase::class);

        $migration = new Migration($this->uri, __DIR__ . '/../../db');
        $migration->reset();

        // Reinitialize connection after migration reset
        $dataset = Factory::getDbRelationalInstance((string)$this->uri);
        $this->repository = new Repository(new DatabaseExecutor($dataset), $this->mapper);
    }

    #[\Override]
    /**
     * @return void
     */
    public function storageOpen()
    {
        // Do nothing;
    }

    #[\Override]
    /**
     * @return void
     */
    public function storageClose()
    {
        // Do nothing;
    }

    /**
     * @param array|string $tokens
     * @return Word[]
     * @throws InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    #[\Override]
    public function storageRetrieve(array|string $tokens)
    {
        $collection = $this->repository->filterIn($tokens);

        $data = array();
        foreach ($collection as $row) {
            $data[$row->token] = $row;
        }
        return $data;
    }

    /**
     * Store a token to the database.
     *
     * @access protected
     * @param Word $word
     * @return void
     * @throws InvalidArgumentException
     * @throws OrmBeforeInvalidException
     * @throws OrmInvalidFieldsException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    #[\Override]
    public function storagePut($word)
    {
        $this->repository->save($word);
    }

    /**
     * Update an existing token.
     *
     * @access protected
     * @param Word $word
     * @return void
     * @throws InvalidArgumentException
     * @throws OrmBeforeInvalidException
     * @throws OrmInvalidFieldsException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    #[\Override]
    public function storageUpdate($word)
    {
        $this->repository->save($word);
    }

    /**
     * Remove a token from the database.
     *
     * @access protected
     * @param array $token
     * @return void
     * @throws InvalidArgumentException
     */
    #[\Override]
    public function storageDel($token)
    {
        $this->repository->delete($token);
    }
}

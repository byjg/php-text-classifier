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
 * Functions used by all storage backend
 * Copyright (C) 2010-2014 Tobias Leupold <tobias.leupold@web.de>
 *
 * @license LGPL 2.1
 * @access public
 * @package b8
 * @author Tobias Leupold
 */

namespace ByJG\TextClassifier\Storage;

use ByJG\TextClassifier\BinaryClassifier;
use ByJG\TextClassifier\Degenerator\DegeneratorInterface;
use ByJG\TextClassifier\Word;
use Exception;

abstract class Base implements StorageInterface
{

    /**
     * @var DegeneratorInterface|null
     */
    protected ?DegeneratorInterface $degenerator = null;

    const INTERNALS_TEXTS     = 'tc*texts';
    const INTERNALS_DBVERSION = 'tc*dbversion';

    /**
     * Checks if the database version is compatible.
     *
     * @return void throws an exception if something's wrong with the database
     * @throws Exception
     */
    #[\Override]
    public function checkVersion()
    {
        $this->storageOpen();
        $internals = $this->storageRetrieve(self::INTERNALS_DBVERSION);
        $this->storageClose();

        if ($internals[BinaryClassifier::DBVERSION]->count_ham == BinaryClassifier::DBVERSION) {
            return;
        }

        throw new Exception(
            'The connected database is not a TextClassifier v' . BinaryClassifier::DBVERSION . ' database.'
        );
    }

    /**
     * Get the database's internal variables.
     *
     * @access public
     * @return Word Returns an array of all internals.
     */
    #[\Override]
    public function getInternals()
    {
        $this->storageOpen();
        $internals = $this->storageRetrieve(self::INTERNALS_TEXTS);
        $this->storageClose();

        return $internals[self::INTERNALS_TEXTS];
    }

    /**
     * Get all data about a list of tags from the database.
     *
     * @access public
     * @param array $tokens
     * @return mixed Returns false on failure, otherwise returns array of returned data
     * in the format array('tokens' => array(token => count),
     * 'degenerates' => array(token => array(degenerate => count))).
     */
    #[\Override]
    public function getTokens($tokens)
    {
        $this->storageOpen();

        # First we see what we have in the database.
        $token_data = $this->storageRetrieve($tokens);

        # Check if we have to degenerate some tokens
        $missing_tokens = array();
        foreach ($tokens as $token) {
            if (! isset($token_data[$token])) {
                $missing_tokens[] = $token;
            }
        }

        if (count($missing_tokens) > 0) {
            # We have to degenerate some tokens
            $degenerates_list = array();

            assert($this->degenerator !== null);
            # Generate a list of degenerated tokens for the missing tokens ...
            $degenerates = $this->degenerator->degenerate($missing_tokens);

            # ... and look them up
            foreach ($degenerates as $token => $token_degenerates) {
                $degenerates_list = array_merge($degenerates_list, $token_degenerates);
            }

            $token_data = array_merge($token_data, $this->storageRetrieve($degenerates_list));
        }

        $this->storageClose();

        # Here, we have all available data in $token_data.

        $return_data_tokens = array();
        $return_data_degenerates = array();

        foreach ($tokens as $token) {
            if (isset($token_data[$token]) === true) {
                # The token was found in the database
                $return_data_tokens[$token] = $token_data[$token];
            } else {
                # The token was not found, so we look if we
                # can return data for degenerated tokens
                assert($this->degenerator !== null);
                foreach ($this->degenerator->getDegenerates($token) as $degenerate) {
                    if (isset($token_data[$degenerate]) === true) {
                        # A degeneration of the token way found in the database
                        $return_data_degenerates[$token][$degenerate] = $token_data[$degenerate];
                    }
                }
            }
        }

        # Now, all token data directly found in the database is in $return_data_tokens
        # and all data for degenerated versions is in $return_data_degenerates, so
        return array(
            'tokens'      => $return_data_tokens,
            'degenerates' => $return_data_degenerates
        );
    }

    /**
     * Stores or deletes a list of tokens from the given category.
     *
     * @access public
     * @param array $tokens
     * @param string $category Either BinaryClassifier::HAM or BinaryClassifier::SPAM
     * @param string $action Either BinaryClassifier::LEARN or BinaryClassifier::UNLEARN
     * @return void
     */
    #[\Override]
    public function processText($tokens, $category, $action)
    {
        # No matter what we do, we first have to check what data we have.

        # First get the internals, including the ham texts and spam texts counter
        $internals = $this->getInternals();

        $this->storageOpen();

        # Then, fetch all data for all tokens we have
        $token_data = $this->storageRetrieve(array_keys($tokens));

        # Process all tokens to learn/unlearn
        foreach ($tokens as $token => $count) {
            if (isset($token_data[$token])) {
                # We already have this token, so update it's data

                # Get the existing data
                $count_ham  = $token_data[$token]->count_ham;
                $count_spam = $token_data[$token]->count_spam;

                # Increase or decrease the right counter
                if ($action === BinaryClassifier::LEARN) {
                    if ($category === BinaryClassifier::HAM) {
                        $count_ham += $count;
                    } elseif ($category === BinaryClassifier::SPAM) {
                        $count_spam += $count;
                    }
                } elseif ($action == BinaryClassifier::UNLEARN) {
                    if ($category === BinaryClassifier::HAM) {
                        $count_ham -= $count;
                    } elseif ($category === BinaryClassifier::SPAM) {
                        $count_spam -= $count;
                    }
                }

                # We don't want to have negative values
                if ($count_ham < 0) {
                    $count_ham = 0;
                }
                if ($count_spam < 0) {
                    $count_spam = 0;
                }

                # Now let's see if we have to update or delete the token
                if ($count_ham != 0 or $count_spam != 0) {
                    $this->storageUpdate(new Word($token, $count_ham, $count_spam));
                } else {
                    $this->storageDel($token);
                }
            } else {
                # We don't have the token. If we unlearn a text, we can't delete it
                # as we don't have it anyway, so just do something if we learn a text
                if ($action === BinaryClassifier::LEARN) {
                    if ($category === BinaryClassifier::HAM) {
                        $this->storagePut(new Word($token, $count, 0));
                    } elseif ($category === BinaryClassifier::SPAM) {
                        $this->storagePut(new Word($token, 0, $count));
                    }
                }
            }
        }

        # Now, all token have been processed, so let's update the right text
        if ($action === BinaryClassifier::LEARN) {
            if ($category === BinaryClassifier::HAM) {
                $internals->count_ham++;
            } elseif ($category === BinaryClassifier::SPAM) {
                $internals->count_spam++;
            }
        } elseif ($action == BinaryClassifier::UNLEARN) {
            if ($category === BinaryClassifier::HAM) {
                if ($internals->count_ham > 0) {
                    $internals->count_ham--;
                }
            } elseif ($category === BinaryClassifier::SPAM) {
                if ($internals->count_spam > 0) {
                    $internals->count_spam--;
                }
            }
        }

        $this->storageUpdate($internals);

        $this->storageClose();
    }
}
